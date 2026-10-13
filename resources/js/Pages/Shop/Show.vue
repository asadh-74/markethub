<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import ProductCard from '../../Components/ProductCard.vue';
import { money } from '../../utils';

const props = defineProps({
    product: { type: Object, required: true },
    related: { type: Array, default: () => [] },
});

const quantity = ref(1);
const processing = ref(false);

const add = () => {
    processing.value = true;
    router.post(`/cart/${props.product.slug}`, { quantity: quantity.value }, {
        preserveScroll: true,
        onFinish: () => (processing.value = false),
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="product.name" />
        <Link href="/shop" class="text-sm text-indigo-600">&larr; Back to shop</Link>

        <div class="mt-4 grid gap-8 md:grid-cols-2">
            <div class="flex h-80 items-center justify-center overflow-hidden rounded-xl bg-gradient-to-br from-indigo-100 to-sky-100 text-7xl font-bold text-indigo-400">
                <img v-if="product.image_url" :src="product.image_url" :alt="product.name" class="h-full w-full object-contain" />
                <span v-else>{{ product.name.charAt(0) }}</span>
            </div>
            <div>
                <h1 class="text-2xl font-semibold">{{ product.name }}</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Sold by {{ product.store }}<span v-if="product.category"> · {{ product.category }}</span>
                </p>
                <p class="mt-4 text-3xl font-bold">{{ money(product.price_cents) }}</p>
                <p class="mt-4 whitespace-pre-line text-slate-600">{{ product.description }}</p>

                <div class="mt-6">
                    <template v-if="product.stock > 0">
                        <p class="mb-2 text-sm" :class="product.stock <= 5 ? 'text-amber-600' : 'text-emerald-600'">
                            {{ product.stock <= 5 ? `Only ${product.stock} left` : 'In stock' }}
                        </p>
                        <div class="flex items-center gap-3">
                            <input v-model.number="quantity" type="number" min="1" :max="Math.min(product.stock, 20)" class="w-20 rounded border px-3 py-2" />
                            <button :disabled="processing" class="rounded bg-indigo-600 px-5 py-2 text-white hover:bg-indigo-700 disabled:opacity-50" @click="add">
                                Add to cart
                            </button>
                        </div>
                        <p v-if="$page.props.errors.quantity" class="mt-2 text-sm text-red-600">{{ $page.props.errors.quantity }}</p>
                    </template>
                    <p v-else class="font-medium text-red-600">Out of stock</p>
                </div>
            </div>
        </div>

        <section v-if="related.length" class="mt-12">
            <h2 class="mb-4 text-lg font-semibold">You may also like</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <ProductCard v-for="p in related" :key="p.id" :product="p" />
            </div>
        </section>
    </AppLayout>
</template>
