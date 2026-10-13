<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    product: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
});

const form = useForm({
    name: props.product?.name ?? '',
    description: props.product?.description ?? '',
    image_url: props.product?.image_url ?? '',
    price: props.product?.price ?? '',
    stock: props.product?.stock ?? 0,
    category_id: props.product?.category_id ?? '',
    is_active: props.product?.is_active ?? true,
});

const submit = () => {
    if (props.product) {
        form.put(`/vendor/products/${props.product.slug}`);
    } else {
        form.post('/vendor/products');
    }
};
</script>

<template>
    <AppLayout>
        <Head :title="product ? 'Edit product' : 'New product'" />
        <Link href="/vendor/products" class="text-sm text-indigo-600">&larr; My products</Link>

        <form class="mt-4 max-w-xl space-y-4 rounded-lg border bg-white p-6" @submit.prevent="submit">
            <h1 class="text-xl font-semibold">{{ product ? 'Edit product' : 'New product' }}</h1>

            <div>
                <label class="mb-1 block text-sm">Name</label>
                <input v-model="form.name" class="w-full rounded border px-3 py-2" />
                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
            </div>
            <div>
                <label class="mb-1 block text-sm">Description</label>
                <textarea v-model="form.description" rows="4" class="w-full rounded border px-3 py-2" />
                <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
            </div>
            <div>
                <label class="mb-1 block text-sm">Product image URL (https://)</label>
                <input v-model="form.image_url" type="url" placeholder="https://images.example.com/product.jpg" class="w-full rounded border px-3 py-2" />
                <p class="mt-1 text-xs text-slate-500">Use a public HTTPS product photo. Image hosting stays independent of Render's ephemeral disk.</p>
                <p v-if="form.errors.image_url" class="mt-1 text-sm text-red-600">{{ form.errors.image_url }}</p>
                <img v-if="form.image_url && /^https?:\/\//.test(form.image_url)" :src="form.image_url" alt="Product preview" class="mt-3 h-32 w-48 rounded-lg border object-cover" />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1 block text-sm">Price (Rs)</label>
                    <input v-model="form.price" type="number" step="0.01" min="0" class="w-full rounded border px-3 py-2" />
                    <p v-if="form.errors.price" class="mt-1 text-sm text-red-600">{{ form.errors.price }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm">Stock</label>
                    <input v-model="form.stock" type="number" min="0" class="w-full rounded border px-3 py-2" />
                    <p v-if="form.errors.stock" class="mt-1 text-sm text-red-600">{{ form.errors.stock }}</p>
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm">Category</label>
                <select v-model="form.category_id" class="w-full rounded border px-3 py-2">
                    <option value="">None</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
            </div>
            <label class="flex items-center gap-2 text-sm"><input v-model="form.is_active" type="checkbox" /> Visible in the shop</label>

            <button :disabled="form.processing" class="rounded bg-indigo-600 px-5 py-2 text-white hover:bg-indigo-700 disabled:opacity-50">
                {{ product ? 'Save changes' : 'Create product' }}
            </button>
        </form>
    </AppLayout>
</template>
