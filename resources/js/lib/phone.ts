/**
 * تطبيع أرقام التليفون لصيغة واتساب (دولي بالأرقام بس).
 *
 * نسخة من `App\Support\PhoneNumber` — متكرّرة عن قصد. الواجهة محتاجة
 * تبني لينك `wa.me` من غير ما تسأل السيرفر، والسيرفر محتاج نفس
 * التحويل وهو بيبعت بالـ API. `PhoneNumberTest` بيقفل على الاتنين
 * بنفس الحالات عشان ما يفرقوش.
 *
 * مصر هي الافتراضي: الرقم اللي بيبدأ بصفر بيتفهم على إنه محلي مصري،
 * وأي رقم مكتوب بكوده الدولي بيعدّي زي ما هو.
 */

const EGYPT = '20';
const EGYPT_MOBILE_LENGTH = 10;

/** الأرقام العربية والفارسية → لاتينية */
const ARABIC_DIGITS: Record<string, string> = {
    '٠': '0',
    '١': '1',
    '٢': '2',
    '٣': '3',
    '٤': '4',
    '٥': '5',
    '٦': '6',
    '٧': '7',
    '٨': '8',
    '٩': '9',
    '۰': '0',
    '۱': '1',
    '۲': '2',
    '۳': '3',
    '۴': '4',
    '۵': '5',
    '۶': '6',
    '۷': '7',
    '۸': '8',
    '۹': '9',
};

function digits(value: string): string {
    return value
        .split('')
        .map((char) => ARABIC_DIGITS[char] ?? char)
        .join('')
        .replace(/\D+/g, '');
}

/** E.164: من 8 لـ 15 رقم. مش تحقّق حقيقي — بس بيمنع فتح واتساب على كود مخزن */
function plausible(value: string): boolean {
    return value.length >= 8 && value.length <= 15;
}

/**
 * الرقم جاهز لواتساب — أو null لو مش صالح.
 *
 * `01012345678` → `201012345678`
 */
export function toWhatsAppNumber(
    value: string | null | undefined,
): string | null {
    if (value == null) {
        return null;
    }

    const raw = value.trim();

    /*
     * `+` و`00` معناهم «دولي أصلاً». بنفتكر ده قبل ما نشيل الرموز،
     * لأن بعدها مفيش فرق بين `+201…` و`0201…`.
     */
    const international = raw.startsWith('+') || raw.startsWith('00');
    const only = digits(raw);

    if (only === '') {
        return null;
    }

    if (international) {
        const trimmed = only.replace(/^0+/, '');

        return plausible(trimmed) ? trimmed : null;
    }

    if (only.startsWith('0')) {
        const local = only.replace(/^0+/, '');

        return local.length === EGYPT_MOBILE_LENGTH ? EGYPT + local : null;
    }

    /*
     * من غير صفر ولا `+`: لو طوله طول موبايل مصري من غير الصفر
     * (`1012345678`) يبقى مصري، وإلا دولي زي ما هو.
     */
    if (only.length === EGYPT_MOBILE_LENGTH && only.startsWith('1')) {
        return EGYPT + only;
    }

    return plausible(only) ? only : null;
}

export function hasWhatsAppNumber(value: string | null | undefined): boolean {
    return toWhatsAppNumber(value) !== null;
}

/**
 * لينك فتح شات الرقم ده.
 *
 * `wa.me` بيحوّل لتطبيق واتساب لو منصّب، و`web.whatsapp.com/send`
 * بيفتح في المتصفح. المستخدم بيختار من الإعدادات — بس الموبايل
 * بياخد التطبيق دايمًا، لأن واتساب ويب على الموبايل بيقول لك «افتح
 * على الكمبيوتر» وخلاص.
 */
export function whatsAppChatUrl(
    number: string,
    text: string,
    target: 'app' | 'web',
    mobile: boolean,
): string {
    const message = encodeURIComponent(text);

    if (target === 'web' && !mobile) {
        return `https://web.whatsapp.com/send?phone=${number}&text=${message}`;
    }

    return `https://wa.me/${number}?text=${message}`;
}

/** الجهاز ده موبايل؟ بيحدّد التطبيق مقابل الويب ومشاركة الملفات */
export function isMobileDevice(): boolean {
    if (typeof navigator === 'undefined') {
        return false;
    }

    return /android|iphone|ipad|ipod|opera mini|iemobile/i.test(
        navigator.userAgent,
    );
}
