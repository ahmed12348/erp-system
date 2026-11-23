<?php

namespace App\Services;

use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * مين بياخد أنهي تنبيه على أنهي قناة.
 *
 * الافتراضي من `config/notifications.php` حسب الدور، والمستخدم يقدر
 * يعدّله من شاشة «إعدادات التنبيهات». المستخدم اللي عمره ما فتح
 * الشاشة بياخد الافتراضي — مش بيتحرم من التنبيهات لحد ما يظبّطها.
 */
class NotificationPreferences
{
    /**
     * القنوات اللي المستخدم ده عايز يستقبل عليها النوع ده.
     *
     * بترجع فاضية لو النوع مقفول عنده أو دوره مش من المستقبلين.
     *
     * @return array<int, string>
     */
    public function channelsFor(User $user, string $type): array
    {
        $config = config("notifications.types.{$type}");

        if ($config === null || ! $this->roleReceives($user, $config['roles'] ?? [])) {
            return [];
        }

        $saved = $this->saved($user)[$type] ?? null;
        $wanted = is_array($saved) ? $saved : ($config['default'] ?? []);

        return array_values(array_filter(
            array_keys(array_filter($wanted)),
            fn (string $channel): bool => $this->channelAvailable($channel),
        ));
    }

    /**
     * المستخدمين اللي هياخدوا النوع ده.
     *
     * @return Collection<int, User>
     */
    public function recipientsFor(string $type): Collection
    {
        return User::query()
            ->get()
            ->filter(fn (User $user): bool => $this->channelsFor($user, $type) !== []);
    }

    /**
     * التفضيلات المحفوظة — أو الافتراضي لو المستخدم ما ظبّطش حاجة.
     *
     * @return array<string, array<string, bool>>
     */
    public function saved(User $user): array
    {
        /** @var array<string, array<string, bool>> $channels */
        $channels = $user->notificationSetting?->channels ?? [];

        return $channels;
    }

    /**
     * الشكل اللي بيتعرض في الشاشة: كل نوع وكل قناة وحالتها.
     *
     * @return array<int, array<string, mixed>>
     */
    public function matrixFor(User $user): array
    {
        $saved = $this->saved($user);
        $rows = [];

        foreach (config('notifications.types') as $type => $config) {
            if (! $this->roleReceives($user, $config['roles'] ?? [])) {
                continue;
            }

            $current = is_array($saved[$type] ?? null) ? $saved[$type] : $config['default'];
            $channels = [];

            foreach (config('notifications.channels') as $channel => $meta) {
                $channels[] = [
                    'key' => $channel,
                    'label' => $meta['label'],
                    'description' => $meta['description'],
                    'enabled' => (bool) ($current[$channel] ?? false),
                    'available' => $this->channelAvailable($channel),
                    'reason' => $this->unavailableReason($channel),
                ];
            }

            $rows[] = [
                'key' => $type,
                'label' => $config['label'],
                'description' => $config['description'],
                'channels' => $channels,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, array<string, bool>>  $channels
     */
    public function save(User $user, array $channels): void
    {
        NotificationSetting::updateOrCreate(
            ['user_id' => $user->id],
            ['channels' => $channels],
        );
    }

    /**
     * القناة متظبّطة على السيرفر؟
     *
     * واتساب والـ push محتاجين مفاتيح. من غيرها بنعتبرهم مقفولين —
     * أحسن من إن المستخدم يفعّلهم ومايوصلهوش حاجة ويفتكر النظام باظ.
     */
    public function channelAvailable(string $channel): bool
    {
        return match ($channel) {
            'whatsapp' => filled(config('notifications.whatsapp.token'))
                && filled(config('notifications.whatsapp.phone_number_id')),
            'push' => filled(config('notifications.push.public_key'))
                && filled(config('notifications.push.private_key')),
            default => true,
        };
    }

    private function unavailableReason(string $channel): ?string
    {
        if ($this->channelAvailable($channel)) {
            return null;
        }

        return match ($channel) {
            'whatsapp' => 'محتاج مفاتيح واتساب في إعدادات السيرفر.',
            'push' => 'محتاج مفاتيح VAPID في إعدادات السيرفر.',
            default => null,
        };
    }

    /**
     * @param  array<int, string>  $roles
     */
    private function roleReceives(User $user, array $roles): bool
    {
        $role = $user->erpRole();

        return $role !== null && in_array($role->value, $roles, true);
    }
}
