<script setup>
import { reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import ProductCard from '../../Components/ProductCard.vue';
import Pagination from '../../Components/Pagination.vue';

const props = defineProps({
    products: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
});

const form = reactive({
    q: props.filters.q || '',
    category: props.filters.category || '',
    min: props.filters.min || '',
    max: props.filters.max || '',
    sort: props.filters.sort || 'newest',
});

const apply = () => {
    const params = Object.fromEntries(Object.entries(form).filter(([, v]) => v !== '' && v !== null));
    router.get('/shop', params, { preserveState: true, replace: true });
};
const reset = () => {
    Object.assign(form, { q: '', category: '', min: '', max: '', sort: 'newest' });
    router.get('/shop', {}, { replace: true });
};
</script>

<template>
    <AppLayout>
        <Head title="Shop" />
        <form class="mb-6 grid gap-3 rounded-lg border bg-white p-4 md:grid-cols-6" @submit.prevent="apply">
            <input v-model="form.q" placeholder="Search products..." class="rounded border px-3 py-2 md:col-span-2" />
            <select v-model="form.category" class="rounded border px-3 py-2">
                <option value="">All categories</option>
                <option v-for="c in categories" :key="c.id" :value="c.slug">{{ c.name }}</option>
            </select>
            <input v-model="form.min" type="number" min="0" placeholder="Min Rs" class="rounded border px-3 py-2" />
            <input v-model="form.max" type="number" min="0" placeholder="Max Rs" class="rounded border px-3 py-2" />
            <select v-model="form.sort" class="rounded border px-3 py-2">
                <option value="newest">Newest</option>
                <option value="price_asc">Price: low to high</option>
                <option value="price_desc">Price: high to low</option>
            </select>
            <div class="flex gap-2 md:col-span-6">
                <button class="rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700">Apply</button>
                <button type="button" class="rounded border px-4 py-2 text-sm hover:bg-slate-100" @click="reset">Reset</button>
            </div>
        </form>

        <div v-if="products.data.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <ProductCard v-for="p in products.data" :key="p.id" :product="p" />
        </div>
        <p v-else class="py-12 text-center text-slate-500">No products match your filters.</p>

        <Pagination :links="products.links" />
    </AppLayout>
</template>
