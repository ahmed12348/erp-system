<?php

namespace App\Http\Controllers\Ai;

use App\Ai\AgentRegistry;
use App\Exceptions\AiUnavailableException;
use App\Http\Controllers\Controller;
use App\Services\AiService;
use App\Services\AskSystemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * «اسأل السيستم» — لوحة المحادثة في الشريط العلوي.
 *
 * شات واحد بيتسأل فيه أي حاجة. توجيه السؤال لمجاله بيحصل في
 * `AgentRouter` وراه — المستخدم مابيختارش تبويب، وما بيشوفش مجالات
 * أصلاً. `domains` في الرد للتشخيص والاختبارات.
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

    /** المحادثة الحالية والأسئلة المقترحة */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'starters' => $this->agents->startersFor($request->user()),
            'messages' => $this->chat->history(),
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

        $user = $request->user();

        // دور من غير أي مجال — مش هياخد أدوات أصلاً، فالأنضف نقولها
        if ($this->agents->for($user) === []) {
            return response()->json([
                'message' => 'مفيش صلاحية لدورك يسأل المساعد.',
                'reason' => 'forbidden',
            ], 403);
        }

        try {
            $answer = $this->chat->ask($user, $validated['message']);
        } catch (AiUnavailableException $e) {
            return response()->json([
                'message' => $e->arabicMessage,
                'reason' => $e->reason,
            ], 503);
        }

        return response()->json($answer);
    }

    /** مسح المحادثة */
    public function destroy(): JsonResponse
    {
        $this->chat->clear();

        return response()->json(['messages' => []]);
    }
}
