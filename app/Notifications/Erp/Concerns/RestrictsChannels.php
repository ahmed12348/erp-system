<?php

namespace App\Notifications\Erp\Concerns;

/**
 * بيحصر التنبيه على قنوات معيّنة.
 *
 * ليه: الجرس لازم يشتغل **فورًا** من غير كرون ولا عامل طابور، لكن
 * الإيميل والواتساب والـ push بيعملوا نداءات شبكة مش ينفع تستنى عليها
 * وانت بتحفظ فاتورة.
 *
 * فالتنبيه بيتبعت مرتين: مرة `sendNow` للجرس بس (متزامن)، ومرة
 * `send` للباقي (في الطابور لأن الكلاس `ShouldQueue`). كل مرة بتشوف
 * القنوات اللي اتحدّدت لها بس.
 */
trait RestrictsChannels
{
    /** @var array<int, string>|null */
    private ?array $onlyChannels = null;

    /**
     * نسخة من التنبيه محصورة على القنوات دي.
     *
     * @param  array<int, string>  $channels
     */
    public function only(array $channels): static
    {
        $clone = clone $this;
        $clone->onlyChannels = $channels;

        return $clone;
    }

    /**
     * بيصفّي تفضيلات المستخدم على القنوات المسموحة للنسخة دي.
     *
     * @param  array<int, string>  $preferred
     * @return array<int, string>
     */
    protected function restrict(array $preferred): array
    {
        if ($this->onlyChannels === null) {
            return $preferred;
        }

        return array_values(array_intersect($preferred, $this->onlyChannels));
    }
}
