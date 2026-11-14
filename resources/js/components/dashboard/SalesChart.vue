<script setup lang="ts">
import { useElementSize } from '@vueuse/core';
import { computed, ref } from 'vue';
import {
    formatCompactMoney,
    formatDate,
    formatMoney,
    formatNumber,
    invoiceCountLabel,
} from '@/lib/format';

export type SeriesPoint = {
    date: string;
    endDate?: string;
    total: number;
    invoices: number;
};

const props = defineProps<{
    points: SeriesPoint[];
    granularity: 'daily' | 'weekly';
}>();

const wrapper = ref<HTMLElement | null>(null);
const { width } = useElementSize(wrapper);

const HEIGHT = 288;
const PAD_TOP = 18;
const PAD_BOTTOM = 30;
// فجوة على اليمين لأرقام المحور الرأسي — في الواجهة العربية المحور على اليمين
const PAD_AXIS = 62;
const PAD_END = 10;

const chartWidth = computed(() => Math.max(width.value || 720, 320));
const plotLeft = computed(() => PAD_END);
const plotRight = computed(() => chartWidth.value - PAD_AXIS);
const plotWidth = computed(() =>
    Math.max(plotRight.value - plotLeft.value, 10),
);
const plotHeight = HEIGHT - PAD_TOP - PAD_BOTTOM;

/** أقصى قيمة مكوّرة لرقم مريح للقراءة */
const niceMax = computed(() => {
    const max = Math.max(...props.points.map((p) => p.total), 0);

    if (max <= 0) {
        return 1000;
    }

    const magnitude = 10 ** Math.floor(Math.log10(max));
    const steps = [1, 1.25, 1.5, 2, 2.5, 3, 4, 5, 7.5, 10];

    for (const step of steps) {
        if (max <= step * magnitude) {
            return step * magnitude;
        }
    }

    return 10 * magnitude;
});

/**
 * المحور الزمني بيمشي من اليمين (الأقدم) لليسار (الأحدث) عشان يتماشى
 * مع اتجاه القراءة العربي والمحور الرأسي اللي على اليمين.
 */
function xFor(index: number): number {
    const count = props.points.length;

    if (count <= 1) {
        return plotRight.value;
    }

    return plotRight.value - (index / (count - 1)) * plotWidth.value;
}

function yFor(value: number): number {
    return PAD_TOP + plotHeight * (1 - value / niceMax.value);
}

const coords = computed(() =>
    props.points.map((point, index) => ({
        ...point,
        index,
        x: xFor(index),
        y: yFor(point.total),
    })),
);

const linePath = computed(() =>
    coords.value
        .map(
            (c, i) =>
                `${i === 0 ? 'M' : 'L'}${c.x.toFixed(2)},${c.y.toFixed(2)}`,
        )
        .join(' '),
);

const areaPath = computed(() => {
    if (coords.value.length === 0) {
        return '';
    }

    const baseline = PAD_TOP + plotHeight;
    const first = coords.value[0];
    const last = coords.value[coords.value.length - 1];

    return `${linePath.value} L${last.x.toFixed(2)},${baseline} L${first.x.toFixed(2)},${baseline} Z`;
});

/** ٤ خطوط شبكة أفقية */
const gridLines = computed(() =>
    [0, 0.25, 0.5, 0.75, 1].map((ratio) => ({
        ratio,
        y: PAD_TOP + plotHeight * (1 - ratio),
        label: formatCompactMoney(niceMax.value * ratio),
    })),
);

/** عدد محدود من علامات المحور الأفقي — مش كل نقطة */
const xTicks = computed(() => {
    const count = props.points.length;

    if (count === 0) {
        return [];
    }

    const target = chartWidth.value < 520 ? 3 : 5;
    const stride = Math.max(1, Math.round((count - 1) / (target - 1)));
    const ticks: { x: number; label: string }[] = [];

    for (let i = 0; i < count; i += stride) {
        ticks.push({ x: xFor(i), label: shortLabel(props.points[i].date) });
    }

    return ticks;
});

function shortLabel(date: string): string {
    return formatDate(date).split(' ').slice(0, 2).join(' ');
}

// ——— طبقة التمرير ———
const hoverIndex = ref<number | null>(null);

const hovered = computed(() =>
    hoverIndex.value === null ? null : coords.value[hoverIndex.value],
);

function onMove(event: MouseEvent) {
    const rect = (
        event.currentTarget as SVGRectElement
    ).getBoundingClientRect();
    const offsetX = event.clientX - rect.left;
    const ratio = (plotRight.value - offsetX) / plotWidth.value;
    const index = Math.round(ratio * (props.points.length - 1));

    hoverIndex.value = Math.min(Math.max(index, 0), props.points.length - 1);
}

/** التولتيب بيتقلب لو قرّب من حافة الرسم */
const tooltipStyle = computed(() => {
    if (!hovered.value) {
        return {};
    }

    const flip = hovered.value.x < 150;

    return {
        insetInlineStart: `${flip ? hovered.value.x + 12 : hovered.value.x - 12}px`,
        top: `${Math.max(hovered.value.y - 8, 8)}px`,
        transform: flip ? 'translate(0, -50%)' : 'translate(-100%, -50%)',
    };
});

