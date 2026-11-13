/**
 * تنسيق الأرقام والتواريخ والعملة.
 *
 * قاعدة ثابتة في المشروع: الأرقام دايماً لاتينية (0-9) حتى في الواجهة العربية،
 * وأسماء الشهور عربية — يعني "13 نوفمبر 2026" مش "١٣ نوفمبر ٢٠٢٦".
 */

const NUMBER_LOCALE = 'en-US';

export const CURRENCY_SYMBOLS: Record<string, string> = {
    EGP: 'ج.م',
    USD: '$',
    EUR: '€',
    TRY: '₺',
};

const AR_MONTHS = [
    'يناير',
    'فبراير',
    'مارس',
    'أبريل',
    'مايو',
    'يونيو',
    'يوليو',
    'أغسطس',
    'سبتمبر',
    'أكتوبر',
    'نوفمبر',
    'ديسمبر',
] as const;

const AR_WEEKDAYS = [
    'الأحد',
    'الاثنين',
    'الثلاثاء',
    'الأربعاء',
    'الخميس',
    'الجمعة',
    'السبت',
] as const;

type Numeric = number | string | null | undefined;

function toNumber(value: Numeric): number {
    if (value === null || value === undefined || value === '') {
        return 0;
    }

    const parsed = typeof value === 'number' ? value : Number(value);

    return Number.isFinite(parsed) ? parsed : 0;
}

/** تاريخ من نص ISO أو كائن Date — بيرجع null لو القيمة مش صالحة */
function toDate(value: string | Date | null | undefined): Date | null {
    if (!value) {
        return null;
    }

    // "2026-11-13" أو "2026-11-13T10:00:00Z" — بناخد جزء التاريخ عشان
    // ما يحصلش إزاحة يوم بسبب التوقيت المحلي
    if (typeof value === 'string') {
        const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(value);

        if (match) {
            return new Date(
                Number(match[1]),
                Number(match[2]) - 1,
                Number(match[3]),
            );
        }
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? null : date;
}

/** 12500.4 → "12,500" | decimals=2 → "12,500.40" */
export function formatNumber(value: Numeric, decimals = 0): string {
    return new Intl.NumberFormat(NUMBER_LOCALE, {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    }).format(toNumber(value));
}

/** 12500 → "12,500 ج.م" — الرمز بيتغيّر حسب العملة */
export function formatMoney(
    value: Numeric,
    currency = 'EGP',
    decimals = 0,
): string {
    const symbol = CURRENCY_SYMBOLS[currency] ?? currency;

    return `${formatNumber(value, decimals)} ${symbol}`;
}

/** 1250000 → "1.25 مليون ج.م" — للكروت اللي المساحة فيها ضيقة */
export function formatCompactMoney(value: Numeric, currency = 'EGP'): string {
    const amount = toNumber(value);
    const symbol = CURRENCY_SYMBOLS[currency] ?? currency;
    const abs = Math.abs(amount);

    if (abs >= 1_000_000) {
        return `${formatNumber(amount / 1_000_000, 2)} مليون ${symbol}`;
    }

    if (abs >= 10_000) {
        return `${formatNumber(amount / 1000, 1)} ألف ${symbol}`;
    }

    return formatMoney(amount, currency);
}

/** 0.142 → "14.2%" */
export function formatPercent(value: Numeric, decimals = 1): string {
    return `${formatNumber(toNumber(value) * 100, decimals)}%`;
}

/** "2026-11-13" → "13 نوفمبر 2026" */
export function formatDate(value: string | Date | null | undefined): string {
    const date = toDate(value);

    if (!date) {
        return '—';
    }

    return `${date.getDate()} ${AR_MONTHS[date.getMonth()]} ${date.getFullYear()}`;
}

/** "2026-11-13" → "13/11/2026" — للجداول المزدحمة */
export function formatDateShort(
    value: string | Date | null | undefined,
): string {
    const date = toDate(value);

    if (!date) {
        return '—';
    }

    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');

    return `${day}/${month}/${date.getFullYear()}`;
}

/** "2026-11-13" → "الجمعة 13 نوفمبر 2026" */
export function formatDateLong(
    value: string | Date | null | undefined,
): string {
    const date = toDate(value);

    if (!date) {
        return '—';
    }

    return `${AR_WEEKDAYS[date.getDay()]} ${formatDate(date)}`;
}

/** "نوفمبر 2026" — لعناوين الفترات */
export function formatMonth(value: string | Date | null | undefined): string {
    const date = toDate(value);

    if (!date) {
        return '—';
    }

    return `${AR_MONTHS[date.getMonth()]} ${date.getFullYear()}`;
}

/** 3.2 كمية → "3.2 قطعة" مع حذف الأصفار الزايدة */
export function formatQuantity(value: Numeric, unit?: string | null): string {
    const amount = toNumber(value);
    const decimals = Number.isInteger(amount) ? 0 : 2;
    const formatted = formatNumber(amount, decimals);

    return unit ? `${formatted} ${unit}` : formatted;
}

export { AR_MONTHS, AR_WEEKDAYS };
