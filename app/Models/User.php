<?php

namespace App\Models;

use App\Concerns\HasErpRole;
use App\Concerns\HasTeams;
use App\Concerns\RecordsActivity;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\CausesActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int|null $current_team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team|null $currentTeam
 * @property-read Collection<int, Team> $ownedTeams
 * @property-read Collection<int, Membership> $teamMemberships
 * @property-read Collection<int, Team> $teams
 */
#[Fillable(['name', 'email', 'password', 'current_team_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, OAuthenticatable, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use CausesActivity, HasApiTokens, HasErpRole, HasFactory, HasRoles, HasTeams, Notifiable, PasskeyAuthenticatable, RecordsActivity, TwoFactorAuthenticatable {
        // فرق التطبيق (team_members) لها الأولوية على خاصية الفرق في spatie/permission المعطّلة
        HasTeams::teams insteadof HasRoles;
    }

    /** اسم السجل في سجل النشاط */
    protected $activityLog = 'user';

    /*
     * الاسم والإيميل بس — الباسورد والتوكنات عمرها ما تدخل السجل،
     * و`last_login_at` بيتغيّر كل دخول فتسجيله كتعديل بيغرق السجل.
     */
    protected $activityFields = ['name', 'email'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'whatsapp_verified_at' => 'datetime',
            'whatsapp_code_expires_at' => 'datetime',
        ];
    }

    /** @return HasOne<NotificationSetting, $this> */
    public function notificationSetting(): HasOne
    {
        return $this->hasOne(NotificationSetting::class);
    }

    /** @return HasMany<PushSubscription, $this> */
    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    /**
     * رقم الواتساب للإرسال — المتحقّق منه بس.
     *
     * رقم مكتوب ومتأكّدش منه ممكن يكون غلط ويبعت بيانات الشركة لحد
     * تاني خالص، فالتحقق شرط مش رفاهية.
     */
    public function routeNotificationForWhatsApp(): ?string
    {
        return $this->whatsapp_verified_at !== null
            ? $this->whatsapp_number
            : null;
    }

    /**
     * الفواتير اللي المستخدم سجّلها — بتمنع حذفه وهي موجودة.
     *
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * السندات اللي المستخدم سجّلها.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
