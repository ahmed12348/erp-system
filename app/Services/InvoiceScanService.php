<?php

namespace App\Services;

use App\Ai\InvoiceSchema;
use App\Exceptions\AiUnavailableException;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Supplier;
use App\Support\ArabicText;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

/**
 * «مسح فاتورة» — بيقرا صورة أو PDF ويرجّع بيانات جاهزة تتحط في الفورم.
 *
 * الموديل بيقرا الورق بس. هو عمره ما بيشوف جدول الأصناف ولا التجار،
 * وعمره ما بيرجّع `id` — المطابقة بتحصل هنا على السيرفر بمقارنة نص.
 * ده بيمنع إنه «يخمّن» صنف غلط، وبيخلّي كل بند يرجع ومعاه درجة ثقة
 * الواجهة بتلوّن على أساسها.
 *
 * ولا حاجة هنا بتتحفظ في قاعدة البيانات — المستخدم بيراجع وبيحفظ بنفسه
 * من زرار الحفظ العادي.
 */
class InvoiceScanService
{
    /** الصنف أو الطرف اتطابق بالكود أو بالاسم حرف بحرف */
    public const MATCH_EXACT = 'exact';

    /** اتطابق بعد تطبيع النص — غالبًا صح بس محتاج نظرة */
    public const MATCH_LIKELY = 'likely';

    /** مفيش مطابقة — المستخدم لازم يختار */
    public const MATCH_NONE = 'none';

