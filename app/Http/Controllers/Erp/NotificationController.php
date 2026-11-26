<?php

namespace App\Http\Controllers\Erp;

use App\Enums\ErpAbility;
use App\Http\Controllers\Controller;
use App\Mail\TestAlertMail;
use App\Models\AlertEmailRecipient;
use App\Models\PushSubscription;
use App\Services\NotificationPreferences;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * جرس التنبيهات وإعداداتها.
 *
 * الجرس بيقرا من `notifications` بتاعة Laravel، والإعدادات بتتحفظ لكل
 * مستخدم على حدة — كل واحد بيقرّر يستقبل إيه وفين.
 */
class NotificationController extends Controller
{
    public function __construct(private readonly NotificationPreferences $preferences) {}

    /** قايمة التنبيهات للجرس — اللي مش مقروء الأول */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            // غير المقروء فوق، وبعدين الأحدث
            ->orderByRaw('read_at is not null')
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn ($notification): array => [
                'id' => $notification->id,
                'type' => $notification->data['type'] ?? null,
                'title' => $notification->data['title'] ?? '',
                'body' => $notification->data['body'] ?? '',
                'url' => $notification->data['url'] ?? null,
                'read' => $notification->read_at !== null,
                'at' => $notification->created_at?->diffForHumans(),
            ]);

        return response()->json([
            'notifications' => $notifications,
            'unread' => $user->unreadNotifications()->count(),
        ]);
    }

    /** علّم واحد أو الكل كمقروء */
    public function markRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $id = $request->string('id')->toString();

        if ($id !== '') {
            $user->unreadNotifications()->where('id', $id)->update(['read_at' => now()]);
        } else {
            $user->unreadNotifications()->update(['read_at' => now()]);
        }

        return response()->json(['unread' => $user->unreadNotifications()->count()]);
    }

    /** شاشة «إعدادات التنبيهات» */
    public function settings(Request $request): Response
    {
        return Inertia::render('settings/Notifications', [
            'matrix' => $this->preferences->matrixFor($request->user()),
            'push' => [
                'publicKey' => config('notifications.push.public_key'),
                'subscribed' => $request->user()->pushSubscriptions()->exists(),
            ],
            'whatsapp' => [
                'number' => $request->user()->whatsapp_number,
                'verified' => $request->user()->whatsapp_verified_at !== null,
                'available' => $this->preferences->channelAvailable('whatsapp'),
            ],
            /*
             * إيميلات التنبيهات — للمدير بس.
             *
             * دي إعدادات نظام مش تفضيلات شخصية: الإيميلات دي بتوصلها
             * تنبيهات المخزون حتى لو أصحابها مش مستخدمين في النظام.
             */
            'alertEmails' => [
                'manageable' => $request->user()->allows(ErpAbility::ManageUsers),
                'mailConfigured' => $this->preferences->channelAvailable('mail'),
                'recipients' => AlertEmailRecipient::query()
                    ->orderBy('email')
                    ->get(['id', 'email', 'name', 'is_active'])
                    ->map(fn (AlertEmailRecipient $recipient) => [
                        'id' => $recipient->id,
                        'email' => $recipient->email,
                        'name' => $recipient->name,
                        'isActive' => $recipient->is_active,
                    ]),
            ],
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'إعدادات التنبيهات', 'href' => route('notifications.settings')],
            ],
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'channels' => ['required', 'array'],
        ]);

        $this->preferences->save($request->user(), $validated['channels']);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'اتحفظت إعدادات التنبيهات.',
        ]);

        return back();
    }

    /** تسجيل اشتراك المتصفح في الإشعارات */
    public function subscribePush(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:2000'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'string', 'max:50'],
        ]);

        PushSubscription::updateOrCreate(
            ['endpoint' => $validated['endpoint']],
            [
                'user_id' => $request->user()->id,
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => $validated['contentEncoding'] ?? 'aesgcm',
            ],
        );

        return response()->json(['subscribed' => true]);
    }

    public function unsubscribePush(Request $request): JsonResponse
    {
        PushSubscription::where('user_id', $request->user()->id)
            ->when(
                filled($request->input('endpoint')),
                fn ($query) => $query->where('endpoint', $request->input('endpoint')),
            )
            ->delete();

        return response()->json(['subscribed' => false]);
    }

    // ── إيميلات التنبيهات ─────────────────────────────────────────

    public function storeAlertEmail(Request $request): RedirectResponse
    {
        Gate::authorize(ErpAbility::ManageUsers->value);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:alert_email_recipients,email'],
            'name' => ['nullable', 'string', 'max:255'],
        ], [
            'email.required' => 'اكتب الإيميل.',
            'email.email' => 'الإيميل ده شكله مش مظبوط.',
            'email.unique' => 'الإيميل ده مضاف قبل كده.',
        ]);

        AlertEmailRecipient::create([...$data, 'is_active' => true]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم إضافة '.$data['email'].' لإيميلات التنبيهات.',
        ]);

        return back();
    }

    public function updateAlertEmail(Request $request, AlertEmailRecipient $alertEmail): RedirectResponse
    {
        Gate::authorize(ErpAbility::ManageUsers->value);

        $alertEmail->update($request->validate([
            'is_active' => ['required', 'boolean'],
        ]));

        return back();
    }

    public function destroyAlertEmail(AlertEmailRecipient $alertEmail): RedirectResponse
    {
        Gate::authorize(ErpAbility::ManageUsers->value);

        $alertEmail->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم حذف الإيميل.']);

        return back();
    }

    /**
     * إيميل تجريبي — بيتبعت **فورًا** مش في الطابور.
     *
     * الزرار ده موجود عشان يتأكد إن إعدادات SMTP شغّالة، فلو عدّى على
     * الطابور المستخدم هيشوف «اتبعت» وهو مش متأكد إن حاجة وصلت. وأي
     * عطل من السيرفر بيترجع كرسالة عربية بدل صفحة 500.
     */
    public function sendTestEmail(Request $request): RedirectResponse
    {
        Gate::authorize(ErpAbility::ManageUsers->value);

        $data = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'اكتب الإيميل اللي هيتبعتله.',
        ]);

        if (! $this->preferences->channelAvailable('mail')) {
            return back()->withErrors([
                'test_email' => 'إعدادات الإيميل (MAIL_) مش متظبّطة في .env — الإيميل مش هيتبعت.',
            ]);
        }

        try {
            Mail::to($data['email'])->sendNow(new TestAlertMail);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'test_email' => 'الإرسال فشل: '.$exception->getMessage(),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم إرسال إيميل تجريبي لـ '.$data['email'].' — شوف صندوق الوارد والـ Spam.',
        ]);

        return back();
    }
}
