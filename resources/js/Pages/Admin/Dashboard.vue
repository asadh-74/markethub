<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { money, statusClass } from '../../utils';

defineProps({
    stats: { type: Object, required: true },
    stores: { type: Array, default: () => [] },
});

const setStatus = (store, status) =>
    router.patch(`/admin/stores/${store.id}`, { status }, { preserveScroll: true });
</script>

<template>
    <AppLayout>
        <Head title="Admin" />
        <h1 class="mb-6 text-2xl font-semibold">Admin dashboard</h1>

        <div class="mb-8 grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border bg-white p-4"><div class="text-sm text-slate-500">Users</div><div class="text-2xl font-bold">{{ stats.users }}</div></div>
            <div class="rounded-lg border bg-white p-4"><div class="text-sm text-slate-500">Orders</div><div class="text-2xl font-bold">{{ stats.orders }}</div></div>
            <div class="rounded-lg border bg-white p-4"><div class="text-sm text-slate-500">Total sales</div><div class="text-2xl font-bold">{{ money(stats.sales_cents) }}</div></div>
        </div>

        <h2 class="mb-3 font-semibold">Stores</h2>
        <div class="overflow-hidden rounded-lg border bg-white">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr><th class="px-4 py-2">Store</th><th class="px-4 py-2">Owner</th><th class="px-4 py-2">Products</th><th class="px-4 py-2">Status</th><th class="px-4 py-2"></th></tr>
                </thead>
                <tbody>
                    <tr v-for="s in stores" :key="s.id" class="border-t">
                        <td class="px-4 py-2">{{ s.name }}</td>
                        <td class="px-4 py-2">{{ s.owner }} <span class="text-xs text-slate-400">{{ s.email }}</span></td>
                        <td class="px-4 py-2">{{ s.products_count }}</td>
                        <td class="px-4 py-2"><span class="rounded px-2 py-0.5 text-xs" :class="statusClass(s.status)">{{ s.status }}</span></td>
                        <td class="space-x-2 px-4 py-2 text-right">
                            <button v-if="s.status !== 'approved'" class="rounded bg-emerald-600 px-3 py-1 text-xs text-white hover:bg-emerald-700" @click="setStatus(s, 'approved')">Approve</button>
                            <button v-if="s.status === 'approved'" class="rounded bg-red-600 px-3 py-1 text-xs text-white hover:bg-red-700" @click="setStatus(s, 'suspended')">Suspend</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
