<?php

namespace App\Http\Requests;

use App\Enums\ErpAbility;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
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
                Rule::unique('customers', 'name')->ignore($this->route('customer')),
            ],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'price_tier' => ['required', 'string', Rule::in(Customer::TIERS)],
            'sales_rep_id' => ['nullable', 'integer', Rule::exists('sales_reps', 'id')],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
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
            'name.required' => 'اكتب اسم التاجر.',
            'name.unique' => 'في تاجر بالاسم ده بالفعل.',
            'name.max' => 'اسم التاجر طويل أوي — 150 حرف كحد أقصى.',
            'city.max' => 'اسم المحافظة طويل أوي — 100 حرف كحد أقصى.',
            'phone.max' => 'رقم التليفون طويل أوي — 50 حرف كحد أقصى.',
            'address.max' => 'العنوان طويل أوي — 255 حرف كحد أقصى.',
            'price_tier.required' => 'اختار شريحة السعر: تجزئة ولا جملة الجملة.',
            'price_tier.in' => 'شريحة السعر لازم تكون تجزئة أو جملة الجملة.',
            'sales_rep_id.exists' => 'المندوب اللي اخترته مش موجود.',
            'credit_limit.numeric' => 'حد المديونية لازم يكون رقم.',
            'credit_limit.min' => 'حد المديونية ما ينفعش يكون بالسالب.',
            'opening_balance.numeric' => 'الرصيد الافتتاحي لازم يكون رقم.',
            'notes.max' => 'الملاحظات طويلة أوي — 1000 حرف كحد أقصى.',
        ];
    }
}
