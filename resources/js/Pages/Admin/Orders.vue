<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import Pagination from '../../Components/Pagination.vue';
import { money, statusClass } from '../../utils';

defineProps({ orders: { type: Object, required: true } });
const ship = (item) => {
    if (confirm('Mark this item as shipped?')) {
        router.patch('/admin/order-items/' + item.id + '/ship', {}, { preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Admin orders" />
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <Link href="/admin" class="text-sm font-medium text-indigo-600 hover:underline">← Admin overview</Link>
                <h1 class="mt-2 text-2xl font-bold text-slate-900">Manage orders</h1>
                <p class="mt-1 text-sm text-slate-500">Review marketplace orders and track fulfillment across sellers.</p>
            </div>
            <Link href="/admin/products" class="rounded-lg border px-4 py-2 text-sm hover:bg-slate-50">Manage products</Link>
        </div>
        <div v-if="!orders.data.length" class="rounded-xl border bg-white p-10 text-center text-slate-500">No orders have been placed yet.</div>
        <section v-for="order in orders.data" :key="order.number" class="mb-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b pb-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ order.number }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ order.customer }} · {{ order.customer_email }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ order.created_at }} · {{ order.shipping_name }}, {{ order.shipping_city }}</p>
                    <p class="text-xs text-slate-500">{{ order.shipping_address }} · {{ order.shipping_phone }}</p>
                </div>
                <div class="text-right"><span class="rounded-full px-2 py-1 text-xs" :class="statusClass(order.status)">{{ order.status }}</span><p class="mt-2 font-semibold">{{ money(order.total_cents) }}</p></div>
            </div>
            <div v-for="item in order.items" :key="item.id" class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 py-3 text-sm last:border-0">
                <div><p class="font-medium">{{ item.name }} × {{ item.quantity }}</p><p class="text-xs text-slate-500">Sold by {{ item.store }} · {{ money(item.line_cents) }}</p></div>
                <div class="flex items-center gap-3">
                    <span class="rounded px-2 py-1 text-xs" :class="statusClass(item.fulfillment_status)">{{ item.fulfillment_status }}</span>
                    <button v-if="item.fulfillment_status === 'pending' && order.status !== 'pending'" class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-700" @click="ship(item)">Mark shipped</button>
                </div>
            </div>
        </section>
        <div class="mt-5"><Pagination :links="orders.links" /></div>
        <p class="mt-4 text-xs text-slate-500">Portfolio checkout is simulated. Paid labels indicate successful demo transactions, not money collected.</p>
    </AppLayout>
</template>
