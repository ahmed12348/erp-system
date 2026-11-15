<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
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
                Rule::unique('warehouses', 'name')->ignore($this->route('warehouse')),
            ],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'اكتب اسم المخزن.',
            'name.unique' => 'في مخزن بالاسم ده بالفعل.',
            'name.max' => 'اسم المخزن طويل أوي — 150 حرف كحد أقصى.',
            'location.max' => 'العنوان طويل أوي — 255 حرف كحد أقصى.',
        ];
    }
}
