<script setup lang="ts">
import { Download, FileSpreadsheet, FileText } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

/**
 * زرار «تحميل» جنب زرار الطباعة.
 *
 * الطباعة والتحميل حاجتين مختلفتين: الطباعة لما تكون عايز ورقة في
 * إيدك دلوقتي، والتحميل لما تكون عايز تبعت الملف أو تأرشفه. فالزرارين
 * بيفضلوا جنب بعض مش واحد مكان التاني.
 *
 * الـ PDF بيتولّد على السيرفر بخط Cairo متضمّن — النص فيه نص حقيقي
 * ينفع تحدّده وتنسخه، مش صورة.
 */

const props = withDefaults(
    defineProps<{
        /** المسار من غير `format` */
        href: string;
        label?: string;
        /** بعض الشاشات مالهاش معنى تتصدّر إكسيل */
        excel?: boolean;
    }>(),
    { label: 'تحميل', excel: true },
);

function url(format: 'pdf' | 'excel'): string {
    const separator = props.href.includes('?') ? '&' : '?';

    return `${props.href}${separator}format=${format}`;
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="outline" data-test="download-menu">
                <Download class="size-4" />
                {{ label }}
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-44">
            <DropdownMenuItem as-child>
                <a :href="url('pdf')" data-test="download-pdf">
                    <FileText class="size-4" />
                    تحميل PDF
                </a>
            </DropdownMenuItem>
            <DropdownMenuItem v-if="excel" as-child>
                <a :href="url('excel')" data-test="download-excel">
                    <FileSpreadsheet class="size-4" />
                    تحميل إكسيل
                </a>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
