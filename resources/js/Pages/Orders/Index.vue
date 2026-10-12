<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import Pagination from '../../Components/Pagination.vue';
import { money, statusClass } from '../../utils';

defineProps({ orders: { type: Object, required: true } });
</script>

<template>
    <AppLayout>
        <Head title="My orders" />
        <h1 class="mb-4 text-2xl font-semibold">My orders</h1>

        <p v-if="!orders.data.length" class="rounded-lg border bg-white p-8 text-center text-slate-500">
            No orders yet. <Link href="/shop" class="text-indigo-600">Start shopping</Link>
        </p>

        <div v-else class="overflow-hidden rounded-lg border bg-white">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr><th class="px-4 py-2">Order</th><th class="px-4 py-2">Date</th><th class="px-4 py-2">Status</th><th class="px-4 py-2 text-right">Total</th></tr>
                </thead>
                <tbody>
                    <tr v-for="o in orders.data" :key="o.number" class="border-t">
                        <td class="px-4 py-2"><Link :href="`/orders/${o.number}`" class="text-indigo-600 hover:underline">{{ o.number }}</Link></td>
                        <td class="px-4 py-2">{{ o.created_at }}</td>
                        <td class="px-4 py-2"><span class="rounded px-2 py-0.5 text-xs" :class="statusClass(o.status)">{{ o.status }}</span></td>
                        <td class="px-4 py-2 text-right">{{ money(o.total_cents) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Pagination :links="orders.links" />
    </AppLayout>
</template>
