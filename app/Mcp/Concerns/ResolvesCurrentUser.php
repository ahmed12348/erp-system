<?php

namespace App\Mcp\Concerns;

use App\Models\User;

/**
 * المستخدم الحالي جوه أداة.
 *
 * نفس الأداة بتشتغل في مكانين بجاردين مختلفين: لوحة «اسأل السيستم»
 * بجلسة الويب، وسيرفر الـ MCP بتوكن Passport. فبنجرّب الاتنين بدل ما
 * نفترض واحد.
 */
trait ResolvesCurrentUser
{
    protected function currentUser(): ?User
    {
        foreach ([null, 'api'] as $guard) {
            $user = auth($guard)->user();

            if ($user instanceof User) {
                return $user;
            }
        }

        return null;
    }
}
