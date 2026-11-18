<?php

namespace App\Services\Concerns;

use Illuminate\Validation\ValidationException;

/**
 * قاعدة واحدة لكل البيانات الأساسية: الحذف مسموح طول ما السجل لسه نضيف.
 *
 * أول ما يبقى عليه فاتورة أو حركة أو ارتباط بسجل تاني، الحذف بيبوّظ
 * الأرصدة والتقارير التاريخية — فبنرفض ونقول للمستخدم يوقّفه بدل ما يحذفه.
 */
trait PreventsDeletingUsedRecords
{
    /**
     * @param  string  $key  مفتاح الخطأ اللي الواجهة بتعرضه
     * @param  string  $label  اسم السجل زي ما المستخدم شايفه
     * @param  array<string, int>  $usages  وصف الارتباط => عدده
     *
     * @throws ValidationException
     */
    protected function guardAgainstDeletion(string $key, string $label, array $usages): void
    {
        $blocking = array_filter($usages, fn (int $count) => $count > 0);

        if ($blocking === []) {
            return;
        }

        $parts = [];

        foreach ($blocking as $what => $count) {
            $parts[] = number_format($count).' '.$what;
        }

        throw ValidationException::withMessages([
            $key => sprintf(
                'مش ممكن تحذف «%s» لأن عليه %s. وقّفه بدل ما تحذفه عشان الأرصدة والتقارير القديمة ما تبوظش.',
                $label,
                implode(' و', $parts),
            ),
        ]);
    }
}
