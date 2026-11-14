<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatMoney } from '@/lib/format';

const props = defineProps<{
    customerId: number;
    customerName: string;
    balance: number;
    openInvoices: { id: number; label: string }[];
}>();

const open = ref(false);

const form = useForm({
    amount: '' as string | number,
    method: 'cash',
    date: new Date().toISOString().slice(0, 10),
    reference: '',
    notes: '',
    invoice_id: '' as string | number,
});

const METHODS = [
    { value: 'cash', label: 'نقدي' },
    { value: 'bank', label: 'تحويل بنكي' },
    { value: 'check', label: 'شيك' },
];

// رقم المرجع منطقي بس مع الشيك والتحويل
watch(
    () => form.method,
    (method) => {
        if (method === 'cash') {
            form.reference = '';
        }
    },
);

function submit() {
    form.transform((data) => ({
        ...data,
        invoice_id: data.invoice_id === '' ? null : data.invoice_id,
    })).post(`/customers/${props.customerId}/payments`, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.date = new Date().toISOString().slice(0, 10);
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <Button data-test="open-payment-dialog" @click="open = true">
            تسجيل دفعة
        </Button>

        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>تسجيل دفعة من {{ customerName }}</DialogTitle>
                <DialogDescription>
                    الرصيد الحالي {{ formatMoney(balance) }}
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="amount">المبلغ</Label>
                    <Input
                        id="amount"
                        v-model="form.amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        required
                        dir="ltr"
                        class="text-start"
                        data-test="payment-amount"
                    />
                    <InputError :message="form.errors.amount" />
                </div>

                <div class="space-y-2">
                    <Label for="method">طريقة الدفع</Label>
                    <select
                        id="method"
                        v-model="form.method"
                        class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                        data-test="payment-method"
                    >
                        <option
                            v-for="m in METHODS"
                            :key="m.value"
                            :value="m.value"
                        >
                            {{ m.label }}
                        </option>
                    </select>
                    <InputError :message="form.errors.method" />
                </div>

                <div class="space-y-2">
                    <Label for="date">التاريخ</Label>
                    <Input
                        id="date"
                        v-model="form.date"
                        type="date"
                        required
                        data-test="payment-date"
                    />
                    <InputError :message="form.errors.date" />
                </div>

                <div v-if="form.method !== 'cash'" class="space-y-2">
                    <Label for="reference">
                        {{
                            form.method === 'check'
                                ? 'رقم الشيك'
                                : 'رقم التحويل'
                        }}
                    </Label>
                    <Input
                        id="reference"
                        v-model="form.reference"
                        dir="ltr"
                        class="text-start"
                    />
                    <InputError :message="form.errors.reference" />
                </div>

                <div v-if="openInvoices.length" class="space-y-2">
                    <Label for="invoice_id">خصم من فاتورة (اختياري)</Label>
                    <select
                        id="invoice_id"
                        v-model="form.invoice_id"
                        class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                        data-test="payment-invoice"
                    >
                        <option value="">تحصيل عام على الحساب</option>
                        <option
                            v-for="invoice in openInvoices"
                            :key="invoice.id"
                            :value="invoice.id"
                        >
                            {{ invoice.label }}
                        </option>
                    </select>
                    <InputError :message="form.errors.invoice_id" />
                </div>

                <div class="space-y-2">
                    <Label for="notes">ملاحظات</Label>
                    <Input id="notes" v-model="form.notes" />
                    <InputError :message="form.errors.notes" />
                </div>

                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                    >
                        إلغاء
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        data-test="submit-payment"
                    >
                        <Spinner v-if="form.processing" />
                        حفظ الدفعة
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
