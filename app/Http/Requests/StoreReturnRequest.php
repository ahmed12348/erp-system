<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use App\Services\PaymentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReturnRequest extends FormRequest
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
            'date' => ['required', 'date', 'before_or_equal:today'],
            'return_reason' => ['required', Rule::in(array_keys(Invoice::RETURN_REASONS))],
            'notes' => ['nullable', 'string', 'max:1000'],

            // الرد النقدي اختياري — ممكن المرتجع يفضل على الحساب
            'refund' => ['nullable', 'array'],
            'refund.amount' => ['nullable', 'numeric', 'min:0'],
            'refund.method' => ['nullable', Rule::in(PaymentService::METHODS)],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'return_reason.required' => 'اختار سبب المرتجع.',
            'return_reason.in' => 'السبب ده مش من الأسباب المتاحة.',
            'date.before_or_equal' => 'ماينفعش تعمل مرتجع بتاريخ في المستقبل.',
            'items.required' => 'اختار بند واحد على الأقل للمرتجع.',
            'items.min' => 'اختار بند واحد على الأقل للمرتجع.',
        ];
    }
}
