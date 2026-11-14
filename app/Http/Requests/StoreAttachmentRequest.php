<?php

namespace App\Http\Requests;

use App\Services\AttachmentService;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
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
            'file' => [
                'required',
                'file',
                'mimes:'.implode(',', AttachmentService::ALLOWED_MIMES),
                'max:'.AttachmentService::MAX_SIZE_KB,
            ],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'اختر ملف الأول.',
            'file.mimes' => 'الملف لازم يكون صورة (JPG أو PNG أو WEBP) أو ملف PDF.',
            'file.max' => 'حجم الملف ماينفعش يزيد عن 8 ميجابايت.',
        ];
    }
}
