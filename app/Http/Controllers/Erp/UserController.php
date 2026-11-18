<?php

namespace App\Http\Controllers\Erp;

use App\Enums\ErpAbility;
use App\Enums\ErpRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * شاشة المستخدمين والأدوار — للمدير لوحده.
 */
class UserController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function index(): Response
    {
        Gate::authorize(ErpAbility::ManageUsers->value);

        return Inertia::render('users/Index', [
            'users' => $this->users->rows(),
            'roles' => ErpRole::options(),
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'المستخدمين', 'href' => route('users.index')],
            ],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = $this->users->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم إضافة المستخدم «'.$user->name.'».',
        ]);

        return back();
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->users->update($user, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم تعديل بيانات المستخدم.',
        ]);

        return back();
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize(ErpAbility::ManageUsers->value);

        $this->users->delete($user, request()->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم حذف المستخدم.',
        ]);

        return back();
    }
}
