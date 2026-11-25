<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Printer, X } from '@lucide/vue';
import { computed, onMounted } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';

/**
 * بيانات الشركة في ترويسة الورقة.
 *
 * بتيجي من `CompanyProfile::present()`. بنقبل نص كمان عشان أي صفحة
 * طباعة لسه بتبعت الاسم لوحده ما تفضلش بترويسة فاضية.
 */
export type CompanyHeader = {
    name: string;
    logo?: string | null;
    address?: string | null;
    phones?: string | null;
    taxNumber?: string | null;
    commercialRegistry?: string | null;
    footerNote?: string | null;
};

const props = defineProps<{
    company: CompanyHeader | string;
    title: string;
    subtitle?: string;
    printedAt: string;
    /** يفتح نافذة الطباعة أول ما الصفحة تحمّل */
    autoPrint?: boolean;
}>();

const co = computed<CompanyHeader>(() =>
    typeof props.company === 'string' ? { name: props.company } : props.company,
);

/** الرقم الضريبي والسجل التجاري — مطلوبين على الفاتورة قانونًا */
const registrations = computed(() =>
    [
        co.value.taxNumber ? `رقم ضريبي: ${co.value.taxNumber}` : null,
        co.value.commercialRegistry
            ? `سجل تجاري: ${co.value.commercialRegistry}`
            : null,
    ]
        .filter(Boolean)
        .join(' · '),
);

const contact = computed(() =>
    [co.value.address, co.value.phones].filter(Boolean).join(' · '),
);

/** ملاحظة الفوتر لو العميل كتبها، وإلا سطر افتراضي */
const footer = computed(
    () => co.value.footerNote || `هذا المستند صادر من ${co.value.name}`,
);

onMounted(() => {
    if (props.autoPrint) {
        setTimeout(() => window.print(), 400);
    }
});

// الـ template ماعندوش وصول لـ window مباشرة
function print() {
    window.print();
}

function close() {
    window.close();
}
</script>

<template>
    <Head :title="title" />

    <!-- الورقة دايماً فاتحة حتى لو المستخدم على الوضع الداكن -->
    <!-- الورقة عرضها A4 ثابت، فعلى الموبايل بتتزحلق جوه الحاوية مش بتكسر الصفحة -->
    <div
        class="min-h-svh overflow-x-auto bg-neutral-100 py-6 print:overflow-visible print:bg-white print:py-0"
    >
        <!-- شريط الإجراءات — بيختفي وقت الطباعة -->
        <div
            class="no-print mx-auto mb-4 flex max-w-[210mm] items-center justify-between gap-3 px-4"
        >
            <Button variant="outline" size="sm" @click="close">
                <X class="size-4" />
                إغلاق
            </Button>
            <Button size="sm" data-test="print-button" @click="print">
                <Printer class="size-4" />
                طباعة / حفظ PDF
            </Button>
        </div>

        <article
            class="print-sheet mx-auto max-w-[210mm] bg-white p-10 text-neutral-900 shadow-soft-lg"
        >
            <header
                class="flex items-start justify-between gap-6 border-b-2 border-neutral-800 pb-5"
            >
                <div class="flex items-center gap-3">
                    <!-- لوجو الشركة لو اترفع، وإلا علامة النظام -->
                    <img
                        v-if="co.logo"
                        :src="co.logo"
                        alt=""
                        class="size-11 object-contain"
                        data-test="company-logo"
                    />
                    <span
                        v-else
                        class="flex size-11 items-center justify-center rounded-lg bg-neutral-900 text-white"
                    >
                        <AppLogoIcon class="size-6" />
                    </span>
                    <div>
                        <p class="text-lg font-bold">{{ co.name }}</p>
                        <p v-if="contact" class="text-xs text-neutral-500">
                            {{ contact }}
                        </p>
                        <p
                            v-if="registrations"
                            class="text-xs text-neutral-500 tabular-nums"
                            data-test="company-registrations"
                        >
                            {{ registrations }}
                        </p>
                    </div>
                </div>

                <div class="text-end">
                    <h1 class="text-xl font-bold">{{ title }}</h1>
                    <p v-if="subtitle" class="mt-0.5 text-xs text-neutral-600">
                        {{ subtitle }}
                    </p>
                    <p class="mt-1 text-xs text-neutral-500">
                        تاريخ الطباعة: {{ formatDate(printedAt) }}
                    </p>
                </div>
            </header>

            <slot />

            <footer
                class="mt-8 border-t border-neutral-300 pt-4 text-center text-[11px] text-neutral-500"
            >
                {{ footer }} — {{ formatDate(printedAt) }}
            </footer>
        </article>
    </div>
</template>
