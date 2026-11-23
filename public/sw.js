/*
 * Service worker للإشعارات.
 *
 * بيعمل حاجتين بس: يعرض الإشعار لما يوصل، ويفتح الصفحة لما المستخدم
 * يدوس عليه. مفيش كاش ولا offline هنا — النظام بيشتغل أونلاين، وكاش
 * غلط بيوقع المستخدم على نسخة قديمة من الشاشة وهو مش واخد باله.
 */

self.addEventListener('push', (event) => {
    if (!event.data) {
        return;
    }

    let payload;

    try {
        payload = event.data.json();
    } catch {
        payload = { title: 'تنبيه جديد', body: event.data.text() };
    }

    event.waitUntil(
        self.registration.showNotification(payload.title ?? 'تنبيه جديد', {
            body: payload.body ?? '',
            icon: '/favicon.ico',
            badge: '/favicon.ico',
            dir: 'rtl',
            lang: 'ar',
            // التنبيهات المتكررة لنفس الحاجة بتستبدل بعض مش بتتكدّس
            tag: payload.tag ?? payload.url ?? 'erp-notification',
            data: { url: payload.url ?? '/dashboard' },
        }),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const target = event.notification.data?.url ?? '/dashboard';

    event.waitUntil(
        self.clients
            .matchAll({ type: 'window', includeUncontrolled: true })
            .then((clients) => {
                /*
                 * لو النظام مفتوح في تاب، بنركّز عليه ونوديه للصفحة
                 * بدل ما نفتح تاب جديد كل مرة.
                 */
                for (const client of clients) {
                    if (client.url.includes(self.location.origin)) {
                        client.navigate(target);

                        return client.focus();
                    }
                }

                return self.clients.openWindow(target);
            }),
    );
});
