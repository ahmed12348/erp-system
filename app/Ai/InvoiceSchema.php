<?php

namespace App\Ai;

use App\Models\Invoice;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

/**
 * شكل الـ JSON اللي الموديل لازم يرجّعه وهو بيقرا صورة فاتورة.
 *
 * Schema واحدة للنوعين — شراء وبيع — والكنترولر بياخد منها اللي يخص
 * الفورم اللي قدامه بس (فاتورة البيع مالهاش عملة ولا سعر صرف ولا
 * مصاريف استيراد، وفاتورة الشراء مالهاش ضريبة).
 *
 * كل الحقول nullable عن قصد: الفاتورة الورقية ساعات مش فيها تاريخ
 * واضح أو كود صنف، ونفضّل الموديل يسيبها فاضية على إنه يخترع قيمة.
 */
class InvoiceSchema
{
    public static function make(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'invoice',
            description: 'بيانات فاتورة مقروءة من صورة أو PDF',
            properties: [
                new StringSchema('party_name', 'اسم المورد أو التاجر زي ما هو مكتوب على الفاتورة', nullable: true),
                new StringSchema('party_code', 'كود المورد أو التاجر لو مكتوب على الفاتورة', nullable: true),
                new StringSchema('party_phone', 'تليفون المورد أو التاجر لو مكتوب', nullable: true),
                new StringSchema('number', 'رقم الفاتورة زي ما هو مكتوب', nullable: true),
                new StringSchema('date', 'تاريخ الفاتورة بصيغة YYYY-MM-DD', nullable: true),
                new StringSchema('currency', 'عملة الفاتورة: EGP أو USD أو EUR أو TRY', nullable: true),
                new NumberSchema('exchange_rate', 'سعر صرف العملة مقابل الجنيه المصري لو مكتوب', nullable: true),
                new NumberSchema('discount', 'الخصم على إجمالي الفاتورة', nullable: true),
                new NumberSchema('shipping_cost', 'مصاريف الشحن', nullable: true),
                new NumberSchema('customs_cost', 'الجمارك', nullable: true),
                new NumberSchema('clearance_cost', 'مصاريف التخليص', nullable: true),
                new StringSchema('notes', 'أي ملاحظات مكتوبة على الفاتورة', nullable: true),
                new ArraySchema(
                    name: 'items',
                    description: 'بنود الفاتورة سطر بسطر',
                    items: new ObjectSchema(
                        name: 'item',
                        description: 'بند واحد',
                        properties: [
                            new StringSchema('code', 'كود الصنف لو مكتوب', nullable: true),
                            new StringSchema('name', 'اسم الصنف زي ما هو مكتوب', nullable: true),
                            new NumberSchema('quantity', 'الكمية', nullable: true),
                            new NumberSchema('unit_price', 'سعر الوحدة قبل الخصم', nullable: true),
                        ],
                        requiredFields: ['code', 'name', 'quantity', 'unit_price'],
                    ),
                ),
            ],
            requiredFields: [
                'party_name', 'party_code', 'party_phone', 'number', 'date',
                'currency', 'exchange_rate', 'discount', 'shipping_cost',
                'customs_cost', 'clearance_cost', 'notes', 'items',
            ],
        );
    }

    /**
     * التعليمات اللي بتتبعت مع الصورة.
     */
    public static function prompt(string $type): string
    {
        $party = $type === Invoice::TYPE_PURCHASE
            ? 'المورد (اللي إحنا بنشتري منه)'
            : 'التاجر (اللي إحنا بنبيع له)';

        $extra = $type === Invoice::TYPE_PURCHASE
            ? "- لو الفاتورة بعملة أجنبية، هات العملة وسعر الصرف لو مكتوب.\n"
              .'- مصاريف الشحن والجمارك والتخليص بتبقى تحت الإجمالي — هاتها لو موجودة.'
            : '- الفاتورة دي بالجنيه المصري، فسيب العملة وسعر الصرف ومصاريف الاستيراد فاضيين.';

        $handwriting = $type === Invoice::TYPE_SALE
            ? self::HANDWRITTEN_SALE
            : '';

        return <<<TXT
            اقرا صورة الفاتورة دي وطلّع بياناتها JSON.

            الطرف التاني في الفاتورة هو {$party}.

            قواعد لازم تلتزم بيها:
            - رجّع اللي مكتوب على الورق بالظبط. لو حاجة مش واضحة أو مش موجودة،
              سيبها null. ماتخترعش ولا قيمة.
            - الكميات والأسعار أرقام مجرّدة من غير رمز عملة ومن غير فاصلة
              آلاف — يعني 1250.50 مش "1,250.50 ج.م".
            - الأرقام الهندية (٠١٢٣٤٥٦٧٨٩) حوّلها لاتينية: «٤٠» تبقى 40،
              و«٣٤٢» تبقى 342.
            - التاريخ بصيغة YYYY-MM-DD. لو مكتوب 13/11/2026 يبقى 2026-11-13.
            - سعر الوحدة هو السعر قبل الخصم. لو الفاتورة مكتوب فيها الإجمالي
              بس، اقسمه على الكمية.
            - هات كل بند في الفاتورة، حتى لو اسمه مختصر أو مش واضح.
            - ماتحسبش إجماليات ولا ضريبة — النظام بيحسبها لوحده.
            {$extra}{$handwriting}
            TXT;
    }

    /**
     * فواتير العميل بتتكتب بخط اليد على دفتر مطبوع.
     *
     * الوصف ده مبني على فواتير حقيقية: تلات خانات بس، اسم العميل مكتوب
     * في أول سطر من خانة الصنف مش في خانة لوحده، والوحدات مختصرة بحرف.
     * من غير الشرح ده الموديل بيقرا اسم العميل كأنه صنف.
     */
    private const HANDWRITTEN_SALE = <<<'TXT'


        الفاتورة دي غالبًا مكتوبة بخط اليد على دفتر مطبوع. خد بالك من ده:

        - الجدول تلات خانات بس، من اليمين للشمال: **الكمية**، بعدين
          **اسم العميل / الصنف**، بعدين **السعر**. مفيش خانة كود صنف خالص،
          فسيب `code` فاضي دايمًا.
        - **أول سطر في خانة «اسم العميل / الصنف» هو اسم العميل، مش صنف.**
          حطّه في `party_name` وماتحطّهوش في `items`. السطور اللي بعده بس
          هي الأصناف.
        - الوحدات مختصرة بحرف: «ك» يعني كرتونة. «٣ ك» يعني الكمية 3.
          حط الرقم في `quantity` والكلام الباقي سيبه في اسم الصنف.
        - ساعات السعر مكتوب للجوز (الاتنين) ومكتوب جنبه «الجوز». رجّع الرقم
          زي ما هو في `unit_price` وسيب كلمة «الجوز» في اسم الصنف عشان
          المستخدم يراجعها.
        - الصورة ممكن تكون مايلة أو مقلوبة ٩٠ درجة وعليها ظل. لفّها في دماغك
          واقرا عادي.
        - الخط وحش وفيه حروف ناقصة. اكتب **أقرب قراءة معقولة** — «حوز»
          اقراها «جوز»، و«كبولى» اقراها «كبولي». ماتسيبش السطر فاضي عشان
          الخط مش واضح؛ النظام بيعرض اللي قريته للمستخدم عشان يصحّحه.
        - الخانات المطبوعة الفاضية (رقم، التاريخ) سيبها null.
        TXT;
}
