<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * الفواتير المسودة — اللي بتتعمل من مسح صورة.
 *
 * المسودة فاتورة حقيقية بحالة `draft`، لكنها **ما بتلمسش** المخزون ولا
 * أرصدة التجار ولا الخزينة ولا عمولة المندوب. ده مجانًا تقريبًا في
 * النظام ده: كل استعلامات الأرصدة والتقارير ولوحة التحكم أصلاً
 * بتفلتر على `status = confirmed`، فالمسودة بره الحسابات لوحدها.
 *
 * السطور لسه ما اتحوّلتش لـ `invoice_items`: الصنف ممكن يكون لسه
 * ما اتطابقش، و`invoice_items.product_id` مطلوب. فنتيجة المسح بتتخزّن
 * في `scan_payload`، والبنود الحقيقية بتتولد وقت التأكيد بس — وساعتها
 * بيشتغل `InvoiceService` العادي بالظبط زي أي فاتورة اتكتبت بالإيد.
 */
class InvoiceDraftService
{
    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly PurchaseService $purchases,
        private readonly AliasService $aliases,
    ) {}

    /**
     * بيعمل مسودة من نتيجة المسح.
     *
     * @param  array<string, mixed>  $scan
     */
    public function createFromScan(array $scan, User $user): Invoice
    {
        $type = (string) $scan['type'];
        $form = $scan['form'];

        $warehouse = Warehouse::where('is_active', true)->orderBy('id')->first();

        if ($warehouse === null) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'لازم تسجّل مخزن واحد على الأقل قبل ما تمسح فاتورة.',
            ]);
        }

        return DB::transaction(function () use ($type, $form, $scan, $user, $warehouse) {
            $invoice = Invoice::create([
                'type' => $type,
                'number' => $this->invoices->nextNumber($type),
                'customer_id' => $form['customer_id'] ?? null,
                'supplier_id' => $form['supplier_id'] ?? null,
                'warehouse_id' => $warehouse->id,
                'user_id' => $user->id,
                'date' => $form['date'] ?? now()->toDateString(),
                'currency' => $form['currency'] ?? 'EGP',
                'exchange_rate' => $form['exchange_rate'] ?? 1,
                'discount' => $form['discount'] ?? 0,
                'notes' => $form['notes'] ?? null,
                'status' => Invoice::STATUS_DRAFT,
                'scan_payload' => $scan,
                'scanned_at' => now(),
            ]);

            return $invoice;
        });
    }

    /**
     * بيحفظ تعديلات المستخدم على المسودة من غير تأكيد.
     *
     * أي مطابقة المستخدم صحّحها بإيده بتتحفظ كاسم بديل، فالمسح الجاي
     * يعرفها لوحده.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Invoice $draft, array $data, User $user): Invoice
    {
        $this->assertDraft($draft);

        return DB::transaction(function () use ($draft, $data, $user) {
            $payload = $draft->scan_payload ?? [];

            $this->learnFrom($draft, $payload, $data, $user);

            $payload['form'] = [
                ...($payload['form'] ?? []),
                ...$this->formFrom($draft->type, $data),
            ];

            $draft->update([
                'customer_id' => $data['customer_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'],
                'date' => $data['date'],
                'discount' => $data['discount'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'currency' => $data['currency'] ?? $draft->currency,
                'exchange_rate' => $data['exchange_rate'] ?? $draft->exchange_rate,
                'scan_payload' => $payload,
            ]);

            return $draft->fresh();
        });
    }

    /**
     * بيأكّد المسودة: بيشيلها وبيعمل الفاتورة الحقيقية بالمسار العادي.
     *
     * المسودة بتتمسح بعد ما الفاتورة الحقيقية تتعمل، عشان ما يبقاش
     * فيه نسختين لنفس الفاتورة. المرفق بيتنقل معاها.
     *
     * @param  array<string, mixed>  $data
     */
    public function confirm(Invoice $draft, array $data, User $user): Invoice
    {
        $this->assertDraft($draft);

        return DB::transaction(function () use ($draft, $data, $user) {
            $payload = $draft->scan_payload ?? [];

            $this->learnFrom($draft, $payload, $data, $user);

            $attachments = $draft->attachments()->get();

            $confirmed = $draft->type === Invoice::TYPE_PURCHASE
                ? $this->purchases->createPurchase($data, $user)
                : $this->invoices->createSale($data, $user);

            /*
             * «أكّد» حدث مستقل عن «أضاف»: الفاتورة اتولدت دلوقتي، بس
             * اللي حصل فعلاً إن مسودة ممسوحة اتراجعت واتأكّدت — وده
             * اللي المدير بيدوّر عليه في السجل.
             */
            $confirmed->recordActivity('confirmed', [
                'number' => $confirmed->number,
                'from_draft' => $draft->number,
                'total' => (float) $confirmed->total,
            ]);

            // المرفق (صورة الفاتورة) بينتقل للفاتورة المؤكّدة
            foreach ($attachments as $attachment) {
                $attachment->update([
                    'attachable_type' => $confirmed->getMorphClass(),
                    'attachable_id' => $confirmed->getKey(),
                ]);
            }

            $draft->delete();

            return $confirmed;
        });
    }

    /**
     * بيحوّل المسودة من شراء لبيع أو العكس.
     *
     * بيعيد بناء المطابقة من نفس القراءة الخام — من غير ما يرفع الصورة
     * ويقراها تاني، فالتحويل مجاني ومابيستهلكش حصة من المزوّد.
     */
    public function switchType(Invoice $draft, InvoiceScanService $scanner): Invoice
    {
        $this->assertDraft($draft);

        $payload = $draft->scan_payload ?? [];
        $extracted = $payload['extracted'] ?? null;

        abort_if($extracted === null, 404);

        $target = $draft->type === Invoice::TYPE_PURCHASE
            ? Invoice::TYPE_SALE
            : Invoice::TYPE_PURCHASE;

        return DB::transaction(function () use ($draft, $scanner, $extracted, $target) {
            $scan = $scanner->build($extracted, $target);
            $form = $scan['form'];

            $draft->update([
                'type' => $target,
                // الرقم بيتجدّد لأن كل نوع ليه سلسلة أرقام لوحده
                'number' => $this->invoices->nextNumber($target),
                'customer_id' => $form['customer_id'] ?? null,
                'supplier_id' => $form['supplier_id'] ?? null,
                'currency' => $form['currency'] ?? 'EGP',
                'exchange_rate' => $form['exchange_rate'] ?? 1,
                'scan_payload' => $scan,
            ]);

            return $draft->fresh();
        });
    }

    /** بيلغي المسودة ويمسح مرفقاتها */
    public function discard(Invoice $draft): void
    {
        $this->assertDraft($draft);

        DB::transaction(function () use ($draft) {
            $draft->attachments()->each(
                fn ($attachment) => app(AttachmentService::class)->delete($attachment)
            );

            $draft->delete();
        });
    }

    /**
     * بيتعلّم الأسماء اللي المستخدم ربطها بإيده.
     *
     * بنقارن اللي اتقرا من الورق باللي المستخدم مختاره دلوقتي: لو
     * الاتنين مختلفين، يبقى ده تصحيح يستاهل نفتكره.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $data
     */
    private function learnFrom(Invoice $draft, array $payload, array $data, User $user): void
    {
        $review = $payload['review'] ?? [];

        // التاجر
        $partyName = $review['party']['extracted'] ?? null;
        $customerId = $data['customer_id'] ?? null;

        if ($partyName !== null && $customerId !== null) {
            $customer = Customer::find($customerId);

            if ($customer !== null) {
                $this->aliases->rememberCustomer($customer, $partyName, $user);
            }
        }

        // الأصناف — بالترتيب، سطر بسطر
        $items = $data['items'] ?? [];
        $scanned = $review['items'] ?? [];

        foreach ($items as $index => $item) {
            $productId = $item['product_id'] ?? null;
            $raw = $scanned[$index]['extracted']['name'] ?? null;

            if ($productId === null || $raw === null) {
                continue;
            }

            $product = Product::find($productId);

            if ($product !== null) {
                $this->aliases->rememberProduct($product, $raw, $user);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function formFrom(string $type, array $data): array
    {
        $common = [
            'date' => $data['date'],
            'discount' => $data['discount'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'items' => $data['items'] ?? [],
        ];

        return $type === Invoice::TYPE_PURCHASE
            ? [
                ...$common,
                'supplier_id' => $data['supplier_id'] ?? null,
                'currency' => $data['currency'] ?? 'EGP',
                'exchange_rate' => $data['exchange_rate'] ?? 1,
                'shipping_cost' => $data['shipping_cost'] ?? 0,
                'customs_cost' => $data['customs_cost'] ?? 0,
                'clearance_cost' => $data['clearance_cost'] ?? 0,
            ]
            : [...$common, 'customer_id' => $data['customer_id'] ?? null];
    }

    private function assertDraft(Invoice $invoice): void
    {
        abort_unless($invoice->status === Invoice::STATUS_DRAFT, 404);
    }
}
