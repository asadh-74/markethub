<script setup>
import { reactive, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import Pagination from '../../Components/Pagination.vue';
import { money, statusClass } from '../../utils';

const props = defineProps({
    products: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});
const search = ref(props.filters.q || '');
const stockDraft = reactive({});
watch(() => props.products.data, (rows) => {
    rows.forEach((p) => { stockDraft[p.id] = p.stock; });
}, { immediate: true });
const find = () => router.get('/admin/products', { q: search.value }, { preserveState: true });
const toggle = (p) => router.patch('/admin/products/' + p.slug, { is_active: !p.is_active }, { preserveScroll: true });
const saveStock = (p) => router.patch('/admin/products/' + p.slug, { stock: stockDraft[p.id] }, { preserveScroll: true });
</script>

<template>
    <AppLayout>
        <Head title="Admin products" />
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <Link href="/admin" class="text-sm font-medium text-indigo-600 hover:underline">← Admin overview</Link>
                <h1 class="mt-2 text-2xl font-bold text-slate-900">Product inventory</h1>
                <p class="mt-1 text-sm text-slate-500">Update inventory levels or hide products across every seller.</p>
            </div>
            <Link href="/admin/orders" class="rounded-lg border px-4 py-2 text-sm hover:bg-slate-50">View orders</Link>
        </div>
        <form class="mb-4 flex flex-wrap gap-2" @submit.prevent="find">
            <input v-model="search" type="search" maxlength="100" placeholder="Search products" aria-label="Search products" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm sm:max-w-sm" />
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white">Search</button>
        </form>
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-[730px] w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-4 py-3">Product</th><th class="px-4 py-3">Vendor</th><th class="px-4 py-3">Price</th><th class="px-4 py-3">Stock</th><th class="px-4 py-3">Listing</th><th class="px-4 py-3">Action</th></tr></thead>
                <tbody>
                    <tr v-if="!products.data.length"><td colspan="6" class="p-8 text-center text-slate-500">No matching products.</td></tr>
                    <tr v-for="p in products.data" :key="p.id" class="border-t border-slate-100">
                        <td class="px-4 py-4"><p class="font-medium">{{ p.name }}</p><p class="text-xs text-slate-500">{{ p.category || 'Uncategorized' }}</p></td>
                        <td class="px-4 py-4"><p>{{ p.store }}</p><p class="text-xs text-slate-500">{{ p.store_status }}</p></td>
                        <td class="px-4 py-4">{{ money(p.price_cents) }}</td>
                        <td class="px-4 py-4">
                            <form class="flex items-center gap-2" @submit.prevent="saveStock(p)">
                                <input v-model.number="stockDraft[p.id]" type="number" min="0" max="1000000" class="w-20 rounded border px-2 py-1" :aria-label="'Stock for ' + p.name" />
                                <button class="rounded border border-indigo-200 px-2 py-1 text-xs font-medium text-indigo-700 hover:bg-indigo-50">Save</button>
                            </form>
                        </td>
                        <td class="px-4 py-4"><span class="rounded px-2 py-1 text-xs" :class="p.is_active ? statusClass('approved') : statusClass('suspended')">{{ p.is_active ? 'Active' : 'Hidden' }}</span></td>
                        <td class="px-4 py-4"><button class="rounded px-3 py-2 text-xs font-medium" :class="p.is_active ? 'bg-rose-50 text-rose-700 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'" @click="toggle(p)">{{ p.is_active ? 'Hide' : 'Activate' }}</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mt-5"><Pagination :links="products.links" /></div>
        <p class="mt-4 text-xs text-slate-500">A product from an unapproved vendor remains hidden even if its listing status is active.</p>
    </AppLayout>
</template>
