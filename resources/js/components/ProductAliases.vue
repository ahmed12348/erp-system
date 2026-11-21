<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Plus, Sparkles, X } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useAbilities } from '@/composables/useAbilities';
import { formatDate } from '@/lib/format';

/**
 * الأسماء البديلة للصنف.
 *
 * فواتير العملاء مكتوبة بخط اليد، والاسم على الورق نادرًا ما بيطابق
 * اسم الكتالوج. أول ما المستخدم يصحّح المطابقة في مسودة، الاسم بيتحفظ
 * هنا والمسح الجاي بيعرفه لوحده.
 *
 * بنعرضهم عشان حاجتين: المستخدم يفهم ليه صنف اتطابق باسم غريب، ويقدر
 * يشيل اسم اتحفظ غلط قبل ما يبوّظ فواتير جاية.
 */

type Alias = {
    id: number;
    alias: string;
    addedBy: string | null;
    addedAt: string | null;
};

const props = defineProps<{
    productId: number;
    aliases: Alias[];
}>();

const { manageMasterData } = useAbilities();

const form = useForm({ alias: '' });

function add() {
    form.post(`/products/${props.productId}/aliases`, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function remove(alias: Alias) {
    router.delete(`/product-aliases/${alias.id}`, { preserveScroll: true });
}
</script>

<template>
    <SectionCard title="أسماء بديلة">
        <div class="space-y-4">
            <p class="text-xs text-muted-foreground">
                الأسماء اللي بيتكتب بيها الصنف على فواتير العملاء. النظام
                بيتعلّمها لوحده أول ما تصحّح مطابقة في فاتورة ممسوحة، وبيستعملها
                قبل أي مقارنة تانية.
            </p>

            <ul v-if="aliases.length" class="flex flex-wrap gap-2">
                <li
                    v-for="alias in aliases"
                    :key="alias.id"
                    class="group flex items-center gap-1.5 rounded-full border border-border bg-muted/40 py-1 ps-3 pe-1.5 text-sm"
                    data-test="product-alias"
                >
                    <Sparkles class="size-3 text-muted-foreground" />
                    <span>{{ alias.alias }}</span>
                    <span
                        v-if="alias.addedAt"
                        class="text-[10px] text-muted-foreground"
                    >
                        {{ formatDate(alias.addedAt) }}
                    </span>
                    <button
                        v-if="manageMasterData"
                        type="button"
                        class="flex size-5 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive"
                        :aria-label="`شيل الاسم البديل ${alias.alias}`"
                        data-test="product-alias-remove"
                        @click="remove(alias)"
                    >
                        <X class="size-3" />
                    </button>
                </li>
            </ul>

            <p v-else class="text-sm text-muted-foreground">
                لسه مافيش أسماء بديلة للصنف ده.
            </p>

            <form
                v-if="manageMasterData"
                class="flex items-start gap-2"
                @submit.prevent="add"
            >
                <div class="flex-1 space-y-1">
                    <Input
                        v-model="form.alias"
                        placeholder="اكتب اسم زي ما بيتكتب على الفاتورة"
                        maxlength="191"
                        data-test="product-alias-input"
                    />
                    <InputError :message="form.errors.alias" />
                </div>
                <Button
                    type="submit"
                    variant="outline"
                    :disabled="form.processing || form.alias.trim() === ''"
                    data-test="product-alias-add"
                >
                    <Plus class="size-4" />
                    إضافة
                </Button>
            </form>
        </div>
    </SectionCard>
</template>
