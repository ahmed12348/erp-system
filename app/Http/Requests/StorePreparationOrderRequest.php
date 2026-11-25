<?php

namespace App\Http\Requests;

use App\Enums\ErpAbility;
use Illuminate\Foundation\Http\FormRequest;

/**
 * **مفيش أي حقل سعر هنا.**
 *
 * مش سهو — الطلب ده بيروح للمخزن، ولو الفورم قبل سعر هيبقى فيه سعر
 * يتعرض على الورقة بعد كام تعديل.
 */
class StorePreparationOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->allows(ErpAbility::ManagePreparationOrders) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'اختار التاجر.',
            'warehouse_id.required' => 'اختار المخزن.',
            'date.before_or_equal' => 'التاريخ ماينفعش يكون في المستقبل.',
            'items.required' => 'لازم تضيف صنف واحد على الأقل للطلب.',
            'items.min' => 'لازم تضيف صنف واحد على الأقل للطلب.',
            'items.*.quantity.gt' => 'الكمية لازم تكون أكبر من صفر.',
        ];
    }
}
