<?php

namespace App\Http\Controllers\Ai;

use App\Ai\AgentRegistry;
use App\Ai\Agents\Agent;
use App\Exceptions\AiUnavailableException;
use App\Http\Controllers\Controller;
use App\Services\AiService;
use App\Services\AskSystemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * «اسأل السيستم» — لوحة المحادثة في الشريط العلوي.
 *
 * اللوحة فيها تبويبات (وكلاء)، كل واحد بأدواته وتاريخه. التبويبات
 * اللي بترجع هنا هي المسموحة لدور المستخدم بس — مش بنعرض تبويب
 * ونمنعه بعدين.
 *
 * الردود JSON عادي مش Inertia، عشان اللوحة تتحدّث والمستخدم فاضل
 * مكانه على الشاشة اللي هو فيها.
 */
class AskController extends Controller
{
    public function __construct(
        private readonly AskSystemService $chat,
        private readonly AiService $ai,
        private readonly AgentRegistry $agents,
    ) {}

    /** التبويبات المتاحة ومحادثة التبويب المطلوب */
    public function index(Request $request): JsonResponse
    {
        $agent = $this->agent($request);

        return response()->json([
            'agents' => array_map(
                fn (Agent $available): array => $available->toArray(),
                $this->agents->for($request->user()),
            ),
            'agent' => $agent?->key(),
            'messages' => $agent ? $this->chat->history($agent) : [],
            'enabled' => $this->ai->isEnabled(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'agent' => ['nullable', 'string', 'max:50'],
        ], [
            'message.required' => 'اكتب سؤالك الأول.',
            'message.max' => 'السؤال طويل أوي — اختصره شوية.',
        ]);

        $agent = $this->agent($request);

        if ($agent === null) {
            return response()->json([
                'message' => 'مفيش تبويب متاح لدورك في النظام.',
                'reason' => 'forbidden',
            ], 403);
        }

        try {
            $answer = $this->chat->ask($agent, $validated['message']);
        } catch (AiUnavailableException $e) {
            return response()->json([
                'message' => $e->arabicMessage,
                'reason' => $e->reason,
            ], 503);
        }

        return response()->json([
            'reply' => $answer,
            'agent' => $agent->key(),
        ]);
    }

    /** مسح محادثة تبويب واحد */
    public function destroy(Request $request): JsonResponse
    {
        $agent = $this->agent($request);

        if ($agent !== null) {
            $this->chat->clear($agent);
        }

        return response()->json(['messages' => []]);
    }

    /**
     * التبويب المطلوب — أو أول تبويب متاح لو المفتاح مش مسموح.
     */
    private function agent(Request $request): ?Agent
    {
        return $this->agents->resolve(
            $request->user(),
            $request->string('agent')->toString() ?: null,
        );
    }
}
