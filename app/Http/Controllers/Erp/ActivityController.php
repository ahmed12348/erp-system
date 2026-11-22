<?php

namespace App\Http\Controllers\Erp;

use App\Enums\ErpAbility;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityService;
use App\Support\ActivityLabels;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

/**
 * شاشة «سجل النشاط» — للمدير لوحده.
 *
 * بتعرض مين عمل إيه وإمتى، ومن أنهي جهاز في حالة الدخول، ومع التعديلات
 * بتعرض «قبل/بعد» لكل حقل اتغيّر.
 */
class ActivityController extends Controller
{
    public function __construct(private readonly ActivityService $activity) {}

    public function index(Request $request): Response
    {
        Gate::authorize(ErpAbility::ManageUsers->value);

        $filters = $request->only(['user_id', 'log', 'event', 'from', 'to']);

        $rows = $this->activity->paginate($request->user(), $filters);

        return Inertia::render('activity/Index', [
            'rows' => $rows->through(
                fn (Activity $activity) => $this->activity->present($activity)
            ),
            'filters' => $filters,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'logs' => ActivityLabels::logOptions(),
            'events' => ActivityLabels::eventOptions(),
            'retentionMonths' => ActivityService::RETENTION_MONTHS,
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'المستخدمين', 'href' => route('users.index')],
                ['title' => 'سجل النشاط', 'href' => route('activity.index')],
            ],
        ]);
    }
}
