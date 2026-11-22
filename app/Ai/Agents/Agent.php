<?php

namespace App\Ai\Agents;

use App\Enums\ErpRole;
use App\Models\User;

/**
 * وكيل — تبويب في لوحة «اسأل السيستم».
 *
 * كل وكيل بيشوف أدواته هو بس. ده مش تنظيم شكلي: الموديل بياخد وصف كل
 * أداة مع كل نداء، فعشرين أداة في تبويب واحد معناها تعليمات أطول ووقت
 * أطول واحتمال أكبر إنه يختار الأداة الغلط. أربع تبويبات بأربع مجموعات
 * صغيرة أسرع وأدق.
 *
 * **القاعدة الذهبية**: كل رقم بيتحسب في PHP جوه الأداة. الموديل بيشرح
 * ويوصّي بس — عمره ما بيجمع ولا بيقدّر.
 */
abstract class Agent
{
    /** المعرّف في الـ URL وفي الجلسة */
    abstract public function key(): string;

    /** الاسم على التبويب */
    abstract public function label(): string;

    /** جملة قصيرة تحت الاسم */
    abstract public function description(): string;

    /** تعليمات الوكيل — بتتضاف لقواعد النظام العامة */
    abstract public function instructions(): string;

    /**
     * الأسئلة المقترحة في التبويب وهو فاضي.
     *
     * @return array<int, string>
     */
    abstract public function starters(): array;

    /**
     * أدوات الوكيل.
     *
     * @return array<int, class-string>
     */
    abstract public function toolClasses(): array;

    /**
     * الأدوار اللي ليها حق التبويب ده.
     *
     * @return array<int, ErpRole>
     */
    abstract public function roles(): array;

    public function allows(User $user): bool
    {
        $role = $user->erpRole();

        return $role !== null && in_array($role, $this->roles(), strict: true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key(),
            'label' => $this->label(),
            'description' => $this->description(),
            'starters' => $this->starters(),
        ];
    }
}
