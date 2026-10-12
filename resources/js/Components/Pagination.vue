<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({ links: { type: Array, default: () => [] } });
</script>

<template>
    <nav v-if="links.length > 3" class="mt-6 flex flex-wrap justify-center gap-1">
        <template v-for="(link, i) in links" :key="i">
            <Link
                v-if="link.url"
                :href="link.url"
                preserve-scroll
                class="rounded border px-3 py-1 text-sm"
                :class="link.active ? 'border-indigo-600 bg-indigo-600 text-white' : 'bg-white hover:bg-slate-100'"
                v-html="link.label"
            />
            <span v-else class="rounded border px-3 py-1 text-sm text-slate-400" v-html="link.label" />
        </template>
    </nav>
</template>
