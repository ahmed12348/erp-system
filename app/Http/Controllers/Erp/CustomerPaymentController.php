<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Customer;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class CustomerPaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function store(StorePaymentRequest $request, Customer $customer): RedirectResponse
    {
        $payment = $this->payments->recordReceipt(
            $customer,
            $request->validated(),
            $request->user(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم تسجيل تحصيل بمبلغ '.number_format((float) $payment->amount, 2).' ج.م من '.$customer->name.'.',
        ]);

        return back();
    }
}
