<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WhatsAppService;
use App\Support\ArabicText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * ربط رقم واتساب بحساب — بتأكيد بكود.
 *
 * التأكيد مش رفاهية: الرقم ده هو اللي بياخد تنبيهات الشركة وبيقدر
 * يسأل المساعد. رقم مكتوب غلط معناه إن بيانات الشركة بتروح لحد تاني
 * خالص.
 */
class WhatsAppNumberController extends Controller
{
    /** الكود صالح عشر دقايق */
    private const CODE_MINUTES = 10;

    public function __construct(private readonly WhatsAppService $whatsapp) {}

    public function store(Request $request): RedirectResponse
    {
        if (! $this->whatsapp->enabled()) {
            throw ValidationException::withMessages([
                'whatsapp_number' => 'واتساب مش متظبّط على السيرفر.',
            ]);
        }

        $validated = $request->validate([
            'whatsapp_number' => ['required', 'string', 'max:20'],
        ], [
            'whatsapp_number.required' => 'اكتب رقم الواتساب.',
        ]);

        $number = ArabicText::digits($validated['whatsapp_number']);

        if (strlen($number) < 10) {
            throw ValidationException::withMessages([
                'whatsapp_number' => 'الرقم مش مظبوط. اكتبه بالصيغة الدولية — مثال: 201001234567.',
            ]);
        }

        // رقم واحد لحساب واحد — وإلا مين بياخد الرد؟
        $taken = User::where('whatsapp_number', $number)
            ->whereKeyNot($request->user()->id)
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'whatsapp_number' => 'الرقم ده مربوط بحساب تاني.',
            ]);
        }

        $code = (string) random_int(100000, 999999);

        $request->user()->forceFill([
            'whatsapp_number' => $number,
            // الربط بيتلغى لحد ما الكود يتأكّد
            'whatsapp_verified_at' => null,
            'whatsapp_code' => $code,
            'whatsapp_code_expires_at' => now()->addMinutes(self::CODE_MINUTES),
        ])->save();

        $sent = $this->whatsapp->sendVerificationCode($request->user(), $number, $code);

        Inertia::flash('toast', $sent
            ? ['type' => 'success', 'message' => 'بعتنا كود على الواتساب. اكتبه هنا خلال '.self::CODE_MINUTES.' دقايق.']
            : ['type' => 'error', 'message' => 'مقدرتش أبعت الكود. اتأكد إن الرقم شغّال على واتساب.']);

        return back();
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10'],
        ], [
            'code.required' => 'اكتب الكود اللي وصلك.',
        ]);

        $user = $request->user();
        $code = ArabicText::digits($validated['code']);

        if (
            blank($user->whatsapp_code)
            || $user->whatsapp_code !== $code
            || $user->whatsapp_code_expires_at?->isPast()
        ) {
            throw ValidationException::withMessages([
                'code' => 'الكود غلط أو انتهت صلاحيته. اطلب كود جديد.',
            ]);
        }

        $user->forceFill([
            'whatsapp_verified_at' => now(),
            'whatsapp_code' => null,
            'whatsapp_code_expires_at' => null,
        ])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'اتأكّد الرقم. دلوقتي تقدر تسأل السيستم من على واتساب.',
        ]);

        return back();
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->forceFill([
            'whatsapp_number' => null,
            'whatsapp_verified_at' => null,
            'whatsapp_code' => null,
            'whatsapp_code_expires_at' => null,
        ])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'اتشال الرقم. مش هتوصلك تنبيهات واتساب تاني.',
        ]);

        return back();
    }
}
