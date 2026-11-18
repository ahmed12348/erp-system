<?php

namespace App\Http\Middleware;

use App\Services\InventoryService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                // دور المستخدم وصلاحياته — الواجهة بتخفي أزرار الإضافة والتعديل على أساسها
                'role' => $user?->erpRole()?->value,
                'can' => $user?->abilityFlags() ?? [
                    'manageMasterData' => false,
                    'quickAddProduct' => false,
                    'manageUsers' => false,
                ],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            // بيغذّي عدّاد الجرس في الشريط العلوي — lazy عشان ما يتحسبش لو مفيش مستخدم
            'lowStockCount' => fn () => $user ? app(InventoryService::class)->lowStockCount() : 0,
            'today' => now()->toDateString(),
            'currentTeam' => fn () => $user?->currentTeam ? $user->toUserTeam($user->currentTeam) : null,
            'teams' => fn () => $user?->toUserTeams(includeCurrent: true) ?? [],
        ];
    }
}
