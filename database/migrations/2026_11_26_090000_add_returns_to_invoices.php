<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            /*
             * الفاتورة الأصلية اللي المرتجع راجع عليها.
             *
             * `nullOnDelete` مش `cascade`: المرتجع مستند مالي قايم بذاته —
             * لو الفاتورة الأصلية اتمسحت لأي سبب، المرتجع يفضل موجود
             * ومعاه حركات مخزونه، وإلا الأرصدة تبوظ في صمت.
             */
            $table->foreignId('original_invoice_id')
                ->nullable()
                ->after('number')
                ->constrained('invoices')
                ->nullOnDelete();

            // سبب المرتجع — بضاعة تالفة، غلط في الطلب، إلخ
            $table->string('return_reason')->nullable()->after('notes');

            /*
             * مرتجع من غير فاتورة أصلية.
             *
             * بيحصل: بضاعة راجعة من زمان ومحدش لاقي ورقتها. المدير بس
             * اللي يقدر يعمله، والعلامة دي بتفضل على المستند عشان
             * المراجعة تعرف تفرزه — مرتجع من غير أصل مابينفعش يتطابق مع
             * أي حاجة.
             */
            $table->boolean('is_orphan_return')->default(false)->after('return_reason');

            $table->index('original_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['original_invoice_id']);
            $table->dropColumn(['original_invoice_id', 'return_reason', 'is_orphan_return']);
        });
    }
};
