<?php

namespace App\Http\Requests;

use App\Enums\ErpAbility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalesRepRequest extends FormRequest
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
                Rule::unique('sales_reps', 'name')->ignore($this->route('rep')),
            ],
            'region' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
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
            'name.required' => 'اكتب اسم المندوب.',
            'name.unique' => 'في مندوب بالاسم ده بالفعل.',
            'name.max' => 'اسم المندوب طويل أوي — 150 حرف كحد أقصى.',
            'region.max' => 'اسم المنطقة طويل أوي — 150 حرف كحد أقصى.',
            'phone.max' => 'رقم التليفون طويل أوي — 50 حرف كحد أقصى.',
            'commission_rate.required' => 'اكتب نسبة العمولة (صفر لو مفيش عمولة).',
            'commission_rate.numeric' => 'نسبة العمولة لازم تكون رقم.',
            'commission_rate.min' => 'نسبة العمولة ما تنفعش تكون بالسالب.',
            'commission_rate.max' => 'نسبة العمولة ما تزيدش عن 100%.',
            'notes.max' => 'الملاحظات طويلة أوي — 1000 حرف كحد أقصى.',
        ];
    }
}
