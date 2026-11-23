<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Services\NotificationPreferences;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
}
