<?php

namespace App\Http\Requests;

use App\Enums\ErpAbility;
use App\Enums\ErpRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->allows(ErpAbility::ManageUsers) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $target */
        $target = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target)],
            // كلمة المرور اختيارية في التعديل — فاضية معناها «سيبها زي ما هي»
            'password' => ['nullable', 'string', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', Rule::in(array_column(ErpRole::cases(), 'value'))],
        ];
    }

    /**
     * آخر مدير في النظام ما ينفعش ينزل من دوره — غير كده محدش هيقدر
     * يدير المستخدمين تاني.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                /** @var User $target */
                $target = $this->route('user');

                if ($this->input('role') === ErpRole::Admin->value) {
                    return;
                }

                if (! $target->hasRole(ErpRole::Admin->value)) {
                    return;
                }

                if (User::role(ErpRole::Admin->value)->count() > 1) {
                    return;
                }

                $validator->errors()->add(
                    'role',
                    'ده آخر مدير في النظام — لازم تعيّن مدير تاني الأول قبل ما تغيّر دوره.',
                );
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'اكتب اسم المستخدم.',
            'name.max' => 'الاسم طويل أوي — 150 حرف كحد أقصى.',
            'email.required' => 'اكتب الإيميل.',
            'email.email' => 'الإيميل ده شكله مش مظبوط.',
            'email.unique' => 'في مستخدم مسجّل بالإيميل ده بالفعل.',
            'password.confirmed' => 'تأكيد كلمة المرور مش مطابق.',
            'role.required' => 'اختار دور المستخدم.',
            'role.in' => 'الدور ده مش من أدوار النظام.',
        ];
    }
}
