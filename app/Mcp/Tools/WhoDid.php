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
#[Title('مين عمل كده')]
#[Description('تاريخ حركة واحدة بعينها: مين أضاف أو عدّل أو أكّد فاتورة أو صنف أو سند. اكتب رقم الفاتورة أو كود الصنف أو الاسم. المدير بيشوف الكل، وباقي الأدوار بيشوفوا اللي هما عملوه بس.')]
class WhoDid extends Tool
{
    use FormatsErpData, ResolvesCurrentUser;

    public function __construct(private readonly ActivityService $activity) {}

    public function handle(Request $request): Response
    {
        $user = $this->currentUser();

        if ($user === null) {
            return Response::error('مش عارف إنت مين، فمقدرش أعرض سجل النشاط.');
        }

        $needle = trim((string) $request->get('reference'));

        if ($needle === '') {
            return Response::error('اكتب رقم الفاتورة أو كود الصنف اللي عايز تعرف تاريخه.');
        }

        $rows = $this->activity->forSubject($user, $needle, 20);

        if ($rows === []) {
            return Response::text("مفيش نشاط مسجّل على «{$needle}».");
        }

        $table = array_map(fn (array $row): array => [
            $row['at'] ?? '—',
            $row['user'] ?? '—',
            $row['eventLabel'],
            $this->summarise($row),
        ], $rows);

        return Response::text($this->table(['التاريخ', 'المستخدم', 'الحركة', 'التفاصيل'], $table));
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function summarise(array $row): string
    {
        $changes = $row['changes'] ?? [];

        if ($changes === []) {
            return $row['subject'] ?? '—';
        }

        // أول تلات حقول اتغيّروا — الباقي بيتشاف من الشاشة
        return collect($changes)
            ->take(3)
            ->map(fn (array $change): string => "{$change['field']}: {$change['from']} ← {$change['to']}")
            ->implode('، ');
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'reference' => $schema->string()
                ->description('رقم الفاتورة أو كود الصنف أو الاسم — زي S-2026-00001 أو HA-9001.')
                ->required(),
        ];
    }
}
