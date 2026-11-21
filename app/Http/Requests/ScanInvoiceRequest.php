<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use App\Services\AttachmentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScanInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * نفس قيود المرفقات بالظبط — الملف ده هو اللي هيتعلّق على الفاتورة
     * بعد الحفظ، فما ينفعش نقبل هنا حاجة المرفقات هترفضها بعدين.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:'.implode(',', AttachmentService::ALLOWED_MIMES),
                'max:'.AttachmentService::MAX_SIZE_KB,
            ],
            'type' => ['required', Rule::in([Invoice::TYPE_PURCHASE, Invoice::TYPE_SALE])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'اختار صورة الفاتورة الأول.',
            'file.mimes' => 'الملف لازم يكون صورة (JPG أو PNG أو WEBP) أو PDF.',
            'file.max' => 'الملف لازم يكون أقل من 8 ميجا.',
        ];
    }
}
