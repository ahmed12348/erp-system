<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\EtaService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class EtaController extends Controller
{
    public function __construct(private readonly EtaService $eta) {}

    public function store(Invoice $sale): RedirectResponse
    {
        $invoice = $this->eta->send($sale);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم إرسال الفاتورة '.$invoice->number.' للمنظومة. الرقم المرجعي: '.$invoice->eta_uuid,
        ]);

        return back();
    }
}
