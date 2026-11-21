import { ref } from 'vue';

/**
 * حالة «مسح فاتورة» جوه فورم الفاتورة.
 *
 * الفكرة إن المسح بيملا الفورم العادي وخلاص — مفيش فورم تاني ولا مسار
 * حفظ تاني. اللي الكومبوزابل ده بيزوّده حاجتين بس:
 *
 *  1. الملف اللي المستخدم رفعه، عشان يتبعت مع الحفظ ويتعلّق على الفاتورة.
 *     المتصفح ماسكه في الذاكرة، فمفيش ملفات مؤقتة على السيرفر.
 *  2. الحقول اللي محتاجة مراجعة، عشان تتلوّن أصفر لحد ما المستخدم يعدّلها
 *     أو يأكّدها.
 *
 * الحالة جوه الكمبوننت مش على مستوى الموديول — كل صفحة فاتورة ليها
 * مسحتها هي.
 */

export type MatchConfidence = 'exact' | 'likely' | 'none';

export type ScanPartyReview = {
    confidence: MatchConfidence;
    extracted: string | null;
    matched: string | null;
};

export type ScanItemReview = {
    confidence: MatchConfidence;
    extracted: { code: string | null; name: string | null };
    matched: string | null;
    matchedSku: string | null;
    unit: string | null;
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

export type ScanResult = {
    type: 'purchase' | 'sale';
    form: Record<string, unknown>;
    review: ScanReview;
};

/** مفتاح الحقل بتاع بند — عشان التلوين */
export function itemKey(index: number, field: string): string {
    return `items.${index}.${field}`;
}

export function useInvoiceScan() {
    /** الملف المرفوع — بيتبعت مع الحفظ */
    const file = ref<File | null>(null);
    const review = ref<ScanReview | null>(null);

    /** الحقول اللي لسه محتاجة مراجعة */
    const pending = ref(new Set<string>());

    /**
     * بيسجّل نتيجة المسح ويحدّد الحقول اللي هتتلوّن.
     */
    function applyReview(result: ScanResult, uploaded: File) {
        file.value = uploaded;
        review.value = result.review;

        const keys = new Set<string>();

        // الطرف التاني: أي حاجة غير مطابقة تمام محتاجة نظرة
        if (result.review.party.confidence !== 'exact') {
            keys.add(
                result.type === 'purchase' ? 'supplier_id' : 'customer_id',
            );
        }

        // الحقول اللي مالقاهاش على الورق
        result.review.fields.forEach((field) => keys.add(field));

        result.review.items.forEach((item, index) => {
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

    /** بيتمسح لما المستخدم يشيل المرفق */
    function reset() {
        file.value = null;
        review.value = null;
        pending.value = new Set();
    }

    return {
        file,
        review,
        pending,
        applyReview,
        confirm,
        needsReview,
        reviewClass,
        reset,
    };
}
