<?php

namespace App\Http\Controllers\Ai;

use App\Exceptions\AiUnavailableException;
use App\Http\Controllers\Controller;
use App\Services\AiService;
use App\Services\AskSystemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * «اسأل السيستم» — لوحة المحادثة في الشريط العلوي.
 *
 * الردود JSON عادي مش Inertia، عشان اللوحة بتتحدّث من غيرها ما تعمل
 * زيارة صفحة جديدة والمستخدم فاضل مكانه على الشاشة اللي هو فيها.
 */
class AskController extends Controller
{
    public function __construct(
        private readonly AskSystemService $chat,
        private readonly AiService $ai,
    ) {}

    /** المحادثة المحفوظة في الجلسة — بتتقرا أول ما اللوحة تتفتح */
    public function index(): JsonResponse
    {
        return response()->json([
            'messages' => $this->chat->history(),
            'starters' => config('ai.starters'),
            'enabled' => $this->ai->isEnabled(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ], [
            'message.required' => 'اكتب سؤالك الأول.',
            'message.max' => 'السؤال طويل أوي — اختصره شوية.',
        ]);

        try {
            $answer = $this->chat->ask($validated['message']);
        } catch (AiUnavailableException $e) {
            return response()->json([
                'message' => $e->arabicMessage,
                'reason' => $e->reason,
            ], 503);
        }

        return response()->json(['reply' => $answer]);
    }

    /** مسح المحادثة والبدء من أول وجديد */
    public function destroy(): JsonResponse
    {
        $this->chat->clear();

        return response()->json(['messages' => []]);
    }
}
