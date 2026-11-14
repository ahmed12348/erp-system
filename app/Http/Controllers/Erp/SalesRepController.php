<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\SalesRep;
use App\Services\SalesRepService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesRepController extends Controller
{
    public function __construct(private readonly SalesRepService $reps) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['from', 'to']);

        return Inertia::render('reps/Index', [
            'reps' => $this->reps->performance($filters),
            'filters' => $filters,
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'المندوبين', 'href' => route('reps.index')],
            ],
        ]);
    }

    public function show(Request $request, SalesRep $rep): Response
    {
        $filters = $request->only(['from', 'to']);

        return Inertia::render('reps/Show', [
            'rep' => $this->reps->summary($rep, $filters),
            'filters' => $filters,
            'customers' => Customer::where('sales_rep_id', $rep->id)
                ->orderBy('name')
                ->get(['id', 'name', 'city', 'credit_limit'])
                ->map(fn (Customer $customer) => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'city' => $customer->city,
                    'balance' => $customer->balance(),
                ]),
            'invoices' => Invoice::where('sales_rep_id', $rep->id)
                ->where('type', Invoice::TYPE_SALE)
                ->with('customer:id,name')
                ->latest('date')
                ->limit(30)
                ->get()
                ->map(fn (Invoice $invoice) => [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'date' => $invoice->date->toDateString(),
                    'customer' => $invoice->customer?->name,
                    'total' => (float) $invoice->total,
                    'remaining' => $invoice->remaining,
                ]),
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'المندوبين', 'href' => route('reps.index')],
                ['title' => $rep->name, 'href' => route('reps.show', $rep)],
            ],
        ]);
    }
}
