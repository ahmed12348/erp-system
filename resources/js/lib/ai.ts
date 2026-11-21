/**
 * نداءات المساعد الذكي.
 *
 * مسارات المساعد بترجّع JSON مش صفحة Inertia، عشان لوحة المحادثة
 * تتحدّث والمستخدم فاضل مكانه على الشاشة اللي هو فيها.
 *
 * بنستخدم fetch عادي — مفيش axios في المشروع — والـ CSRF بيتبعت من
 * كوكي XSRF-TOKEN زي ما Laravel متوقّع.
 */

/** رسالة عطل عربية جاهزة للعرض */
export class AiError extends Error {
    constructor(
        message: string,
        public readonly reason: string,
    ) {
        super(message);
        this.name = 'AiError';
    }
}

function csrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

/**
 * بيرجّع الـ JSON، ويحوّل أي رد مش ناجح لـ AiError برسالة عربية.
 *
 * السيرفر بيبعت رسالة عربية جاهزة في `message` لكل حالات العطل
 * المعروفة (الحصة، المفتاح، الزحمة، تجاوز الحد) — فبنعرضها زي ما هي.
 */
export async function aiRequest<T>(
    url: string,
    options: { method?: string; body?: FormData | object } = {},
): Promise<T> {
    const { method = 'GET', body } = options;
    const isFormData = body instanceof FormData;

    let response: Response;

    try {
        response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': csrfToken(),
                ...(body && !isFormData
                    ? { 'Content-Type': 'application/json' }
                    : {}),
            },
            body: isFormData ? body : body ? JSON.stringify(body) : undefined,
        });
    } catch {
        // الشبكة نفسها وقعت — مفيش رد من السيرفر أصلاً
        throw new AiError(
            'مفيش اتصال بالسيرفر. راجع النت وجرّب تاني.',
            'network',
        );
    }

    let payload: Record<string, unknown> = {};

    try {
        payload = await response.json();
    } catch {
        payload = {};
    }

    if (!response.ok) {
        throw new AiError(
            typeof payload.message === 'string'
                ? payload.message
                : 'حصل عطل غير متوقع. جرّب تاني.',
            typeof payload.reason === 'string' ? payload.reason : 'http',
        );
    }

    return payload as T;
}
