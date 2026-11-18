<?php

namespace App\Http\Requests;

use App\Enums\ErpAbility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * إضافة صنف سريعة من جوه فورم الفاتورة.
 *
 * الحقول هنا أقل من فورم الأصناف الكامل عن قصد: المستخدم في نص كتابة
 * فاتورة ومش هيقف يملا كل البيانات. الباقي بياخد قيمه الافتراضية
 * والصنف يتكمّل بعدين من صفحة الأصناف.
 */
class QuickStoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->allows(ErpAbility::QuickAddProduct) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'sku' => ['required', 'string', 'max:60', Rule::unique('products', 'sku')],
            'unit' => ['required', 'string', 'max:30'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'اكتب اسم الصنف.',
            'sku.required' => 'اكتب كود الصنف.',
            'sku.unique' => 'الكود ده متسجّل على صنف تاني — اختار كود غيره.',
            'unit.required' => 'اختار وحدة الصنف.',
            'sale_price.required' => 'اكتب سعر البيع.',
            'sale_price.min' => 'سعر البيع ما ينفعش يكون بالسالب.',
        ];
    }
}
