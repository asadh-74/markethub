<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import Pagination from '../../../Components/Pagination.vue';
import { money } from '../../../utils';

defineProps({
    products: { type: Object, required: true },
    store_status: { type: String, default: 'pending' },
});

const remove = (p) => {
    if (confirm(`Delete "${p.name}"?`)) router.delete(`/vendor/products/${p.slug}`, { preserveScroll: true });
};
</script>

<template>
    <AppLayout>
        <Head title="My products" />
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-2xl font-semibold">My products</h1>
            <Link v-if="store_status === 'approved'" href="/vendor/products/create" class="rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700">New product</Link>
        </div>
        <div v-if="store_status !== 'approved'" class="mb-4 rounded border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Your store is awaiting admin approval - you can add products once it is approved.
        </div>

        <div class="overflow-hidden rounded-lg border bg-white">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr><th class="px-4 py-2">Name</th><th class="px-4 py-2">Price</th><th class="px-4 py-2">Stock</th><th class="px-4 py-2">Visible</th><th class="px-4 py-2"></th></tr>
                </thead>
                <tbody>
                    <tr v-if="!products.data.length"><td colspan="5" class="px-4 py-6 text-center text-slate-500">No products yet.</td></tr>
                    <tr v-for="p in products.data" :key="p.id" class="border-t">
                        <td class="px-4 py-2">{{ p.name }}</td>
                        <td class="px-4 py-2">{{ money(p.price_cents) }}</td>
                        <td class="px-4 py-2" :class="p.stock <= 5 ? 'text-amber-600' : ''">{{ p.stock }}</td>
                        <td class="px-4 py-2">{{ p.is_active ? 'Yes' : 'Hidden' }}</td>
                        <td class="space-x-3 px-4 py-2 text-right">
                            <Link :href="`/vendor/products/${p.slug}/edit`" class="text-indigo-600 hover:underline">Edit</Link>
                            <button class="text-red-600 hover:underline" @click="remove(p)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Pagination :links="products.links" />
    </AppLayout>
</template>
