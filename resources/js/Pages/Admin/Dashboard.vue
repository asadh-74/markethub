<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import AnalyticsTrendChart from '../../Components/AnalyticsTrendChart.vue';
import { money, statusClass } from '../../utils';

const props = defineProps({
    stats: { type: Object, required: true },
    analytics: { type: Object, required: true },
    stores: { type: Array, default: () => [] },
    recent_orders: { type: Array, default: () => [] },
});
const setStatus = (store, status) =>
    router.patch('/admin/stores/' + store.id, { status }, { preserveScroll: true });

const changePeriod = (days) => router.get('/admin', { period: days }, {
    preserveScroll: true,
    preserveState: true,
});
const refresh = () => router.reload({ only: ['stats', 'analytics', 'stores', 'recent_orders'] });
const maximumCategoryStock = computed(() =>
    Math.max(1, ...props.analytics.inventory.map((row) => Number(row.stock_units) || 0)));
const stockWidth = (stock) => Math.max(0, (Number(stock) / maximumCategoryStock.value) * 100);
const hasSimulatedSales = computed(() => props.analytics.period_orders > 0);
</script>

<template>
    <AppLayout>
        <Head title="Admin analytics" />

        <section class="mb-6 overflow-hidden rounded-2xl bg-gradient-to-br from-slate-950 via-indigo-950 to-indigo-800 p-6 text-white shadow-xl sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-medium tracking-wide text-indigo-100">
                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                        MARKETPLACE COMMAND CENTER
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">MarketHub Analytics</h1>
                    <p class="mt-2 max-w-2xl text-sm text-indigo-100">
                        Monitor demo orders, shared inventory, vendor activity and catalogue health in one place.
                        Figures come from your Laravel database, not illustrative chart data.
                    </p>
                    <div class="mt-4 flex flex-wrap gap-2 text-xs">
                        <span class="rounded-full bg-white/15 px-3 py-1.5">
                            Laravel + {{ stats.database_driver === 'mysql' ? 'Aiven MySQL' : stats.database_driver === 'pgsql' ? 'PostgreSQL' : 'SQLite Demo' }}
                        </span>
                        <span class="rounded-full bg-white/15 px-3 py-1.5">Simulated checkout · no real payments</span>
                    </div>
                </div>
                <button @click="refresh" class="rounded-lg border border-white/30 bg-white/10 px-4 py-2 text-sm font-medium hover:bg-white/20">
                    ↻ Refresh metrics
                </button>
            </div>
            <div class="mt-6 flex flex-wrap gap-2">
                <Link href="/admin/users" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-indigo-900 hover:bg-indigo-100">Manage users ↗</Link>
                <Link href="/admin/products" class="rounded-lg border border-white/30 px-4 py-2 text-sm font-medium text-white hover:bg-white/10">Inventory</Link>
                <Link href="/admin/orders" class="rounded-lg border border-white/30 px-4 py-2 text-sm font-medium text-white hover:bg-white/10">View orders</Link>
            </div>
        </section>

        <section aria-label="Key marketplace metrics" class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex items-center justify-between"><p class="text-sm text-slate-500">Registered users</p><span class="rounded-lg bg-sky-50 p-2 text-sky-600">♙</span></div>
                <p class="mt-3 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ stats.users.toLocaleString('en-PK') }}</p>
                <p class="mt-1 text-xs text-slate-500">Customer, vendor and admin accounts</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex items-center justify-between"><p class="text-sm text-slate-500">All orders</p><span class="rounded-lg bg-violet-50 p-2 text-violet-600">▣</span></div>
                <p class="mt-3 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ stats.orders.toLocaleString('en-PK') }}</p>
                <p class="mt-1 text-xs text-slate-500">Orders across all statuses</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex items-center justify-between"><p class="text-sm text-slate-500">Product listings</p><span class="rounded-lg bg-amber-50 p-2 text-amber-600">▦</span></div>
                <p class="mt-3 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ stats.products.toLocaleString('en-PK') }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ analytics.visible_products }} public · {{ analytics.stock_units }} units in stock</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex items-center justify-between"><p class="text-sm text-slate-500">Lifetime demo sales</p><span class="rounded-lg bg-emerald-50 p-2 text-emerald-600">↗</span></div>
                <p class="mt-3 text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">{{ money(stats.sales_cents) }}</p>
                <p class="mt-1 text-xs text-slate-500">Paid and shipped simulated orders</p>
            </article>
        </section>

        <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6" aria-label="Sales analytics">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">Performance</p>
                    <h2 class="mt-1 text-lg font-semibold text-slate-900">Order &amp; sales trends</h2>
                    <p class="mt-1 text-xs text-slate-500">Actual demo transactions, grouped by order date. No fabricated activity.</p>
                </div>
                <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1" aria-label="Analytics date range">
                    <button v-for="period in [7, 30, 90]" :key="period" @click="changePeriod(period)"
                        :aria-pressed="analytics.period_days === period"
                        class="rounded-md px-3 py-1.5 text-xs font-medium transition"
                        :class="analytics.period_days === period ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200'">
                        {{ period }}d
                    </button>
                </div>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl bg-indigo-50/60 p-4">
                    <p class="text-xs font-medium text-slate-500">Simulated sales in selected period</p>
                    <p class="mt-1 text-xl font-bold text-indigo-900">{{ money(analytics.period_sales_cents) }}</p>
                </div>
                <div class="rounded-xl bg-emerald-50/60 p-4">
                    <p class="text-xs font-medium text-slate-500">Paid / shipped demo orders in selected period</p>
                    <p class="mt-1 text-xl font-bold text-emerald-900">{{ analytics.period_orders }}</p>
                </div>
            </div>
            <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-2">
                <div class="min-w-0 rounded-xl border border-slate-100 p-3 sm:p-4">
                    <h3 class="text-sm font-semibold text-slate-900"><span class="mr-2 text-indigo-600">●</span> Simulated sales (PKR)</h3>
                    <AnalyticsTrendChart :points="analytics.trend" value-key="simulated_sales_cents"
                        unit="currency" accent="#4f46e5" aria-label="Daily simulated sales revenue in Pakistani rupees" />
                </div>
                <div class="min-w-0 rounded-xl border border-slate-100 p-3 sm:p-4">
                    <h3 class="text-sm font-semibold text-slate-900"><span class="mr-2 text-emerald-600">■</span> Demo order volume</h3>
                    <AnalyticsTrendChart :points="analytics.trend" value-key="order_count"
                        kind="bar" accent="#059669" aria-label="Daily count of paid or shipped demo orders" />
                </div>
            </div>
            <p class="mt-3 text-xs text-slate-500">
                Charts use completed demo orders only; pending orders are excluded from sales. This portfolio does not collect real payments.
            </p>
        </section>

        <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 xl:col-span-3" aria-label="Inventory by category">
                <div class="mb-5 flex flex-wrap items-start justify-between gap-2">
                    <div><p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">Warehouse</p><h2 class="mt-1 text-lg font-semibold">Stock by category</h2><p class="mt-1 text-xs text-slate-500">Visible products from approved sellers · quantities in units</p></div>
                    <span class="rounded-lg bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700">{{ analytics.stock_units }} units</span>
                </div>
                <div v-if="analytics.visible_products === 0" class="rounded-lg bg-slate-50 py-12 text-center text-sm text-slate-500">No public product listings yet.</div>
                <div v-else class="space-y-5">
                    <div v-for="(category, i) in analytics.inventory" :key="category.name">
                        <div class="mb-1.5 flex items-center justify-between gap-2 text-sm">
                            <span class="truncate font-medium text-slate-700">{{ category.name }}</span>
                            <span class="whitespace-nowrap text-slate-500">{{ category.stock_units.toLocaleString('en-PK') }} units · {{ category.product_count }} items</span>
                        </div>
                        <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full transition-all duration-300" :class="['bg-indigo-600', 'bg-violet-500', 'bg-sky-500', 'bg-emerald-500', 'bg-amber-500'][i % 5]"
                                :style="{ width: stockWidth(category.stock_units) + '%' }"></div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 xl:col-span-2" aria-label="Stock alerts">
                <div class="mb-4 flex items-center justify-between gap-2">
                    <div><p class="text-xs font-semibold uppercase tracking-wider text-amber-600">Stock watch</p><h2 class="mt-1 text-lg font-semibold">Low inventory alerts</h2><p class="mt-1 text-xs text-slate-500">Products with 5 units or fewer</p></div>
                    <span class="rounded-lg bg-amber-50 px-3 py-1.5 text-sm font-semibold text-amber-700">{{ analytics.low_stock_products.length }}</span>
                </div>
                <div v-if="!analytics.low_stock_products.length" class="rounded-xl border border-dashed border-emerald-200 bg-emerald-50 p-6 text-center text-sm text-emerald-800">
                    ✓ No active listings are low on stock.
                </div>
                <div v-else class="divide-y divide-slate-100">
                    <div v-for="p in analytics.low_stock_products" :key="p.slug" class="flex items-center justify-between gap-3 py-3 text-sm">
                        <div class="min-w-0"><p class="truncate font-medium text-slate-800">{{ p.name }}</p><p class="text-xs text-slate-500">{{ p.store }}</p></div>
                        <span class="whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium" :class="p.stock === 0 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700'">
                            {{ p.stock === 0 ? 'Out of stock' : p.stock + ' left' }}
                        </span>
                    </div>
                </div>
                <Link href="/admin/products" class="mt-5 inline-block text-sm font-semibold text-indigo-600 hover:underline">Review inventory →</Link>
            </section>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <Link href="#vendors" class="rounded-xl border border-amber-200 bg-amber-50 p-4 hover:bg-amber-100"><p class="text-sm text-amber-800">Vendors awaiting approval</p><p class="mt-2 text-xl font-bold text-amber-900">{{ stats.pending_stores }}</p></Link>
            <Link href="/admin/orders" class="rounded-xl border border-sky-200 bg-sky-50 p-4 hover:bg-sky-100"><p class="text-sm text-sky-800">Order items awaiting shipment</p><p class="mt-2 text-xl font-bold text-sky-900">{{ stats.pending_items }}</p></Link>
            <Link href="/admin/products" class="rounded-xl border border-rose-200 bg-rose-50 p-4 hover:bg-rose-100"><p class="text-sm text-rose-800">Low-stock products</p><p class="mt-2 text-xl font-bold text-rose-900">{{ stats.low_stock }}</p></Link>
        </div>

        <section id="vendors" class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b px-4 py-4 sm:px-5">
                <h2 class="text-lg font-semibold text-slate-900">Vendor stores</h2>
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

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Recent orders</h2>
                <Link href="/admin/orders" class="text-sm font-medium text-indigo-600 hover:underline">View all orders →</Link>
            </div>
            <p v-if="!recent_orders.length" class="text-sm text-slate-500">No demo orders yet. Place a sample checkout to populate the analytics.</p>
            <div v-for="order in recent_orders" :key="order.number" class="flex flex-wrap items-center justify-between gap-2 border-t py-3 text-sm first:border-0">
                <div><p class="font-medium">{{ order.number }}</p><p class="text-slate-500">{{ order.customer }}</p></div>
                <div class="flex items-center gap-3"><span class="rounded px-2 py-1 text-xs" :class="statusClass(order.status)">{{ order.status }}</span><span class="font-medium">{{ money(order.total_cents) }}</span></div>
            </div>
        </section>

        <p class="mt-5 text-xs text-slate-500">All payment and sales figures are simulated and originate from stored demo transactions. No real payments have been collected.</p>
    </AppLayout>
</template>
