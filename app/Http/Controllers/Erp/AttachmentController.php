<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttachmentRequest;
use App\Models\Attachment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\AttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function __construct(private readonly AttachmentService $attachments) {}

    public function storeForInvoice(StoreAttachmentRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->attachments->store(
            $invoice,
            $request->file('file'),
            $request->user(),
            $request->input('notes'),
        );

        return $this->done();
    }

    public function storeForPayment(StoreAttachmentRequest $request, Payment $payment): RedirectResponse
    {
        $this->attachments->store(
            $payment,
            $request->file('file'),
            $request->user(),
            $request->input('notes'),
        );

        return $this->done();
    }

    /** معاينة داخل المتصفح */
    public function show(Attachment $attachment): StreamedResponse
    {
        return Storage::disk($attachment->disk)->response(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type, 'Content-Disposition' => 'inline'],
        );
    }

    /** تحميل الملف */
    public function download(Attachment $attachment): StreamedResponse
    {
        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name,
        );
    }

    public function destroy(Attachment $attachment): RedirectResponse
    {
        $this->attachments->delete($attachment);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم حذف المرفق.']);

        return back();
    }

    private function done(): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم رفع المرفق.']);

        return back();
    }
}
