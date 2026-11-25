<?php

namespace App\Http\Requests;

use App\Services\AttachmentService;
use Illuminate\Foundation\Http\FormRequest;

class StoreSaleInvoiceRequest extends FormRequest
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
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'due_date' => ['nullable', 'date', 'after_or_equal:date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],

            // طلب التحضير اللي الفاتورة دي بتنفّذه — بيتعلّم «اتحوّل» وقت الحفظ
            'preparation_order_id' => ['nullable', 'integer', 'exists:preparation_orders,id'],

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
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
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
            'items.*.product_id.required' => 'اختر الصنف في كل سطر.',
            'date.before_or_equal' => 'ماينفعش تعمل فاتورة بتاريخ في المستقبل.',
            'due_date.after_or_equal' => 'تاريخ الاستحقاق لازم يكون بعد تاريخ الفاتورة أو نفسه.',
            'attachment.mimes' => 'المرفق لازم يكون صورة (JPG أو PNG أو WEBP) أو PDF.',
            'attachment.max' => 'المرفق لازم يكون أقل من 8 ميجا.',
        ];
    }
}
