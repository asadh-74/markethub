<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { money, statusClass } from '../../utils';

defineProps({
    stats: { type: Object, required: true },
    stores: { type: Array, default: () => [] },
    recent_orders: { type: Array, default: () => [] },
});
const setStatus = (store, status) =>
    router.patch('/admin/stores/' + store.id, { status }, { preserveScroll: true });
</script>

<template>
    <AppLayout>
        <Head title="Admin overview" />
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">MarketHub / Administration</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">Marketplace overview</h1>
                <p class="mt-2 text-sm text-slate-500">Manage vendors, inventory and orders from one secure workspace. <span class="ml-1 rounded bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700">Laravel + {{ stats.database_driver === 'mysql' ? 'MySQL' : stats.database_driver === 'pgsql' ? 'PostgreSQL' : 'SQLite demo' }}</span></p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Link href="/admin/users" class="rounded-lg border border-indigo-200 bg-white px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50">Manage users</Link>
                <Link href="/admin/products" class="rounded-lg border border-indigo-200 bg-white px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50">Manage products</Link>
                <Link href="/admin/orders" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Manage orders</Link>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-sm text-slate-500">Users</p><p class="mt-2 text-2xl font-semibold">{{ stats.users }}</p></div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-sm text-slate-500">Orders</p><p class="mt-2 text-2xl font-semibold">{{ stats.orders }}</p></div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-sm text-slate-500">Products</p><p class="mt-2 text-2xl font-semibold">{{ stats.products }}</p></div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-sm text-slate-500">Demo sales</p><p class="mt-2 text-2xl font-semibold">{{ money(stats.sales_cents) }}</p></div>
        </div>

        <div class="mb-8 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <Link href="#vendors" class="rounded-xl border border-amber-200 bg-amber-50 p-4 hover:bg-amber-100"><p class="text-sm text-amber-800">Vendors awaiting approval</p><p class="mt-2 text-xl font-bold text-amber-900">{{ stats.pending_stores }}</p></Link>
            <Link href="/admin/orders" class="rounded-xl border border-sky-200 bg-sky-50 p-4 hover:bg-sky-100"><p class="text-sm text-sky-800">Order items awaiting shipment</p><p class="mt-2 text-xl font-bold text-sky-900">{{ stats.pending_items }}</p></Link>
            <Link href="/admin/products" class="rounded-xl border border-rose-200 bg-rose-50 p-4 hover:bg-rose-100"><p class="text-sm text-rose-800">Low-stock products</p><p class="mt-2 text-xl font-bold text-rose-900">{{ stats.low_stock }}</p></Link>
        </div>

        <section id="vendors" class="mb-8 rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b px-4 py-4 sm:px-5">
                <h2 class="text-lg font-semibold">Vendor stores</h2>
                <span class="text-sm text-slate-500">{{ stores.length }} registered stores</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[630px] w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                        <tr><th class="px-4 py-3">Store</th><th class="px-4 py-3">Owner</th><th class="px-4 py-3">Products</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Action</th></tr>
                    </thead>
                    <tbody>
                        <tr v-if="!stores.length"><td colspan="5" class="px-4 py-8 text-center text-slate-500">No vendor stores yet.</td></tr>
                        <tr v-for="s in stores" :key="s.id" class="border-t border-slate-100">
                            <td class="px-4 py-3 font-medium">{{ s.name }}</td>
                            <td class="px-4 py-3"><div>{{ s.owner }}</div><div class="text-xs text-slate-500">{{ s.email }}</div></td>
                            <td class="px-4 py-3">{{ s.products_count }}</td>
                            <td class="px-4 py-3"><span class="rounded-full px-2 py-1 text-xs" :class="statusClass(s.status)">{{ s.status }}</span></td>
                            <td class="px-4 py-3 text-right">
                                <button v-if="s.status !== 'approved'" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-medium text-white hover:bg-emerald-700" @click="setStatus(s, 'approved')">Approve</button>
                                <button v-else class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-medium text-white hover:bg-rose-700" @click="setStatus(s, 'suspended')">Suspend</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Recent orders</h2>
                <Link href="/admin/orders" class="text-sm font-medium text-indigo-600 hover:underline">View all orders →</Link>
            </div>
            <p v-if="!recent_orders.length" class="text-sm text-slate-500">No orders yet.</p>
            <div v-for="order in recent_orders" :key="order.number" class="flex flex-wrap items-center justify-between gap-2 border-t py-3 text-sm first:border-0">
                <div><p class="font-medium">{{ order.number }}</p><p class="text-slate-500">{{ order.customer }}</p></div>
                <div class="flex items-center gap-3"><span class="rounded px-2 py-1 text-xs" :class="statusClass(order.status)">{{ order.status }}</span><span class="font-medium">{{ money(order.total_cents) }}</span></div>
            </div>
        </section>
        <p class="mt-4 text-xs text-slate-500">All payment and revenue figures in this portfolio are simulated. No actual payments are collected.</p>
    </AppLayout>
</template>
