<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\FormatsErpData;
use App\Mcp\Concerns\ResolvesCurrentUser;
use App\Services\ActivityService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[IsIdempotent]
#[Title('آخر دخول')]
#[Description('آخر مرة دخل فيها كل مستخدم على النظام، ومن أنهي IP. المدير بيشوف الكل، وباقي الأدوار بيشوفوا دخولهم هما بس.')]
class LastLogin extends Tool
{
    use FormatsErpData, ResolvesCurrentUser;

    public function __construct(private readonly ActivityService $activity) {}

    public function handle(Request $request): Response
    {
        $user = $this->currentUser();

        if ($user === null) {
            return Response::error('مش عارف إنت مين، فمقدرش أعرض بيانات الدخول.');
        }

        $rows = $this->activity->lastLogins($user, $request->get('user'));

        if ($rows === []) {
            return Response::text('مفيش مستخدم بالاسم ده.');
        }

        $table = array_map(fn (array $row): array => [
            $row['user'],
            $row['at'] ?? 'عمره ما دخل',
            $row['ip'] ?? '—',
        ], $rows);

        return Response::text($this->table(['المستخدم', 'آخر دخول', 'IP'], $table));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'user' => $schema->string()
                ->description('اسم المستخدم أو جزء منه. سيبه فاضي عشان تشوف الكل.'),
        ];
    }
}
