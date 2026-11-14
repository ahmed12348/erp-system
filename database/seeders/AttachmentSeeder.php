<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * مرفقات تجريبية على الفواتير والسندات.
 *
 * مفيش ext-gd على السيرفر ده، فبنولّد الملفات بإيدنا: PNG بكاتب صغير
 * (IHDR/IDAT/IEND + CRC) و PDF بصيغته النصية البسيطة. الناتج ملفات صحيحة
 * المتصفح بيفتحها عادي — صفحة بيضا فيها سطور رمادية شبه مستند مصوّر.
 */
class AttachmentSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'admin@erp.test')->first() ?? User::first();

        if (! $user) {
            return;
        }

        // بنبدأ من نضيف عشان إعادة التشغيل ما تكرّرش المرفقات
        Attachment::query()->delete();
        Storage::disk('local')->deleteDirectory('attachments');

        // فواتير مشتريات: فاتورة المورد + بوليصة الشحن
        Invoice::query()->where('type', Invoice::TYPE_PURCHASE)
            ->latest('date')->take(4)->get()
            ->each(function (Invoice $invoice) use ($user) {
                $this->attach($invoice, $user, 'supplier-invoice-'.$invoice->number.'.pdf',
                    $this->pdf('SUPPLIER INVOICE', $invoice->number), 'فاتورة المورد الأصلية');

                $this->attach($invoice, $user, 'bill-of-lading-'.$invoice->number.'.png',
                    $this->png(), 'بوليصة الشحن');
            });

        // فواتير بيع: صورة إذن التسليم موقّعة
        Invoice::query()->where('type', Invoice::TYPE_SALE)
            ->latest('date')->take(5)->get()
            ->each(function (Invoice $invoice) use ($user) {
                $this->attach($invoice, $user, 'delivery-note-'.$invoice->number.'.png',
                    $this->png(), 'إذن تسليم موقّع من التاجر');
            });

        // سندات: إيصال التحويل البنكي
        Payment::query()->where('method', '!=', 'cash')
            ->latest('date')->take(6)->get()
            ->each(function (Payment $payment) use ($user) {
                $this->attach($payment, $user, 'transfer-receipt-'.$payment->id.'.pdf',
                    $this->pdf('BANK TRANSFER RECEIPT', '#'.$payment->id),
                    $payment->method === 'check' ? 'صورة الشيك' : 'إيصال التحويل البنكي');
            });
    }

    private function attach(mixed $model, User $user, string $name, string $contents, string $notes): void
    {
        $directory = 'attachments/'.Str::of(class_basename($model))->lower().'/'.$model->getKey();
        $path = $directory.'/'.Str::random(40).'.'.Str::afterLast($name, '.');

        Storage::disk('local')->put($path, $contents);

        Attachment::create([
            'attachable_type' => $model->getMorphClass(),
            'attachable_id' => $model->getKey(),
            'disk' => 'local',
            'path' => $path,
            'original_name' => $name,
            'mime_type' => str_ends_with($name, '.pdf') ? 'application/pdf' : 'image/png',
            'size' => strlen($contents),
            'user_id' => $user->id,
            'notes' => $notes,
        ]);
    }

    /** PDF بصفحة واحدة فيها سطرين بخط Helvetica */
    private function pdf(string $title, string $reference): string
    {
        $text = "BT /F1 20 Tf 60 760 Td ({$title}) Tj ET\n"
            ."BT /F1 12 Tf 60 730 Td (Reference: {$reference}) Tj ET\n"
            ."BT /F1 10 Tf 60 700 Td (Scanned document - demo data) Tj ET\n"
            ."0.5 w 60 690 m 480 690 l S\n";

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] '
                .'/Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            '<< /Length '.strlen($text)." >>\nstream\n".$text.'endstream',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\n"
            ."startxref\n".$xref."\n%%EOF";
    }

    /**
     * PNG بيشبه ورقة مصوّرة: هيدر لونه أخضر مزرق وسطور رمادية بعرض عشوائي.
     */
    private function png(int $width = 420, int $height = 560): string
    {
        $raw = '';

        for ($y = 0; $y < $height; $y++) {
            $raw .= chr(0); // filter: none

            for ($x = 0; $x < $width; $x++) {
                $raw .= $this->pixel($x, $y, $width, $height);
            }
        }

        return "\x89PNG\r\n\x1a\n"
            .$this->chunk('IHDR', pack('N2', $width, $height).pack('C5', 8, 2, 0, 0, 0))
            .$this->chunk('IDAT', gzcompress($raw, 9))
            .$this->chunk('IEND', '');
    }

    /** @return string ثلاث بايتات RGB */
    private function pixel(int $x, int $y, int $width, int $height): string
    {
        $margin = 30;

        // إطار رمادي فاتح حوالين الورقة
        if ($x < $margin || $x > $width - $margin || $y < 20 || $y > $height - 20) {
            return pack('C3', 240, 241, 243);
        }

        // شريط العنوان
        if ($y >= 40 && $y < 78) {
            return pack('C3', 0, 142, 140);
        }

        // سطور "نص" رمادية كل 26 بكسل بأطوال مختلفة
        if ($y > 110 && (($y - 110) % 26) < 9) {
            $line = intdiv($y - 110, 26);
            $length = (int) (($width - 2 * $margin) * (0.45 + (($line * 37) % 55) / 100));

            if ($x < $margin + $length) {
                return pack('C3', 196, 199, 204);
            }
        }

        return pack('C3', 255, 255, 255);
    }

    private function chunk(string $type, string $data): string
    {
        return pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
    }
}
