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

    /**
     * بيستقبل فاتورة بيع أو مرتجع — الاتنين بيتبعتوا لنفس المنظومة،
     * الفاتورة كـ«فاتورة» والمرتجع كـ«إشعار خصم».
     */
    public function store(Invoice $sale): RedirectResponse
    {
        $invoice = $this->eta->send($sale);

        $label = EtaService::DOC_LABELS[$this->eta->documentType($invoice)];

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم إرسال '.$label.' '.$invoice->number.' للمنظومة. الرقم المرجعي: '.$invoice->eta_uuid,
        ]);

        return back();
    }
}
