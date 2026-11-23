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
                /*
                 * القناة اللي مفاتيحها مش متظبّطة بتتخفي خالص — مش
                 * بتظهر مقفولة. خيار معطّل بيخلّي المستخدم يسأل «ليه
                 * ده مقفول؟»، والخيار اللي مش موجود مابيسألش.
                 */
                if (! $this->channelAvailable($channel)) {
                    continue;
                }

                $channels[] = [
                    'key' => $channel,
                    'label' => $meta['label'],
                    'description' => $meta['description'],
                    'enabled' => (bool) ($current[$channel] ?? false),
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
     * الجرس شغّال دايمًا — مش محتاج ولا إعداد. الباقي محتاج مفاتيح،
     * ومن غيرها بيتخفي خالص: المستخدم مايشوفش خيار مش هيوصله حاجة
     * منه، والنظام بيشتغل كامل من غير أي إعداد بريد أو push.
     */
    public function channelAvailable(string $channel): bool
    {
        return match ($channel) {
            'database' => true,

            /*
             * البريد شغّال بس لو فيه ناقل حقيقي. `log` و`array` هما
             * إعدادات التطوير — بيكتبوا في ملف مش بيبعتوا لحد.
             */
            'mail' => ! in_array(config('mail.default'), ['log', 'array', null], true)
                && filled(config('mail.from.address')),

            'whatsapp' => filled(config('notifications.whatsapp.token'))
                && filled(config('notifications.whatsapp.phone_number_id')),

            'push' => filled(config('notifications.push.public_key'))
                && filled(config('notifications.push.private_key')),

            default => false,
        };
    }

    /**
     * القنوات اللي بره الجرس.
     *
     * متفصّلة عن الجرس لأن الجرس بيتبعت فورًا والباقي في الطابور.
     *
     * @return array<int, string>
     */
    public function externalChannels(): array
    {
        return array_values(array_filter(
            array_keys(config('notifications.channels')),
            fn (string $channel): bool => $channel !== 'database'
                && $this->channelAvailable($channel),
        ));
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
