<script setup>
import { Link, router } from '@inertiajs/vue3';
import { money } from '../utils';

const props = defineProps({ product: { type: Object, required: true } });

const addToCart = () =>
    router.post(`/cart/${props.product.slug}`, { quantity: 1 }, { preserveScroll: true });
</script>

<template>
    <div class="flex flex-col overflow-hidden rounded-lg border bg-white">
        <Link :href="`/products/${product.slug}`" class="flex h-32 items-center justify-center bg-gradient-to-br from-indigo-100 to-sky-100 text-4xl font-bold text-indigo-400">
            {{ product.name.charAt(0) }}
        </Link>
        <div class="flex flex-1 flex-col p-4">
            <Link :href="`/products/${product.slug}`" class="font-medium hover:text-indigo-600">{{ product.name }}</Link>
            <p class="mt-1 text-xs text-slate-500">
                {{ product.store }}<span v-if="product.category"> · {{ product.category }}</span>
            </p>
            <div class="mt-auto flex items-center justify-between pt-4">
                <span class="font-semibold">{{ money(product.price_cents) }}</span>
                <button
                    v-if="product.stock > 0"
                    class="rounded bg-indigo-600 px-3 py-1 text-sm text-white hover:bg-indigo-700"
                    @click="addToCart"
                >Add</button>
                <span v-else class="text-xs text-red-600">Out of stock</span>
            </div>
            <p v-if="product.stock > 0 && product.stock <= 5" class="mt-1 text-xs text-amber-600">Only {{ product.stock }} left</p>
        </div>
    </div>
</template>
