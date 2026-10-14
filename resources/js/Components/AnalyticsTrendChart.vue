<script setup>
import { computed } from 'vue';
import { money } from '../utils';

const props = defineProps({
    points: { type: Array, required: true },
    valueKey: { type: String, required: true },
    kind: { type: String, default: 'line' },
    ariaLabel: { type: String, required: true },
    unit: { type: String, default: 'count' },
    accent: { type: String, default: '#6366f1' },
});

const width = 720;
const height = 244;
const left = 74;
const right = 18;
const top = 20;
const bottom = 202;
const plotWidth = width - left - right;
const plotHeight = bottom - top;

const values = computed(() => props.points.map((point) => Number(point[props.valueKey] || 0)));
const maxValue = computed(() => Math.max(1, ...values.value));
const hasData = computed(() => values.value.some((value) => value > 0));
const x = (i) => left + (props.points.length <= 1 ? plotWidth / 2 : (i / (props.points.length - 1)) * plotWidth);
const y = (v) => bottom - (Math.max(0, Number(v)) / maxValue.value) * plotHeight;
const coordinates = computed(() => values.value.map((v, i) => [x(i), y(v)]));
const polyline = computed(() => coordinates.value.map(([px, py]) => px + ',' + py).join(' '));
const area = computed(() => coordinates.value.length
    ? left + ',' + bottom + ' ' + polyline.value + ' ' + x(props.points.length - 1) + ',' + bottom
    : '');
const tickLabels = computed(() => {
    if (!props.points.length) return [];
    const steps = Math.min(5, props.points.length);
    return Array.from({ length: steps }, (_, i) =>
        Math.round((i * (props.points.length - 1)) / Math.max(1, steps - 1))
    ).filter((i, index, list) => list.indexOf(i) === index);
});
const displayValue = (value) => props.unit === 'currency' ? money(value) : Number(value).toLocaleString('en-PK');
const axisValue = (value) => props.unit === 'currency'
    ? 'Rs ' + Math.round(value / 100).toLocaleString('en-PK')
    : Math.round(value).toLocaleString('en-PK');
const barWidth = computed(() => Math.max(2, Math.min(28, plotWidth / Math.max(1, props.points.length) * 0.65)));
</script>

<template>
    <div class="relative w-full">
        <svg :viewBox="'0 0 ' + width + ' ' + height" class="block w-full" role="img" :aria-label="ariaLabel" preserveAspectRatio="xMidYMid meet">
            <g v-for="fraction in [0, 0.25, 0.5, 0.75, 1]" :key="fraction">
                <line :x1="left" :x2="width - right" :y1="y(fraction * maxValue)" :y2="y(fraction * maxValue)"
                    stroke="#e2e8f0" stroke-width="1" stroke-dasharray="4 5" />
                <text :x="left - 10" :y="y(fraction * maxValue) + 4" text-anchor="end" font-size="11" fill="#64748b">{{ axisValue(fraction * maxValue) }}</text>
            </g>
            <template v-if="hasData">
                <template v-if="kind === 'line'">
                    <polygon :points="area" :fill="accent" fill-opacity=".10" />
                    <polyline :points="polyline" fill="none" :stroke="accent" stroke-width="3"
                        stroke-linejoin="round" stroke-linecap="round" />
                    <circle v-for="(point, i) in points" :key="point.date"
                        :cx="x(i)" :cy="y(values[i])" :r="points.length <= 30 ? 3 : 1.8"
                        :fill="accent" stroke="white" stroke-width="1">
                        <title>{{ point.date }}: {{ displayValue(values[i]) }}</title>
                    </circle>
                </template>
                <template v-else>
                    <rect v-for="(point, i) in points" :key="point.date"
                        :x="x(i) - barWidth / 2" :y="y(values[i])"
                        :width="barWidth" :height="Math.max(0, bottom - y(values[i]))"
                        :fill="accent" rx="2">
                        <title>{{ point.date }}: {{ displayValue(values[i]) }}</title>
                    </rect>
                </template>
            </template>
            <g v-for="index in tickLabels" :key="index">
                <text :x="x(index)" :y="height - 17" text-anchor="middle" font-size="11" fill="#64748b">{{ points[index].label }}</text>
            </g>
        </svg>
        <div v-if="!hasData" class="absolute inset-0 flex items-center justify-center">
            <p class="rounded-lg border border-slate-200 bg-white/90 px-4 py-2 text-sm font-medium text-slate-500">
                No completed demo orders in this period yet
            </p>
        </div>
    </div>
</template>