function periodLabel(point: SeriesPoint): string {
    return point.endDate
        ? `${formatDate(point.date)} — ${formatDate(point.endDate)}`
        : formatDate(point.date);
}

const total = computed(() =>
    props.points.reduce((sum, point) => sum + point.total, 0),
);
</script>

<template>
    <div ref="wrapper" class="relative w-full">
        <svg
            :viewBox="`0 0 ${chartWidth} ${HEIGHT}`"
            :height="HEIGHT"
            class="w-full overflow-visible"
            role="img"
            :aria-label="`مبيعات آخر ٣ شهور، إجمالي ${formatMoney(total)}`"
        >
            <defs>
                <linearGradient id="salesFill" x1="0" y1="0" x2="0" y2="1">
                    <stop
                        offset="0%"
                        stop-color="var(--chart-1)"
                        stop-opacity="0.28"
                    />
                    <stop
                        offset="100%"
                        stop-color="var(--chart-1)"
                        stop-opacity="0.01"
                    />
                </linearGradient>
            </defs>

            <!-- شبكة خافتة + أرقام المحور الرأسي -->
            <g>
                <line
                    v-for="line in gridLines"
                    :key="`g-${line.ratio}`"
                    :x1="plotLeft"
                    :x2="plotRight"
                    :y1="line.y"
                    :y2="line.y"
                    stroke="var(--border)"
                    stroke-width="1"
                    :stroke-dasharray="line.ratio === 0 ? '0' : '3 4'"
                />
                <text
                    v-for="line in gridLines"
                    :key="`t-${line.ratio}`"
                    :x="plotRight + PAD_AXIS / 2"
                    :y="line.y + 4"
                    text-anchor="middle"
                    class="fill-muted-foreground text-[11px]"
                >
                    {{ line.label }}
                </text>
            </g>

            <path :d="areaPath" fill="url(#salesFill)" />
            <path
                :d="linePath"
                fill="none"
                stroke="var(--chart-1)"
                stroke-width="2"
                stroke-linejoin="round"
                stroke-linecap="round"
            />

            <!-- علامات المحور الأفقي -->
            <text
                v-for="tick in xTicks"
                :key="tick.label"
                :x="tick.x"
                :y="HEIGHT - 10"
                text-anchor="middle"
                class="fill-muted-foreground text-[11px]"
            >
                {{ tick.label }}
            </text>

            <!-- خط التتبّع ونقطة القيمة -->
            <g v-if="hovered">
                <line
                    :x1="hovered.x"
                    :x2="hovered.x"
                    :y1="PAD_TOP"
                    :y2="PAD_TOP + plotHeight"
                    stroke="var(--chart-1)"
                    stroke-width="1"
                    stroke-dasharray="3 3"
                    opacity="0.55"
                />
                <circle
                    :cx="hovered.x"
                    :cy="hovered.y"
                    r="5"
                    fill="var(--chart-1)"
                    stroke="var(--card)"
                    stroke-width="2"
                />
            </g>

            <rect
                :x="plotLeft"
                :y="PAD_TOP"
                :width="plotWidth"
                :height="plotHeight"
                fill="transparent"
                @mousemove="onMove"
                @mouseleave="hoverIndex = null"
            />
        </svg>

        <!-- التولتيب -->
        <div
            v-if="hovered"
            class="pointer-events-none absolute z-10 min-w-40 rounded-lg border border-border bg-popover px-3 py-2 text-xs shadow-soft-lg"
            :style="tooltipStyle"
        >
            <p class="font-semibold text-popover-foreground">
                {{ periodLabel(hovered) }}
            </p>
            <p class="mt-1 font-bold text-popover-foreground">
                {{ formatMoney(hovered.total) }}
            </p>
            <p class="mt-0.5 text-muted-foreground">
                {{ invoiceCountLabel(hovered.invoices) }}
            </p>
        </div>

        <!--
          بديل نصي لقارئ الشاشة بدل الاعتماد على الرسم لوحده.
          الـ sr-only لازم تبقى على div حاوي مش على الـ table نفسه: عنصر
          display:table بيتجاهل width/height 1px وبيتمدّ على طول محتواه،
          وبما إنه absolute كان بيزوّد ارتفاع الصفحة بآلاف البكسلات الفاضية.
        -->
        <div class="sr-only">
            <table>
                <caption>
                    مبيعات آخر ٣ شهور
                    {{
                        granularity === 'daily' ? 'باليوم' : 'بالأسبوع'
                    }}
                </caption>
                <thead>
                    <tr>
                        <th scope="col">الفترة</th>
                        <th scope="col">المبيعات</th>
                        <th scope="col">عدد الفواتير</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="point in points" :key="point.date">
                        <th scope="row">{{ periodLabel(point) }}</th>
                        <td>{{ formatMoney(point.total) }}</td>
                        <td>{{ formatNumber(point.invoices) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