    public function __construct(
        private readonly AiService $ai,
        private readonly AliasService $aliases,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws AiUnavailableException
     */
    public function scan(UploadedFile $file, string $type): array
    {
        $extracted = $this->ai->extract(
            InvoiceSchema::prompt($type),
            $file,
            InvoiceSchema::make(),
        );

        return $type === Invoice::TYPE_PURCHASE
            ? $this->buildPurchase($extracted)
            : $this->buildSale($extracted);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildPurchase(array $data): array
    {
        $party = $this->matchParty(Supplier::active()->get(), $data);
        $supplier = $party['model'];

        /*
         * العملة: اللي على الورق أول حاجة، وإلا عملة المورد المطابق،
         * وإلا دولار زي الفورم.
         */
        $currency = $this->currency($data['currency'] ?? null)
            ?? $supplier?->currency
            ?? 'USD';

        $items = $this->matchItems($data['items'] ?? [], fn (Product $product): float => (float) $product->purchase_price);

        return [
            'type' => Invoice::TYPE_PURCHASE,
            'form' => [
                'supplier_id' => $supplier?->id,
                'date' => $this->date($data['date'] ?? null),
                'currency' => $currency,
                'exchange_rate' => $this->positive($data['exchange_rate'] ?? null),
                'discount' => $this->amount($data['discount'] ?? null),
                'shipping_cost' => $this->amount($data['shipping_cost'] ?? null),
                'customs_cost' => $this->amount($data['customs_cost'] ?? null),
                'clearance_cost' => $this->amount($data['clearance_cost'] ?? null),
                'notes' => $this->notes($data),
                'items' => array_map(fn (array $item): array => $item['form'], $items),
            ],
            'review' => [
                'party' => $party['review'],
                'fields' => $this->uncertainFields($data, [
                    'date', 'currency', 'exchange_rate',
                ]),
                'items' => array_map(fn (array $item): array => $item['review'], $items),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildSale(array $data): array
    {
        $party = $this->matchParty(Customer::active()->get(), $data);
        $customer = $party['model'];

        /*
         * سعر البيع بيرجع لشريحة التاجر لو الفاتورة مش مكتوب فيها سعر —
         * نفس المنطق اللي الفورم بيعمله لما تختار صنف بإيدك. والبند
         * بيتعلّم عشان الواجهة تلوّنه أصفر: السعر ده من النظام مش من الورق.
         */
        $tier = $customer?->price_tier;

        $items = $this->matchItems(
            $data['items'] ?? [],
            fn (Product $product): float => $tier === 'wholesale' && $product->wholesale_price !== null
                ? (float) $product->wholesale_price
                : (float) $product->sale_price,
        );

        return [
            'type' => Invoice::TYPE_SALE,
            'form' => [
                'customer_id' => $customer?->id,
                'date' => $this->date($data['date'] ?? null),
                'discount' => $this->amount($data['discount'] ?? null),
                'notes' => $this->notes($data),
                // فاتورة البيع بالجنيه، ومافيهاش مصاريف استيراد
                'items' => array_map(fn (array $item): array => $item['form'] + ['discount' => 0], $items),
            ],
            'review' => [
                'party' => $party['review'],
                'fields' => $this->uncertainFields($data, ['date']),
                'items' => array_map(fn (array $item): array => $item['review'], $items),
            ],
        ];
    }

    /**
     * مطابقة المورد أو التاجر.
     *
     * مفيش عمود كود للموردين ولا التجار في النظام، فالمطابقة بالاسم
     * أساسًا: حرف بحرف الأول، وبعدين بعد تطبيع النص (همزات وتاء مربوطة
     * وتشكيل)، وبعدين بالتليفون لو الفاتورة كاتباه — التليفون دليل قوي
     * لما الاسم مكتوب بشكل مختلف.
     *
     * @param  Collection<int, Supplier|Customer>  $candidates
     * @param  array<string, mixed>  $data
     * @return array{model: Supplier|Customer|null, review: array<string, mixed>}
     */
    private function matchParty(Collection $candidates, array $data): array
    {
        $name = $this->text($data['party_name'] ?? null);
        $phone = ArabicText::digits($this->text($data['party_phone'] ?? null));

        $match = null;
        $confidence = self::MATCH_NONE;
        $via = null;

        /*
         * الاسم البديل الأول — ده اسم المستخدم صحّحه بإيده قبل كده،
         * فهو أقوى من أي مقارنة نص.
         */
        if ($candidates->first() instanceof Customer) {
            $learned = $this->aliases->matchCustomer($name);

            if ($learned !== null && $candidates->contains('id', $learned->id)) {
                $match = $learned;
                $confidence = self::MATCH_EXACT;
                $via = 'alias';
            }
        }

        if ($match === null && $name !== null) {
            $exact = $candidates->first(fn ($party): bool => $party->name === $name);

            if ($exact !== null) {
                $match = $exact;
                $confidence = self::MATCH_EXACT;
            }
        }

        if ($match === null && $name !== null) {
            $normalized = ArabicText::normalize($name);

            $likely = $normalized === '' ? null : $candidates->first(
                fn ($party): bool => ArabicText::normalize($party->name) === $normalized
            );

            if ($likely !== null) {
                $match = $likely;
                $confidence = self::MATCH_LIKELY;
            }
        }

        if ($match === null && $phone !== '') {
            $byPhone = $candidates->first(
                fn ($party): bool => $party->phone !== null
                    && ArabicText::digits($party->phone) === $phone
            );

            if ($byPhone !== null) {
                $match = $byPhone;
                $confidence = self::MATCH_LIKELY;
            }
        }

        return [
            'model' => $match,
            'review' => [
                'confidence' => $confidence,
                'extracted' => $name,
                'matched' => $match?->name,
                'via' => $via,
            ],
        ];
    }

    /**
     * مطابقة البنود — بالكود الأول وبعدين بالاسم.
     *
     * @param  array<int, mixed>  $rows
     * @param  callable(Product): float  $fallbackPrice
     * @return array<int, array{form: array<string, mixed>, review: array<string, mixed>}>
     */
    private function matchItems(array $rows, callable $fallbackPrice): array
    {
        $products = Product::active()->get(['id', 'sku', 'name', 'unit', 'purchase_price', 'sale_price', 'wholesale_price']);

        // بنبني الفهارس مرة واحدة بدل ما ندوّر في الجدول كله لكل بند
        $bySku = $products->keyBy(fn (Product $product): string => mb_strtolower($product->sku));
        $byName = $products->keyBy(fn (Product $product): string => $product->name);
        $byNormalized = $products->keyBy(fn (Product $product): string => ArabicText::normalize($product->name));

        $items = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $code = $this->text($row['code'] ?? null);
            $name = $this->text($row['name'] ?? null);

            // بند من غير اسم ولا كود ولا كمية مالوش لازمة
            if ($code === null && $name === null) {
                continue;
            }

            [$product, $confidence, $via] = $this->matchProduct($code, $name, $bySku, $byName, $byNormalized);

            // الكميات والأسعار ساعات بترجع كنص فيه أرقام هندية أو وحدة
            $quantity = $this->positive(ArabicText::number($row['quantity'] ?? null)) ?? 1;
            $extractedPrice = $this->amount(ArabicText::number($row['unit_price'] ?? null));

            /*
             * السعر: اللي على الورق أولًا. لو مفيش، وكان الصنف اتطابق،
             * بنرجع لسعر النظام ونعلّم البند عشان يتلوّن.
             */
            $priceFromSystem = $extractedPrice === null && $product !== null;
            $price = $extractedPrice ?? ($product !== null ? $fallbackPrice($product) : null);

            $items[] = [
                'form' => [
                    'product_id' => $product?->id,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                ],
                'review' => [
                    'confidence' => $confidence,
                    'extracted' => ['code' => $code, 'name' => $name],
                    'matched' => $product?->name,
                    'matchedSku' => $product?->sku,
                    'unit' => $product?->unit,
                    'via' => $via,
                    'priceFromSystem' => $priceFromSystem,
                    'missingPrice' => $price === null,
                ],
            ];
        }

        return $items;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, Product>  $bySku
     * @param  \Illuminate\Support\Collection<string, Product>  $byName
     * @param  \Illuminate\Support\Collection<string, Product>  $byNormalized
     * @return array{0: Product|null, 1: string, 2: string|null}
     */
    private function matchProduct(
        ?string $code,
        ?string $name,
        $bySku,
        $byName,
        $byNormalized,
    ): array {
        /*
         * الاسم البديل الأول — المستخدم صحّح الاسم ده بإيده قبل كده،
         * فهو أقوى دليل عندنا، أقوى حتى من الكود المكتوب على الورق.
         */
        foreach ([$name, $code] as $candidate) {
            $learned = $this->aliases->matchProduct($candidate);

            if ($learned !== null) {
                return [$learned, self::MATCH_EXACT, 'alias'];
            }
        }

        // الكود — أدق حاجة على الفاتورة المطبوعة
        if ($code !== null) {
            $product = $bySku->get(mb_strtolower($code));

            if ($product !== null) {
                return [$product, self::MATCH_EXACT, 'code'];
            }
        }

        if ($name === null) {
            return [null, self::MATCH_NONE, null];
        }

        $product = $byName->get($name);

        if ($product !== null) {
            return [$product, self::MATCH_EXACT, 'name'];
        }

        // ساعات اسم الصنف نفسه بيتكتب في خانة الكود
        $product = $bySku->get(mb_strtolower($name));

        if ($product !== null) {
            return [$product, self::MATCH_LIKELY, 'code'];
        }

        $normalized = ArabicText::normalize($name);

        $product = $normalized === '' ? null : $byNormalized->get($normalized);

        return $product !== null
            ? [$product, self::MATCH_LIKELY, 'name']
            : [null, self::MATCH_NONE, null];
    }

    /**
     * الحقول اللي الموديل مالقاهاش على الورق — الواجهة بتلوّنها أصفر.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $keys
     * @return array<int, string>
     */
    private function uncertainFields(array $data, array $keys): array
    {
        return array_values(array_filter(
            $keys,
            fn (string $key): bool => blank($data[$key] ?? null),
        ));
    }

    /** تاريخ صالح ومش في المستقبل، وإلا null والفورم بيسيب بتاعه */
    private function date(mixed $value): ?string
    {
        $text = $this->text($value);

        if ($text === null) {
            return null;
        }

        try {
            $date = Carbon::parse($text);
        } catch (\Throwable) {
            return null;
        }

        // الفورم بيرفض تاريخ في المستقبل، فما ينفعش نملاه بواحد
        return $date->isFuture() ? null : $date->toDateString();
    }

    private function currency(mixed $value): ?string
    {
        $text = mb_strtoupper((string) $this->text($value));

        return in_array($text, ['EGP', 'USD', 'EUR', 'TRY'], true) ? $text : null;
    }

    /** مبلغ موجب أو صفر — السالب على الفاتورة غلط قراءة */
    private function amount(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $amount = (float) $value;

        return $amount >= 0 ? round($amount, 2) : null;
    }

    private function positive(mixed $value): ?float
    {
        $amount = $this->amount($value);

        return $amount !== null && $amount > 0 ? $amount : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function notes(array $data): ?string
    {
        $notes = $this->text($data['notes'] ?? null);
        $number = $this->text($data['number'] ?? null);

        // رقم الفاتورة الأصلي مفيد في الملاحظات — النظام بيرقّم بنفسه
        $parts = array_filter([
            $number !== null ? "رقم الفاتورة الأصلي: {$number}" : null,
            $notes,
        ]);

        $combined = implode("\n", $parts);

        return $combined === '' ? null : mb_substr($combined, 0, 1000);
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        // الموديل ساعات بيكتب "null" أو "غير محدد" بدل ما يسيبها فاضية
        return in_array(mb_strtolower($trimmed), ['', 'null', 'n/a', 'غير محدد', '-'], true)
            ? null
            : $trimmed;
    }
}
