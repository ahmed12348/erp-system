<?php

namespace App\Http\Controllers\Erp;

use App\Enums\ErpAbility;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\CustomerService;
use App\Services\SalesRepService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerService $customers,
        private readonly SalesRepService $reps,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'city', 'status']);

        return Inertia::render('customers/Index', [
            'customers' => $this->customers->paginate($filters),
            'governorates' => $this->customers->governorates(),
            'reps' => $this->reps->options(),
            'hasAny' => Customer::query()->exists(),
            'filters' => $filters,
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'التجار', 'href' => route('customers.index')],
            ],
        ]);
    }

    public function show(Request $request, Customer $customer): Response
    {
        $from = $request->query('from');
        $to = $request->query('to');

        return Inertia::render('customers/Show', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'city' => $customer->city,
                'address' => $customer->address,
                'priceTier' => $customer->price_tier,
                'openingBalance' => (float) $customer->opening_balance,
                'notes' => $customer->notes,
            ],
            'summary' => $this->customers->summary($customer),
            'statement' => $this->customers->statement($customer, $from, $to),
            'invoices' => $customer->invoices()
                ->whereIn('type', [Invoice::TYPE_SALE, Invoice::TYPE_SALE_RETURN])
                ->latest('date')
                ->latest('id')
                ->limit(50)
                ->get()
                ->map(fn (Invoice $invoice) => [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'type' => $invoice->type,
                    'date' => $invoice->date->toDateString(),
                    'total' => (float) $invoice->total,
                    'paid' => (float) $invoice->paid,
                    'remaining' => $invoice->remaining,
                    'etaStatus' => $invoice->eta_status,
                ]),
            'openInvoices' => $customer->invoices()
                ->where('type', Invoice::TYPE_SALE)
                ->where('status', Invoice::STATUS_CONFIRMED)
                ->whereRaw('total > paid')
                ->latest('date')
                ->get()
                ->map(fn (Invoice $invoice) => [
                    'id' => $invoice->id,
                    'label' => $invoice->number.' — متبقي '.number_format($invoice->remaining, 2),
                ]),
            'filters' => ['from' => $from, 'to' => $to],
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'التجار', 'href' => route('customers.index')],
                ['title' => $customer->name, 'href' => route('customers.show', $customer)],
            ],
        ]);
    }

    /** كشف حساب مهيّأ للطباعة / الحفظ PDF من المتصفح */
    public function statement(Request $request, Customer $customer): Response
    {
        $from = $request->query('from');
        $to = $request->query('to');

        return Inertia::render('print/CustomerStatement', [
            'company' => config('app.name'),
            'customer' => [
                'name' => $customer->name,
                'phone' => $customer->phone,
                'city' => $customer->city,
                'address' => $customer->address,
            ],
            'summary' => $this->customers->summary($customer),
            'statement' => $this->customers->statement($customer, $from, $to),
            'filters' => ['from' => $from, 'to' => $to],
            'printedAt' => now()->toDateString(),
        ]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $customer = $this->customers->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم إضافة تاجر «'.$customer->name.'».',
        ]);

        return back();
    }

    public function update(StoreCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->customers->update($customer, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم تعديل بيانات التاجر.',
        ]);

        return back();
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        Gate::authorize(ErpAbility::ManageMasterData->value);

        $this->customers->delete($customer);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم حذف التاجر.',
        ]);

        return back();
    }
}
