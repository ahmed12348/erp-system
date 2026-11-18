<?php

namespace App\Http\Requests;

use App\Enums\ErpAbility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
{
    /** العملات اللي النظام بيتعامل بيها في فواتير الاستيراد */
    public const CURRENCIES = ['EGP', 'USD', 'EUR', 'TRY', 'SAR', 'AED', 'CNY'];

    public function authorize(): bool
    {
        return $this->user()?->allows(ErpAbility::ManageMasterData) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('suppliers', 'name')->ignore($this->route('supplier')),
            ],
            'country' => ['nullable', 'string', 'max:100'],
            'currency' => ['required', 'string', Rule::in(self::CURRENCIES)],
            'phone' => ['nullable', 'string', 'max:50'],
            'opening_balance' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'اكتب اسم المورد.',
            'name.unique' => 'في مورد بالاسم ده بالفعل.',
            'name.max' => 'اسم المورد طويل أوي — 150 حرف كحد أقصى.',
            'country.max' => 'اسم البلد طويل أوي — 100 حرف كحد أقصى.',
            'currency.required' => 'اختار عملة التعامل مع المورد.',
            'currency.in' => 'العملة دي مش من العملات المدعومة.',
            'phone.max' => 'رقم التليفون طويل أوي — 50 حرف كحد أقصى.',
            'opening_balance.numeric' => 'الرصيد الافتتاحي لازم يكون رقم.',
            'notes.max' => 'الملاحظات طويلة أوي — 1000 حرف كحد أقصى.',
        ];
    }
}
