<?php

namespace App\Http\Requests;

use App\Services\PaymentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'method' => ['required', Rule::in(PaymentService::METHODS)],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.gt' => 'المبلغ لازم يكون أكبر من صفر.',
            'date.before_or_equal' => 'ماينفعش تسجّل دفعة بتاريخ في المستقبل.',
            'method.in' => 'طريقة الدفع لازم تكون نقدي أو تحويل بنكي أو شيك.',
        ];
    }
}
