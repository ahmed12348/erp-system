<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseInvoiceRequest;
use App\Http\Requests\StoreSaleInvoiceRequest;
use App\Models\Attachment;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\AttachmentService;
use App\Services\CategoryService;
use App\Services\InventoryService;
use App\Services\InvoiceDraftService;
use App\Services\InvoiceService;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * صفحة مراجعة الفاتورة الممسوحة.
 *
 * نفس فورم الفاتورة العادي، بس متملّي من المسح، وجنبه صورة الفاتورة
 * الأصلية، وتحته زرارين: «حفظ كمسودة» (بيحفظ ويسيبها مسودة) و«تأكيد
 * الفاتورة» (بيشغّل مسار الحفظ العادي وبيحرّك المخزون والأرصدة).
 *
 * لحد ما المستخدم يضغط «تأكيد»، الفاتورة دي مش موجودة في أي رصيد ولا
 * تقرير.
 */
class DraftInvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceDraftService $drafts,
        private readonly AttachmentService $attachments,
        private readonly InventoryService $inventory,
        private readonly ProductService $products,
        private readonly CategoryService $categories,
    ) {}

    public function showSale(Invoice $draft): Response
    {
        $this->assertDraftOfType($draft, Invoice::TYPE_SALE);

        return Inertia::render('drafts/Sale', [
            'draft' => $this->present($draft),
            'customers' => Customer::active()->orderBy('name')->get([
                'id', 'name', 'city', 'price_tier',
            ])->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'city' => $customer->city,
                'priceTier' => $customer->price_tier,
            ]),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => $this->inventory->catalogueWithStock(),
            'vatRate' => InvoiceService::VAT_RATE,
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'المبيعات', 'href' => route('sales.index')],
                ['title' => 'مراجعة فاتورة ممسوحة', 'href' => route('sales.drafts.show', $draft)],
            ],
        ]);
    }

    public function showPurchase(Invoice $draft): Response
    {
        $this->assertDraftOfType($draft, Invoice::TYPE_PURCHASE);

        return Inertia::render('drafts/Purchase', [
            'draft' => $this->present($draft),
            'suppliers' => Supplier::active()->orderBy('name')->get(['id', 'name', 'country', 'currency']),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => $this->products->options(),
            'categories' => $this->categories->options(),
            'units' => Product::UNITS,
            'suggestedSku' => $this->products->suggestSku(),
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'المشتريات', 'href' => route('purchases.index')],
                ['title' => 'مراجعة فاتورة ممسوحة', 'href' => route('purchases.drafts.show', $draft)],
            ],
        ]);
    }

    /** «حفظ كمسودة» — بيحفظ المراجعة من غير ما يحرّك أي حاجة */
    public function updateSale(StoreSaleInvoiceRequest $request, Invoice $draft): RedirectResponse
    {
        $this->assertDraftOfType($draft, Invoice::TYPE_SALE);

        $this->drafts->update($draft, $request->validated(), $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'اتحفظت كمسودة. المخزون والأرصدة ما اتحركوش.',
        ]);

        return back();
    }

    public function updatePurchase(StorePurchaseInvoiceRequest $request, Invoice $draft): RedirectResponse
    {
        $this->assertDraftOfType($draft, Invoice::TYPE_PURCHASE);

        $this->drafts->update($draft, $request->safe()->except('attachment'), $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'اتحفظت كمسودة. المخزون والأرصدة ما اتحركوش.',
        ]);

        return back();
    }

    /** «تأكيد الفاتورة» — بيشغّل مسار الحفظ العادي */
    public function confirmSale(StoreSaleInvoiceRequest $request, Invoice $draft): RedirectResponse
    {
        $this->assertDraftOfType($draft, Invoice::TYPE_SALE);

        $invoice = $this->drafts->confirm($draft, $request->validated(), $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم تأكيد الفاتورة '.$invoice->number.' وخصم الكميات من المخزن.',
        ]);

        return redirect()->route('sales.show', $invoice);
    }

    public function confirmPurchase(StorePurchaseInvoiceRequest $request, Invoice $draft): RedirectResponse
    {
        $this->assertDraftOfType($draft, Invoice::TYPE_PURCHASE);

        $invoice = $this->drafts->confirm(
            $draft,
            $request->safe()->except('attachment'),
            $request->user(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم تأكيد فاتورة الشراء '.$invoice->number.' وإضافة الكميات للمخزن.',
        ]);

        return redirect()->route('purchases.show', $invoice);
    }

    public function destroy(Invoice $draft): RedirectResponse
    {
        abort_unless($draft->isDraft(), 404);

        $type = $draft->type;

        $this->drafts->discard($draft);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'اتلغت المسودة.',
        ]);

        return redirect()->route(
            $type === Invoice::TYPE_PURCHASE ? 'purchases.index' : 'sales.index'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Invoice $draft): array
    {
        $draft->load('attachments.user:id,name');

        $payload = $draft->scan_payload ?? [];

        return [
            'id' => $draft->id,
            'number' => $draft->number,
            'type' => $draft->type,
            'form' => $payload['form'] ?? [],
            'review' => $payload['review'] ?? null,
            'warehouseId' => $draft->warehouse_id,
            'scannedAt' => $draft->scanned_at?->toDateString(),
            // صورة الفاتورة بتتعرض جنب الفورم وهو بيراجع
            'image' => $draft->attachments->map(
                fn (Attachment $attachment) => $this->attachments->present($attachment)
            )->first(),
        ];
    }

    private function assertDraftOfType(Invoice $draft, string $type): void
    {
        abort_unless($draft->isDraft() && $draft->type === $type, 404);
    }
}
