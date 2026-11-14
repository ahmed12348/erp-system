<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(private readonly CustomerService $customers) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'city', 'status']);

        return Inertia::render('customers/Index', [
            'customers' => $this->customers->paginate($filters),
            'governorates' => $this->customers->governorates(),
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
}
