<?php

namespace App\Http\Controllers\Erp;

use App\Enums\ErpAbility;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierPaymentRequest;
use App\Http\Requests\StoreSupplierRequest;
use App\Models\Invoice;
use App\Models\Supplier;
use App\Services\PaymentService;
use App\Services\SupplierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function __construct(
        private readonly SupplierService $suppliers,
        private readonly PaymentService $payments,
    ) {}

    public function index(): Response
    {
        return Inertia::render('suppliers/Index', [
            'suppliers' => $this->suppliers->listWithBalances(),
            'currencies' => StoreSupplierRequest::CURRENCIES,
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'الموردون', 'href' => route('suppliers.index')],
            ],
        ]);
    }

    public function show(Request $request, Supplier $supplier): Response
    {
        $search = $request->query('search');

        return Inertia::render('suppliers/Show', [
            'supplier' => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'country' => $supplier->country,
                'phone' => $supplier->phone,
                'currency' => $supplier->currency,
                'openingBalance' => (float) $supplier->opening_balance,
                'notes' => $supplier->notes,
            ],
            'summary' => $this->suppliers->summary($supplier),
            'statement' => $this->suppliers->statement($supplier, $search),
            'filters' => ['search' => $search],
            'invoices' => $supplier->invoices()
                ->where('type', Invoice::TYPE_PURCHASE)
                ->latest('date')
                ->get()
                ->map(fn (Invoice $invoice) => [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'date' => $invoice->date->toDateString(),
                    'currency' => $invoice->currency,
                    'exchangeRate' => (float) $invoice->exchange_rate,
                    'total' => (float) $invoice->total,
                    'remaining' => $invoice->remaining,
                ]),
            'openInvoices' => $supplier->invoices()
                ->where('type', Invoice::TYPE_PURCHASE)
                ->whereRaw('total > paid')
                ->latest('date')
                ->get()
                ->map(fn (Invoice $invoice) => [
                    'id' => $invoice->id,
                    'label' => $invoice->number.' — متبقي '.number_format($invoice->remaining, 2).' '.$invoice->currency,
                    'exchangeRate' => (float) $invoice->exchange_rate,
                ]),
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'الموردون', 'href' => route('suppliers.index')],
                ['title' => $supplier->name, 'href' => route('suppliers.show', $supplier)],
            ],
        ]);
    }

    public function storePayment(StoreSupplierPaymentRequest $request, Supplier $supplier): RedirectResponse
    {
        $payment = $this->payments->recordDisbursement(
            $supplier,
            [...$request->validated(), 'currency' => $supplier->currency],
            $request->user(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم تسجيل دفعة '.number_format((float) $payment->amount, 2).' '.$payment->currency.' للمورد '.$supplier->name.'.',
        ]);

        return back();
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $supplier = $this->suppliers->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم إضافة مورد «'.$supplier->name.'».',
        ]);

        return back();
    }

    public function update(StoreSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $this->suppliers->update($supplier, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم تعديل بيانات المورد.',
        ]);

        return back();
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        Gate::authorize(ErpAbility::ManageMasterData->value);

        $this->suppliers->delete($supplier);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم حذف المورد.',
        ]);

        return back();
    }
}
