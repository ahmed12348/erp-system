<?php

namespace Tests\Feature\Erp;

use App\Models\Invoice;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * إدارة المستخدمين والأدوار — شاشة المدير.
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->admin();
    }

    public function test_the_screen_lists_the_users_with_their_roles(): void
    {
        $this->accountant();

        $this->actingAs($this->admin)
            ->get('/users')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('users/Index')
                ->has('users', 2)
                ->has('roles', 3)
            );
    }

    public function test_a_user_is_created_with_a_role_and_can_sign_in_straight_away(): void
    {
        $this->actingAs($this->admin)
            ->from('/users')
            ->post('/users', [
                'name' => 'منى عبد الرحمن',
                'email' => 'accountant@erp.test',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'accountant',
            ])
            ->assertRedirect('/users')
            ->assertSessionHasNoErrors();

        $user = User::where('email', 'accountant@erp.test')->sole();

        $this->assertTrue($user->hasRole('accountant'));

        // كلمة المرور اللي المدير كتبها بتشتغل فعلاً
        $this->assertTrue(Auth::attempt([
            'email' => 'accountant@erp.test',
            'password' => 'password',
        ]));

        // ومن غير `email_verified_at` الـ middleware بتاع verified كان هيوقفه
        // على الباب ويحوّله لصفحة تأكيد إيميل مش هتوصله
        $this->assertNotNull($user->email_verified_at);
        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_the_password_must_be_confirmed_with_an_arabic_message(): void
    {
        $this->actingAs($this->admin)
            ->from('/users')
            ->post('/users', [
                'name' => 'مستخدم',
                'email' => 'u@erp.test',
                'password' => 'password',
                'password_confirmation' => 'something-else',
                'role' => 'sales',
            ])
            ->assertSessionHasErrors(['password' => 'تأكيد كلمة المرور مش مطابق.']);
    }

    public function test_the_email_must_be_unique_with_an_arabic_message(): void
    {
        $this->actingAs($this->admin)
            ->from('/users')
            ->post('/users', [
                'name' => 'مستخدم',
                'email' => $this->admin->email,
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'sales',
            ])
            ->assertSessionHasErrors(['email' => 'في مستخدم مسجّل بالإيميل ده بالفعل.']);
    }

    public function test_a_role_can_be_changed(): void
    {
        $user = $this->salesUser();

        $this->actingAs($this->admin)
            ->from('/users')
            ->put("/users/{$user->id}", [
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'accountant',
            ])
            ->assertRedirect('/users')
            ->assertSessionHasNoErrors();

        $user->refresh();
        $user->unsetRelation('roles');

        $this->assertTrue($user->hasRole('accountant'));
        $this->assertFalse($user->hasRole('sales'));
    }

    /** الخانة الفاضية معناها «سيب كلمة المرور زي ما هي» */
    public function test_an_empty_password_on_edit_leaves_the_password_alone(): void
    {
        $user = $this->salesUser();
        $original = $user->password;

        $this->actingAs($this->admin)
            ->from('/users')
            ->put("/users/{$user->id}", [
                'name' => 'اسم جديد',
                'email' => $user->email,
                'password' => '',
                'role' => 'sales',
            ])
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame('اسم جديد', $user->name);
        $this->assertSame($original, $user->password);
    }

    public function test_a_filled_password_on_edit_replaces_it(): void
    {
        $user = $this->salesUser();

        $this->actingAs($this->admin)
            ->from('/users')
            ->put("/users/{$user->id}", [
                'name' => $user->name,
                'email' => $user->email,
                'password' => 'brand-new-password',
                'password_confirmation' => 'brand-new-password',
                'role' => 'sales',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('brand-new-password', $user->fresh()->password));
    }

    /** من غير الحماية دي ممكن النظام يفضل من غير مدير خالص */
    public function test_the_last_admin_cannot_be_demoted(): void
    {
        $this->actingAs($this->admin)
            ->from('/users')
            ->put("/users/{$this->admin->id}", [
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'role' => 'sales',
            ])
            ->assertSessionHasErrors('role');

        $this->assertTrue($this->admin->fresh()->hasRole('admin'));
    }

    public function test_an_admin_can_be_demoted_once_another_admin_exists(): void
    {
        $other = $this->admin();

        $this->actingAs($other)
            ->from('/users')
            ->put("/users/{$this->admin->id}", [
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'role' => 'accountant',
            ])
            ->assertSessionHasNoErrors();

        $admin = $this->admin->fresh();
        $admin->unsetRelation('roles');

        $this->assertTrue($admin->hasRole('accountant'));
    }

    public function test_the_last_admin_cannot_be_deleted(): void
    {
        $this->actingAs($this->admin)
            ->from('/users')
            ->delete("/users/{$this->admin->id}")
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_you_cannot_delete_your_own_account(): void
    {
        $this->admin();

        $this->actingAs($this->admin)
            ->from('/users')
            ->delete("/users/{$this->admin->id}")
            ->assertSessionHasErrors(['user' => 'ما ينفعش تحذف حسابك انت.']);

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_an_unused_user_can_be_deleted(): void
    {
        $user = $this->salesUser();

        $this->actingAs($this->admin)
            ->from('/users')
            ->delete("/users/{$user->id}")
            ->assertRedirect('/users')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /** المستخدم اللي سجّل فواتير ما يتحذفش — الفواتير بتشاور عليه */
    public function test_a_user_who_recorded_invoices_cannot_be_deleted(): void
    {
        $user = $this->accountant();
        $warehouse = Warehouse::create(['name' => 'مخزن']);

        Invoice::create([
            'type' => Invoice::TYPE_SALE,
            'number' => 'INV-1',
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'currency' => 'EGP',
            'exchange_rate' => 1,
            'subtotal' => 100,
            'total' => 100,
            'status' => Invoice::STATUS_CONFIRMED,
        ]);

        $this->actingAs($this->admin)
            ->from('/users')
            ->delete("/users/{$user->id}")
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
