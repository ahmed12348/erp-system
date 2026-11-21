import { ref } from 'vue';

/**
 * حالة مراجعة فاتورة ممسوحة.
 *
 * المسح بيعمل مسودة على السيرفر، وصفحة المسودة بتعرض الفورم العادي
 * متملّي. الكومبوزابل ده بيمسك حاجة واحدة بس: **الخانات اللي لسه
 * محتاجة مراجعة**، عشان تتلوّن أصفر لحد ما المستخدم يعدّلها أو
 * يأكّدها.
 *
 * الحالة جوه الكمبوننت مش على مستوى الموديول — كل مسودة ليها مراجعتها.
 */

export type MatchConfidence = 'exact' | 'likely' | 'none';

/** إزاي اتطابق: اسم بديل اتعلّمناه، ولا كود، ولا اسم */
export type MatchVia = 'alias' | 'code' | 'name' | null;

export type ScanPartyReview = {
    confidence: MatchConfidence;
    extracted: string | null;
    matched: string | null;
    via: MatchVia;
};

export type ScanItemReview = {
    confidence: MatchConfidence;
    extracted: { code: string | null; name: string | null };
    matched: string | null;
    matchedSku: string | null;
    unit: string | null;
    via: MatchVia;
    /** السعر جه من كتالوج النظام مش من الورق */
    priceFromSystem: boolean;
    /** مفيش سعر لا من الورق ولا من النظام */
    missingPrice: boolean;
};

export type ScanReview = {
    party: ScanPartyReview;
    /** أسماء الحقول اللي الموديل مالقاهاش على الورق */
    fields: string[];
    items: ScanItemReview[];
};

/** مفتاح الحقل بتاع بند — عشان التلوين */
export function itemKey(index: number, field: string): string {
    return `items.${index}.${field}`;
}

export function useInvoiceScan() {
    const review = ref<ScanReview | null>(null);

    /** الحقول اللي لسه محتاجة مراجعة */
    const pending = ref(new Set<string>());

    /**
     * بيحدّد الخانات اللي هتتلوّن من نتيجة المسح.
     */
    function seed(incoming: ScanReview | null, type: 'purchase' | 'sale') {
        review.value = incoming;

        if (!incoming) {
            pending.value = new Set();

            return;
        }

        const keys = new Set<string>();

        // الطرف التاني: أي حاجة غير مطابقة تمام محتاجة نظرة
        if (incoming.party.confidence !== 'exact') {
            keys.add(type === 'purchase' ? 'supplier_id' : 'customer_id');
        }

        // الحقول اللي مالقاهاش على الورق
        incoming.fields.forEach((field) => keys.add(field));

        incoming.items.forEach((item, index) => {
            if (item.confidence !== 'exact') {
                keys.add(itemKey(index, 'product_id'));
            }

            // السعر من النظام مش من الورق، أو مفيش سعر خالص
            if (item.priceFromSystem || item.missingPrice) {
                keys.add(itemKey(index, 'unit_price'));
            }
        });

        pending.value = keys;
    }

    /** بيشيل التلوين أول ما المستخدم يعدّل الحقل */
    function confirm(key: string) {
        if (!pending.value.has(key)) {
            return;
        }

        const next = new Set(pending.value);

        next.delete(key);
        pending.value = next;
    }

    function needsReview(key: string): boolean {
        return pending.value.has(key);
    }

    /** كلاس التلوين الأصفر — بيتحط على الكنترول نفسه */
    function reviewClass(key: string): string {
        return needsReview(key)
            ? 'ring-2 ring-yellow-400 bg-yellow-50 dark:bg-yellow-500/10 dark:ring-yellow-500/60'
            : '';
    }

    /** اللي كان مكتوب على الورق في السطر ده */
    function rawItem(index: number): ScanItemReview | null {
        return review.value?.items[index] ?? null;
    }

    return {
        review,
        pending,
        seed,
        confirm,
        needsReview,
        reviewClass,
        rawItem,
    };
}
