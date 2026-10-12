<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { money, statusClass } from '../../utils';

defineProps({ order: { type: Object, required: true } });
</script>

<template>
    <AppLayout>
        <Head :title="`Order ${order.number}`" />
        <Link href="/orders" class="text-sm text-indigo-600">&larr; All orders</Link>

        <div class="mt-3 flex items-center gap-3">
            <h1 class="text-2xl font-semibold">Order {{ order.number }}</h1>
            <span class="rounded px-2 py-0.5 text-xs" :class="statusClass(order.status)">{{ order.status }}</span>
        </div>
        <p class="text-sm text-slate-500">Placed {{ order.created_at }}</p>

        <div class="mt-6 grid gap-6 md:grid-cols-3">
            <div class="overflow-hidden rounded-lg border bg-white md:col-span-2">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr><th class="px-4 py-2">Item</th><th class="px-4 py-2">Seller</th><th class="px-4 py-2">Qty</th><th class="px-4 py-2">Shipping</th><th class="px-4 py-2 text-right">Total</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in order.items" :key="item.id" class="border-t">
                            <td class="px-4 py-2">{{ item.name }}</td>
                            <td class="px-4 py-2">{{ item.store }}</td>
                            <td class="px-4 py-2">{{ item.quantity }}</td>
                            <td class="px-4 py-2"><span class="rounded px-2 py-0.5 text-xs" :class="statusClass(item.fulfillment_status)">{{ item.fulfillment_status }}</span></td>
                            <td class="px-4 py-2 text-right">{{ money(item.line_cents) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="border-t font-semibold"><td colspan="4" class="px-4 py-2 text-right">Total</td><td class="px-4 py-2 text-right">{{ money(order.total_cents) }}</td></tr>
                    </tfoot>
                </table>
            </div>
            <aside class="h-fit rounded-lg border bg-white p-4 text-sm">
                <h2 class="mb-2 font-semibold">Shipping to</h2>
                <p>{{ order.shipping.name }}</p>
                <p>{{ order.shipping.address }}</p>
                <p>{{ order.shipping.city }}</p>
                <p>{{ order.shipping.phone }}</p>
            </aside>
        </div>
    </AppLayout>
</template>
