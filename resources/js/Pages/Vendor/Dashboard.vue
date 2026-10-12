<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { money, statusClass } from '../../utils';

defineProps({
    store: { type: Object, required: true },
    stats: { type: Object, required: true },
    low_stock: { type: Array, default: () => [] },
    recent_items: { type: Array, default: () => [] },
    notifications: { type: Array, default: () => [] },
});

const ship = (item) => router.patch(`/vendor/order-items/${item.id}/ship`, {}, { preserveScroll: true });
</script>

<template>
    <AppLayout>
        <Head title="Vendor dashboard" />
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold">{{ store.name }}</h1>
                <span class="rounded px-2 py-0.5 text-xs" :class="statusClass(store.status)">{{ store.status }}</span>
            </div>
            <Link href="/vendor/products" class="rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700">Manage products</Link>
        </div>

        <div v-if="store.status !== 'approved'" class="mb-6 rounded border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Your store is <strong>{{ store.status }}</strong>. Products stay hidden from shoppers until an admin approves it.
        </div>

        <div class="mb-8 grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border bg-white p-4"><div class="text-sm text-slate-500">Revenue</div><div class="text-2xl font-bold">{{ money(stats.revenue_cents) }}</div></div>
            <div class="rounded-lg border bg-white p-4"><div class="text-sm text-slate-500">Items to ship</div><div class="text-2xl font-bold">{{ stats.pending_items }}</div></div>
            <div class="rounded-lg border bg-white p-4"><div class="text-sm text-slate-500">Products</div><div class="text-2xl font-bold">{{ stats.products }}</div></div>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <section class="rounded-lg border bg-white p-4 md:col-span-2">
                <h2 class="mb-3 font-semibold">Recent order items</h2>
                <p v-if="!recent_items.length" class="text-sm text-slate-500">No orders yet.</p>
                <div v-for="item in recent_items" :key="item.id" class="flex items-center justify-between border-t py-2 text-sm first:border-0">
                    <div>
                        <div>{{ item.name }} × {{ item.quantity }}</div>
                        <div class="text-xs text-slate-500">{{ item.order_number }} · {{ item.customer }}</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span>{{ money(item.line_cents) }}</span>
                        <button v-if="item.fulfillment_status === 'pending'" class="rounded bg-emerald-600 px-3 py-1 text-xs text-white hover:bg-emerald-700" @click="ship(item)">Mark shipped</button>
                        <span v-else class="rounded px-2 py-0.5 text-xs" :class="statusClass(item.fulfillment_status)">{{ item.fulfillment_status }}</span>
                    </div>
                </div>
            </section>

            <div class="space-y-6">
                <section class="rounded-lg border bg-white p-4">
                    <h2 class="mb-3 font-semibold">Low stock</h2>
                    <p v-if="!low_stock.length" class="text-sm text-slate-500">All good.</p>
                    <div v-for="p in low_stock" :key="p.id" class="flex justify-between py-1 text-sm">
                        <Link :href="`/vendor/products/${p.slug}/edit`" class="text-indigo-600 hover:underline">{{ p.name }}</Link>
                        <span class="text-amber-600">{{ p.stock }} left</span>
                    </div>
                </section>
                <section class="rounded-lg border bg-white p-4">
                    <h2 class="mb-3 font-semibold">Notifications</h2>
                    <p v-if="!notifications.length" class="text-sm text-slate-500">Nothing yet.</p>
                    <p v-for="n in notifications" :key="n.id" class="py-1 text-sm" :class="n.unread ? 'font-medium' : 'text-slate-500'">{{ n.message }}</p>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
