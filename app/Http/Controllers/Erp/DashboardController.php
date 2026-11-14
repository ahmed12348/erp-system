<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\DashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(DashboardService $dashboard): Response
    {
        return Inertia::render('Dashboard', [
            // لو مفيش حركة، الشاشة بتعرض خطوات التشغيل بدل الأصفار والرسم الفاضي
            'setup' => $dashboard->setup(),
            'kpis' => $dashboard->kpis(),
            'series' => $dashboard->salesSeries(),
            'topProducts' => $dashboard->topProducts(),
            'topDebtors' => $dashboard->topDebtors()->map(fn ($customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'city' => $customer->city,
                'phone' => $customer->phone,
                'balance' => (float) $customer->balance,
                'creditLimit' => $customer->credit_limit ? (float) $customer->credit_limit : null,
            ]),
            'latestInvoices' => $dashboard->latestInvoices()->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'type' => $invoice->type,
                'date' => $invoice->date->toDateString(),
                'party' => $invoice->customer?->name ?? $invoice->supplier?->name,
                'currency' => $invoice->currency,
                'total' => (float) $invoice->total,
                'remaining' => $invoice->remaining,
                'etaStatus' => $invoice->eta_status,
            ]),
        ]);
    }
}
