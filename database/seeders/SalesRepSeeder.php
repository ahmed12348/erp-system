<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\SalesRep;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ٤ مندوبين موزّعين على مناطق مصر، وكل تاجر بيتربط بمندوب منطقته.
 * الفواتير والسندات القديمة بتاخد مندوب التاجر بأثر رجعي عشان شاشة
 * المندوبين يبقى فيها بيانات حقيقية من أول يوم.
 */
class SalesRepSeeder extends Seeder
{
    /** [الاسم, التليفون, المنطقة, نسبة العمولة, المحافظات] */
    private const REPS = [
        ['محمود عبد الله', '01001234567', 'القاهرة الكبرى', 2.50, ['القاهرة', 'الجيزة', 'القليوبية']],
        ['هشام فتحي', '01112345678', 'الدلتا', 2.00, ['الدقهلية', 'الشرقية', 'الغربية', 'المنوفية', 'كفر الشيخ', 'دمياط']],
        ['سامح رمضان', '01223456789', 'الإسكندرية والساحل', 2.25, ['الإسكندرية', 'البحيرة', 'مطروح', 'بورسعيد', 'الإسماعيلية', 'السويس', 'شمال سيناء']],
        ['عمرو شعبان', '01534567890', 'الصعيد', 3.00, ['أسيوط', 'سوهاج', 'قنا', 'المنيا', 'بني سويف', 'الفيوم', 'أسوان', 'الأقصر', 'البحر الأحمر']],
    ];

    public function run(): void
    {
        $byCity = [];

        foreach (self::REPS as [$name, $phone, $region, $rate, $cities]) {
            $rep = SalesRep::create([
                'name' => $name,
                'phone' => $phone,
                'region' => $region,
                'commission_rate' => $rate,
                'is_active' => true,
            ]);

            foreach ($cities as $city) {
                $byCity[$city] = $rep->id;
            }
        }

        $fallback = SalesRep::first()->id;

        // ربط التجار بمندوب منطقتهم
        foreach (Customer::all() as $customer) {
            $customer->forceFill([
                'sales_rep_id' => $byCity[$customer->city] ?? $fallback,
            ])->saveQuietly();
        }

        // الفواتير والسندات بتاخد مندوب التاجر
        DB::table('invoices')
            ->whereNotNull('customer_id')
            ->orderBy('id')
            ->chunkById(500, function ($invoices) {
                foreach ($invoices as $invoice) {
                    DB::table('invoices')->where('id', $invoice->id)->update([
                        'sales_rep_id' => DB::table('customers')
                            ->where('id', $invoice->customer_id)
                            ->value('sales_rep_id'),
                    ]);
                }
            });

        DB::table('payments')
            ->where('type', Payment::TYPE_RECEIPT)
            ->whereNotNull('customer_id')
            ->orderBy('id')
            ->chunkById(500, function ($payments) {
                foreach ($payments as $payment) {
                    DB::table('payments')->where('id', $payment->id)->update([
                        'sales_rep_id' => DB::table('customers')
                            ->where('id', $payment->customer_id)
                            ->value('sales_rep_id'),
                    ]);
                }
            });

    }
}
