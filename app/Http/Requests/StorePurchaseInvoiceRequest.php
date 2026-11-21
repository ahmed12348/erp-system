<?php

namespace App\Http\Requests;

use App\Services\AttachmentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseInvoiceRequest extends FormRequest
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
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'due_date' => ['nullable', 'date', 'after_or_equal:date'],
            'currency' => ['required', Rule::in(['EGP', 'USD', 'EUR', 'TRY'])],
            'exchange_rate' => ['required', 'numeric', 'gt:0'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'customs_cost' => ['nullable', 'numeric', 'min:0'],
            'clearance_cost' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],

            // صورة الفاتورة الممسوحة — بتتعلّق على الفاتورة بعد الحفظ
            'attachment' => [
                'nullable',
                'file',
                'mimes:'.implode(',', AttachmentService::ALLOWED_MIMES),
                'max:'.AttachmentService::MAX_SIZE_KB,
            ],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'لازم تضيف صنف واحد على الأقل للفاتورة.',
            'items.min' => 'لازم تضيف صنف واحد على الأقل للفاتورة.',
            'items.*.quantity.gt' => 'الكمية لازم تكون أكبر من صفر.',
            'exchange_rate.gt' => 'سعر الصرف لازم يكون أكبر من صفر.',
            'currency.in' => 'العملة لازم تكون جنيه مصري أو دولار أو يورو أو ليرة تركية.',
            'date.before_or_equal' => 'ماينفعش تعمل فاتورة بتاريخ في المستقبل.',
            'attachment.mimes' => 'المرفق لازم يكون صورة (JPG أو PNG أو WEBP) أو PDF.',
            'attachment.max' => 'المرفق لازم يكون أقل من 8 ميجا.',
        ];
    }
}
