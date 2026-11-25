<?php

namespace App\Http\Controllers\Erp;

use App\Enums\ErpAbility;
use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * بيانات الشركة — الترويسة اللي بتطلع على كل مستند.
 *
 * للمدير بس: الاسم والرقم الضريبي على الفاتورة التزام قانوني، مش
 * تفضيل شكلي.
 */
class CompanyProfileController extends Controller
{
    public function edit(): Response
    {
        Gate::authorize(ErpAbility::ManageUsers->value);

        $profile = CompanyProfile::current();

        return Inertia::render('settings/Company', [
            'profile' => [
                ...$profile->present(),
                'id' => $profile->id,
            ],
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'بيانات الشركة', 'href' => route('company.edit')],
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        Gate::authorize(ErpAbility::ManageUsers->value);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phones' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'commercial_registry' => ['nullable', 'string', 'max:50'],
            'footer_note' => ['nullable', 'string', 'max:500'],
            /*
             * اللوجو بيتحط على القرص العام مش الخاص: mPDF محتاج يقراه
             * من مسار محلي وقت الرسم، والمتصفح محتاج رابط — والقرص
             * الخاص ما بيدّيش رابط من غير مسار محمي.
             */
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'اسم الشركة مطلوب — بيطلع على كل مستند.',
            'logo.image' => 'اللوجو لازم يكون صورة (PNG أو JPG أو WEBP).',
            'logo.max' => 'اللوجو لازم يكون أقل من 2 ميجا.',
        ]);

        $profile = CompanyProfile::current();

        if ($request->boolean('remove_logo') && $profile->logo_path) {
            Storage::disk('public')->delete($profile->logo_path);
            $profile->logo_path = null;
        }

        if ($request->hasFile('logo')) {
            // اللوجو القديم بيتمسح — مفيش داعي يفضل على القرص
            if ($profile->logo_path) {
                Storage::disk('public')->delete($profile->logo_path);
            }

            $profile->logo_path = $request->file('logo')->store('company', 'public');
        }

        $profile->fill(collect($data)->except(['logo', 'remove_logo'])->all())->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم حفظ بيانات الشركة — هتظهر على كل المستندات.',
        ]);

        return back();
    }
}
