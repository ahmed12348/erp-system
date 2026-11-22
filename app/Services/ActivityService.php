<?php

namespace App\Services;

use App\Enums\ErpAbility;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Support\ActivityLabels;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

/**
 * قراءة سجل النشاط.
 *
 * مكان واحد بيخدم الشاشة وأدوات المساعد مع بعض، فاللي بيتعرض على
 * الشاشة هو نفسه اللي المساعد بيقوله — مفيش نسختين من نفس الاستعلام
 * تفرق عن بعض مع الوقت.
 *
 * **الصلاحيات**: المدير بيشوف نشاط أي حد؛ باقي الأدوار بيشوفوا نشاطهم
 * هما بس. ده أضيق من باقي النظام (اللي كله قراءة مفتوحة) عن قصد —
 * «مين عدّل السعر ده؟» سؤال إداري، مش بيانات شغل.
 */
class ActivityService
{
    /** الاحتفاظ بسنة — بعدها السجل بيتقلّم */
    public const RETENTION_MONTHS = 12;

    /**
     * استعلام مفلتر ومحكوم بصلاحيات المستخدم.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<Activity>
     */
    public function query(User $viewer, array $filters = []): Builder
    {
        return Activity::query()
            ->with(['causer:id,name', 'subject'])
            ->unless($this->seesEveryone($viewer), function (Builder $query) use ($viewer) {
                // غير المدير بيشوف نشاطه هو بس
                $query->where('causer_type', $viewer->getMorphClass())
                    ->where('causer_id', $viewer->getKey());
            })
            ->when(
                filled($filters['user_id'] ?? null),
                fn (Builder $query) => $query->where('causer_id', $filters['user_id'])
            )
            ->when(
                filled($filters['log'] ?? null),
                fn (Builder $query) => $query->where('log_name', $filters['log'])
            )
            ->when(
                filled($filters['event'] ?? null),
                fn (Builder $query) => $query->where('event', $filters['event'])
            )
            ->when(
                filled($filters['from'] ?? null),
                fn (Builder $query) => $query->where(
                    'created_at',
                    '>=',
                    Carbon::parse($filters['from'])->startOfDay()
                )
            )
            ->when(
                filled($filters['to'] ?? null),
                fn (Builder $query) => $query->where(
                    'created_at',
                    '<',
                    Carbon::parse($filters['to'])->addDay()->startOfDay()
                )
            )
            ->latest('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Activity>
     */
    public function paginate(User $viewer, array $filters = [], int $perPage = 30): LengthAwarePaginator
    {
        return $this->query($viewer, $filters)->paginate($perPage)->withQueryString();
    }

    /**
     * آخر دخول لمستخدم — أو لكل المستخدمين لو محدّدش حد.
     *
     * @return array<int, array{user: string, at: string|null, ip: string|null}>
     */
    public function lastLogins(User $viewer, ?string $name = null): array
    {
        $users = User::query()
            ->unless($this->seesEveryone($viewer), fn ($query) => $query->whereKey($viewer->getKey()))
            ->when(filled($name), fn ($query) => $query->where('name', 'like', '%'.$name.'%'))
            ->orderByDesc('last_login_at')
            ->get(['id', 'name', 'last_login_at']);

        return $users->map(fn (User $user): array => [
            'user' => $user->name,
            'at' => $user->last_login_at?->format('Y-m-d H:i'),
            'ip' => $this->lastLoginIp($user),
        ])->all();
    }

    /**
     * مين عمل إيه في سجل معيّن — بالبحث في الموضوع نفسه.
     *
     * @return array<int, array{at: string, user: string|null, event: string, summary: string}>
     */
    public function forSubject(User $viewer, string $needle, int $limit = 20): array
    {
        /*
         * بندوّر على السجل نفسه الأول (فاتورة برقمها، صنف بكوده…)
         * وناخد كل نشاطه.
         *
         * البحث في نص السجل لوحده ما بيكفيش: التعديل بيسجّل الحقول
         * اللي اتغيّرت بس، فتعديل السعر مافيهوش كود الصنف أصلاً وكان
         * بيختفي من النتيجة. بنسيب البحث النصي كمان عشان السجلات
         * اللي اتمسحت — دي مالهاش موضوع نرجع له.
         */
        $subject = $this->findSubject($needle);

        $rows = $this->query($viewer)
            ->where(function (Builder $query) use ($needle, $subject) {
                $query->where('attribute_changes', 'like', '%'.$needle.'%')
                    ->orWhere('properties', 'like', '%'.$needle.'%');

                if ($subject !== null) {
                    $query->orWhere(fn (Builder $inner) => $inner
                        ->where('subject_type', $subject->getMorphClass())
                        ->where('subject_id', $subject->getKey()));
                }
            })
            ->limit($limit)
            ->get();

        return $rows->map(fn (Activity $activity): array => $this->present($activity))->all();
    }

    /**
     * سطر جاهز للعرض — بالعربي.
     *
     * @return array<string, mixed>
     */
    public function present(Activity $activity): array
    {
        /*
         * الإصدار 5 فصل العمودين: تغييرات الموديل بتروح في
         * `attribute_changes`، و`properties` بقت للبيانات اللي بنضيفها
         * بنفسنا (الـ IP والمتصفح مثلاً). بنقرا الاتنين.
         */
        $properties = $activity->properties?->toArray() ?? [];
        $changes = $activity->attribute_changes?->toArray() ?? [];

        return [
            'id' => $activity->id,
            'at' => $activity->created_at?->format('Y-m-d H:i'),
            'user' => $activity->causer?->name,
            'log' => $activity->log_name,
            'logLabel' => ActivityLabels::log($activity->log_name),
            'event' => $activity->event,
            'eventLabel' => ActivityLabels::event($activity->event),
            'subject' => $this->subjectLabel($activity),
            'ip' => $properties['ip'] ?? null,
            'agent' => $properties['agent'] ?? null,
            'email' => $properties['email'] ?? null,
            'changes' => $this->changes($changes),
        ];
    }

    /**
     * «قبل/بعد» للتعديلات — بأسماء حقول عربية.
     *
     * @param  array<string, mixed>  $properties
     * @return array<int, array{field: string, from: string, to: string}>
     */
    private function changes(array $properties): array
    {
        $after = $properties['attributes'] ?? null;

        if (! is_array($after)) {
            return [];
        }

        $before = is_array($properties['old'] ?? null) ? $properties['old'] : [];
        $changes = [];

        foreach ($after as $field => $value) {
            $was = $before[$field] ?? null;

            // الإضافة مالهاش «قبل»، فبنعرض القيمة الجديدة بس
            if ($before !== [] && $was === $value) {
                continue;
            }

            $changes[] = [
                'field' => ActivityLabels::field((string) $field),
                'from' => $before === [] ? '—' : ActivityLabels::value($was),
                'to' => ActivityLabels::value($value),
            ];
        }

        return $changes;
    }

    private function subjectLabel(Activity $activity): ?string
    {
        $subject = $activity->subject;

        if ($subject === null) {
            // الموضوع اتمسح — بنحاول نجيب اسمه من اللي اتسجّل وقتها
            $changes = $activity->attribute_changes?->toArray() ?? [];
            $attributes = $changes['attributes'] ?? [];

            return $attributes['number'] ?? $attributes['name'] ?? $attributes['sku'] ?? null;
        }

        return $subject->number    // فاتورة
            ?? $subject->name      // صنف، تاجر، مورد، مخزن، مستخدم
            ?? $subject->reference // سند
            ?? ('#'.$subject->getKey());
    }

    /**
     * بيدوّر على السجل اللي المستخدم كتب رقمه أو كوده.
     *
     * الترتيب بيبدأ بالمعرّفات الفريدة (رقم الفاتورة، كود الصنف) قبل
     * الأسماء، عشان «HA-9001» ما تجيبش صنف اسمه فيه الرقم ده صدفة.
     */
    private function findSubject(string $needle): ?Model
    {
        return Invoice::where('number', $needle)->first()
            ?? Product::where('sku', $needle)->first()
            ?? Product::where('name', $needle)->first()
            ?? Customer::where('name', $needle)->first()
            ?? Supplier::where('name', $needle)->first();
    }

    private function lastLoginIp(User $user): ?string
    {
        $login = Activity::query()
            ->where('log_name', 'auth')
            ->where('event', 'login')
            ->where('causer_type', $user->getMorphClass())
            ->where('causer_id', $user->getKey())
            ->latest('id')
            ->first();

        return $login?->properties['ip'] ?? null;
    }

    private function seesEveryone(User $viewer): bool
    {
        return $viewer->allows(ErpAbility::ManageUsers);
    }
}
