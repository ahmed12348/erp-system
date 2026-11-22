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
use Spatie\Activitylog\Models\Activity;

#[IsReadOnly]
#[IsIdempotent]
#[Title('آخر النشاط')]
#[Description('آخر الحركات اللي اتعملت في النظام: مين أضاف أو عدّل أو حذف إيه وإمتى. ينفع تفلتر بالمستخدم أو نوع الحركة أو الكيان. المدير بيشوف الكل، وباقي الأدوار بيشوفوا نشاطهم هما بس.')]
class RecentActivity extends Tool
{
    use FormatsErpData, ResolvesCurrentUser;

    public function __construct(private readonly ActivityService $activity) {}

    public function handle(Request $request): Response
    {
        $user = $this->currentUser();

        if ($user === null) {
            return Response::error('مش عارف إنت مين، فمقدرش أعرض سجل النشاط.');
        }

        $limit = min((int) ($request->get('limit') ?: 15), 50);

        $rows = $this->activity->query($user, [
            'log' => $request->get('entity'),
            'event' => $request->get('action'),
        ])
            ->when(
                filled($request->get('user')),
                fn ($query) => $query->whereHas(
                    'causer',
                    fn ($q) => $q->where('name', 'like', '%'.$request->get('user').'%')
                )
            )
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            return Response::text('مفيش نشاط مسجّل بالمواصفات دي.');
        }

        $table = $rows->map(function (Activity $activity): array {
            $row = $this->activity->present($activity);

            return [
                $row['at'] ?? '—',
                $row['user'] ?? '—',
                $row['eventLabel'],
                $row['logLabel'],
                $row['subject'] ?? '—',
            ];
        })->all();

        return Response::text(
            $this->table(['التاريخ', 'المستخدم', 'الحركة', 'الكيان', 'الموضوع'], $table)
            .($this->activity->query($user)->count() > $limit
                ? "\n\n(أحدث {$limit} حركة — فيه أكتر من كده في سجل النشاط.)"
                : '')
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'user' => $schema->string()
                ->description('اسم المستخدم أو جزء منه. المدير بس اللي يقدر يسأل عن حد تاني.'),
            'action' => $schema->string()
                ->description('نوع الحركة: created أو updated أو deleted أو confirmed أو eta_sent أو login.'),
            'entity' => $schema->string()
                ->description('الكيان: invoice أو payment أو product أو customer أو supplier أو warehouse أو user أو auth.'),
            'limit' => $schema->integer()
                ->description('أقصى عدد حركات ترجع (الافتراضي 15).')
                ->min(1)
                ->max(50),
        ];
    }
}
