<?php

namespace App\Http\Requests;

use App\Enums\ErpAbility;
use App\Enums\ErpRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', Rule::in(array_column(ErpRole::cases(), 'value'))],
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
            'password.required' => 'اكتب كلمة مرور للمستخدم.',
            'password.confirmed' => 'تأكيد كلمة المرور مش مطابق.',
            'role.required' => 'اختار دور المستخدم.',
            'role.in' => 'الدور ده مش من أدوار النظام.',
        ];
    }
}
