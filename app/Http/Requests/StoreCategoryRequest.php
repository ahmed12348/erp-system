<?php

namespace App\Http\Requests;

use App\Enums\ErpAbility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
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
                Rule::unique('categories', 'name')->ignore($this->route('category')),
            ],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'اكتب اسم التصنيف.',
            'name.unique' => 'في تصنيف بالاسم ده بالفعل.',
            'name.max' => 'اسم التصنيف طويل أوي — 150 حرف كحد أقصى.',
        ];
    }
}
