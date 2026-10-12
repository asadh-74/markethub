<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { money } from '../../utils';

defineProps({
    items: { type: Array, default: () => [] },
    subtotal_cents: { type: Number, default: 0 },
});

const form = useForm({
    shipping_name: '',
    shipping_address: '',
    shipping_city: '',
    shipping_phone: '',
    card_number: '',
    card_expiry: '',
    card_cvc: '',
});

// Never keep card details around after a failed attempt.
const submit = () => form.post('/checkout', { onError: () => form.reset('card_number', 'card_expiry', 'card_cvc') });
</script>

<template>
    <AppLayout>
        <Head title="Checkout" />
        <h1 class="mb-4 text-2xl font-semibold">Checkout</h1>

        <div v-if="form.errors.checkout" class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">
            {{ form.errors.checkout }}
        </div>

        <form class="grid gap-6 md:grid-cols-3" @submit.prevent="submit">
            <div class="space-y-6 md:col-span-2">
                <section class="rounded-lg border bg-white p-4">
                    <h2 class="mb-3 font-semibold">Shipping</h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <input v-model="form.shipping_name" placeholder="Full name" class="w-full rounded border px-3 py-2" />
                            <p v-if="form.errors.shipping_name" class="mt-1 text-sm text-red-600">{{ form.errors.shipping_name }}</p>
                        </div>
                        <div>
                            <input v-model="form.shipping_phone" placeholder="Phone" class="w-full rounded border px-3 py-2" />
                            <p v-if="form.errors.shipping_phone" class="mt-1 text-sm text-red-600">{{ form.errors.shipping_phone }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <input v-model="form.shipping_address" placeholder="Address" class="w-full rounded border px-3 py-2" />
                            <p v-if="form.errors.shipping_address" class="mt-1 text-sm text-red-600">{{ form.errors.shipping_address }}</p>
                        </div>
                        <div>
                            <input v-model="form.shipping_city" placeholder="City" class="w-full rounded border px-3 py-2" />
                            <p v-if="form.errors.shipping_city" class="mt-1 text-sm text-red-600">{{ form.errors.shipping_city }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg border bg-white p-4">
                    <h2 class="mb-1 font-semibold">Payment (demo gateway)</h2>
                    <p class="mb-3 text-xs text-slate-500">
                        Use <code>4242 4242 4242 4242</code> to succeed or <code>4000 0000 0000 0002</code> to see a declined payment. No real money is used.
                    </p>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="sm:col-span-3">
                            <input v-model="form.card_number" inputmode="numeric" autocomplete="off" placeholder="Card number" class="w-full rounded border px-3 py-2" />
                            <p v-if="form.errors.card_number" class="mt-1 text-sm text-red-600">{{ form.errors.card_number }}</p>
                        </div>
                        <input v-model="form.card_expiry" autocomplete="off" placeholder="MM/YY" class="rounded border px-3 py-2" />
                        <input v-model="form.card_cvc" autocomplete="off" placeholder="CVC" class="rounded border px-3 py-2" />
                    </div>
                </section>
            </div>

            <aside class="h-fit rounded-lg border bg-white p-4">
                <h2 class="mb-3 font-semibold">Order summary</h2>
                <div v-for="item in items" :key="item.id" class="flex justify-between py-1 text-sm">
                    <span>{{ item.name }} × {{ item.quantity }}</span>
                    <span>{{ money(item.line_cents) }}</span>
                </div>
                <div class="mt-3 flex justify-between border-t pt-3 font-semibold"><span>Total</span><span>{{ money(subtotal_cents) }}</span></div>
                <button :disabled="form.processing" class="mt-4 w-full rounded bg-indigo-600 py-2 text-white hover:bg-indigo-700 disabled:opacity-50">
                    {{ form.processing ? 'Processing...' : `Pay ${money(subtotal_cents)}` }}
                </button>
            </aside>
        </form>
    </AppLayout>
</template>
