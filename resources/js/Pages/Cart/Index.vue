<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { money } from '../../utils';

defineProps({
    items: { type: Array, default: () => [] },
    subtotal_cents: { type: Number, default: 0 },
});

const setQuantity = (item, quantity) =>
    router.patch(`/cart/items/${item.id}`, { quantity: Number(quantity) }, { preserveScroll: true });
const remove = (item) => router.delete(`/cart/items/${item.id}`, { preserveScroll: true });
</script>

<template>
    <AppLayout>
        <Head title="Cart" />
        <h1 class="mb-4 text-2xl font-semibold">Your cart</h1>

        <div v-if="!items.length" class="rounded-lg border bg-white p-8 text-center text-slate-500">
            Your cart is empty. <Link href="/shop" class="text-indigo-600">Browse products</Link>
        </div>

        <div v-else class="grid gap-6 md:grid-cols-3">
            <div class="space-y-3 md:col-span-2">
                <div v-for="item in items" :key="item.id" class="flex items-center justify-between rounded-lg border bg-white p-4">
                    <div>
                        <Link :href="`/products/${item.product.slug}`" class="font-medium hover:text-indigo-600">{{ item.product.name }}</Link>
                        <p class="text-xs text-slate-500">{{ item.product.store }} · {{ money(item.product.price_cents) }} each</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <select :value="item.quantity" class="rounded border px-2 py-1" @change="setQuantity(item, $event.target.value)">
                            <option v-for="n in Math.min(item.product.stock, 20)" :key="n" :value="n">{{ n }}</option>
                        </select>
                        <span class="w-28 text-right font-medium">{{ money(item.line_cents) }}</span>
                        <button class="text-sm text-red-600 hover:underline" @click="remove(item)">Remove</button>
                    </div>
                </div>
                <p v-if="$page.props.errors.quantity" class="text-sm text-red-600">{{ $page.props.errors.quantity }}</p>
            </div>

            <aside class="h-fit rounded-lg border bg-white p-4">
                <h2 class="mb-3 font-semibold">Summary</h2>
                <div class="flex justify-between text-sm"><span>Subtotal</span><span>{{ money(subtotal_cents) }}</span></div>
                <div class="mt-1 flex justify-between text-sm text-slate-500"><span>Shipping</span><span>Free</span></div>
                <div class="mt-3 flex justify-between border-t pt-3 font-semibold"><span>Total</span><span>{{ money(subtotal_cents) }}</span></div>
                <Link href="/checkout" class="mt-4 block rounded bg-indigo-600 py-2 text-center text-white hover:bg-indigo-700">Checkout</Link>
            </aside>
        </div>
    </AppLayout>
</template>
