<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Services\TreasuryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TreasuryController extends Controller
{
    public function __construct(private readonly TreasuryService $treasury) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['type', 'method', 'from', 'to']);
        $ledger = $this->treasury->ledger($filters);

        return Inertia::render('treasury/Index', [
            'rows' => $ledger['rows'],
            'totals' => $ledger['totals'],
            'byMethod' => $this->treasury->byMethod($filters),
            'filters' => $filters,
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'الخزينة', 'href' => route('treasury.index')],
            ],
        ]);
    }
}
