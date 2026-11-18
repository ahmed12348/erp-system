<?php

namespace App\Http\Requests;

use App\Enums\ErpAbility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:200'],
            'sku' => [
                'required',
                'string',
                'max:60',
                Rule::unique('products', 'sku')->ignore($this->route('product')),
            ],
            'unit' => ['required', 'string', 'max:30'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'purchase_price' => ['nullable', 'numeric', 'min:0', 'max:99999999999999'],
            'sale_price' => ['required', 'numeric', 'min:0', 'max:99999999999999'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0', 'max:99999999999999'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'اكتب اسم الصنف.',
            'name.max' => 'اسم الصنف طويل أوي — 200 حرف كحد أقصى.',
            'sku.required' => 'اكتب كود الصنف.',
            'sku.unique' => 'الكود ده متسجّل على صنف تاني — اختار كود غيره.',
            'sku.max' => 'الكود طويل أوي — 60 حرف كحد أقصى.',
            'unit.required' => 'اختار وحدة الصنف (قطعة، كرتونة، كيلو…).',
            'category_id.exists' => 'التصنيف اللي اخترته مش موجود.',
            'sale_price.required' => 'اكتب سعر البيع.',
            'sale_price.numeric' => 'سعر البيع لازم يكون رقم.',
            'sale_price.min' => 'سعر البيع ما ينفعش يكون بالسالب.',
            'purchase_price.numeric' => 'تكلفة الشراء لازم تكون رقم.',
            'purchase_price.min' => 'تكلفة الشراء ما تنفعش تكون بالسالب.',
            'wholesale_price.numeric' => 'سعر الجملة لازم يكون رقم.',
            'wholesale_price.min' => 'سعر الجملة ما ينفعش يكون بالسالب.',
            'min_stock.numeric' => 'حد الطلب لازم يكون رقم.',
            'min_stock.min' => 'حد الطلب ما ينفعش يكون بالسالب.',
        ];
    }
}
