<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * تصنيف مصروفات — شجرة على مستويين.
 *
 * الأب مجموعة (مصروفات ثابتة / متغيرة) والابن هو اللي بيتسجّل عليه
 * المصروف فعلاً. القسمة دي هي اللي بتخلّي التقرير يجمّع بالمجموعة
 * ويفصّل بالبند في نفس الوقت.
 *
 * **الكود (`code`) مقصود إنه مفتاح لدليل حسابات كامل بعدين**: الأب
 * بياخد 5100 والابن 5110، فلما النظام يمسك قيود يومية التصنيف بيبقى
 * معاه رقم حسابه جاهز بدل مطابقة بالاسم.
 */
class ExpenseCategory extends Model
{
    use RecordsActivity;

    /**
     * اسم السجل في سجل النشاط
     *
     * @var string
     */
    protected $activityLog = 'expense_category';

    /**
     * الحقول اللي بيتسجّل تغيّرها
     *
     * @var array<int, string>
     */
    protected $activityFields = ['name', 'code', 'parent_id', 'is_active'];

    protected $fillable = ['parent_id', 'name', 'code', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return BelongsTo<self, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<self, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    /** @return HasMany<Expense, $this> */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** المجموعات — اللي مالهاش أب */
    public function scopeRoots(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    public function isGroup(): bool
    {
        return $this->parent_id === null;
    }

    /** الاسم الكامل — «مصروفات ثابتة ← إيجار» */
    public function fullName(): string
    {
        return $this->parent
            ? $this->parent->name.' ← '.$this->name
            : $this->name;
    }

    /**
     * الشجرة كاملة بمستوييها.
     *
     * @return Collection<int, self>
     */
    public static function tree(): Collection
    {
        return self::query()
            ->roots()
            ->with(['children' => fn ($query) => $query->withCount('expenses')])
            ->withCount('expenses')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
