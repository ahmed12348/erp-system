<?php

namespace App\Ai\Agents;

use App\Enums\ErpRole;
use App\Models\User;

/**
 * وكيل — مجال من مجالات النظام.
 *
 * المستخدم بيشوف شات واحد بيسأله أي حاجة؛ التقسيم ده بيحصل وراه.
 * قبل كل إجابة `AgentRouter` بيسأل الموديل الخفيف السؤال ده بيخص
 * أنهي مجال، وبنبعت أدوات المجال ده بس.
 *
 * ليه أصلاً؟ الموديل بياخد وصف كل أداة مع كل جولة، فتمنتاشر أداة في
 * السلّة معناها تعليمات أطول ووقت أطول واحتمال أكبر إنه يختار الأداة
 * الغلط. مجموعة صغيرة مركّزة أسرع وأدق.
 *
 * التعريفات فاضلة في الكود (تعليمات + أدوات) عن قصد: التوجيه بيتغيّر
 * بسطر في `toolClasses()` أو في `instructions()`، من غير ما حد يفتح
 * لوحة إعدادات.
 *
 * **القاعدة الذهبية**: كل رقم بيتحسب في PHP جوه الأداة. الموديل بيشرح
 * ويوصّي بس — عمره ما بيجمع ولا بيقدّر.
 */
abstract class Agent
{
    /** المعرّف اللي الموجّه بيرد بيه وبيتخزّن في الجلسة */
    abstract public function key(): string;

    /** الاسم العربي للمجال */
    abstract public function label(): string;

    /** جملة قصيرة بتوصّف المجال */
    abstract public function description(): string;

    /** تعليمات الوكيل — بتتضاف لقواعد النظام العامة */
    abstract public function instructions(): string;

    /**
     * أهم قاعدة في المجال ده، في سطر أو اتنين.
     *
     * بتتستعمل لما السؤال يلمس أكتر من مجال: تعليمات أربع مجالات
     * كاملة معناها تعليمات بتتبعت مع كل جولة أدوات وبتاكل من ميزانية
     * الوقت. فالسؤال اللي على مجال واحد بياخد `instructions()` كاملة،
     * واللي على أكتر من مجال بياخد `brief()` من كل واحد.
     */
    abstract public function brief(): string;

    /**
     * الأسئلة المقترحة في اللوحة وهي فاضية.
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
     * الأدوار اللي ليها حق المجال ده.
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
