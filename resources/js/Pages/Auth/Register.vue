<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    account_type: 'customer',
    store_name: '',
});
const submit = () => form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <AppLayout>
        <Head title="Register" />
        <div class="mx-auto max-w-sm rounded-lg border bg-white p-6">
            <h1 class="mb-4 text-xl font-semibold">Create account</h1>
            <form class="space-y-3" @submit.prevent="submit">
                <div class="flex gap-4 text-sm">
                    <label class="flex items-center gap-1"><input v-model="form.account_type" type="radio" value="customer" /> I want to buy</label>
                    <label class="flex items-center gap-1"><input v-model="form.account_type" type="radio" value="vendor" /> I want to sell</label>
                </div>
                <div>
                    <input v-model="form.name" placeholder="Full name" required class="w-full rounded border px-3 py-2" />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>
                <div>
                    <input v-model="form.email" type="email" placeholder="Email" required class="w-full rounded border px-3 py-2" />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                </div>
                <div v-if="form.account_type === 'vendor'">
                    <input v-model="form.store_name" placeholder="Store name" class="w-full rounded border px-3 py-2" />
                    <p v-if="form.errors.store_name" class="mt-1 text-sm text-red-600">{{ form.errors.store_name }}</p>
                    <p class="mt-1 text-xs text-slate-500">New stores are reviewed by an admin before products go live.</p>
                </div>
                <div>
                    <input v-model="form.password" type="password" placeholder="Password (min 8)" required class="w-full rounded border px-3 py-2" />
                    <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                </div>
                <input v-model="form.password_confirmation" type="password" placeholder="Confirm password" required class="w-full rounded border px-3 py-2" />
                <button :disabled="form.processing" class="w-full rounded bg-indigo-600 py-2 text-white hover:bg-indigo-700 disabled:opacity-50">Register</button>
            </form>
            <p class="mt-4 text-sm text-slate-500">Have an account? <Link href="/login" class="text-indigo-600">Log in</Link></p>
        </div>
    </AppLayout>
</template>
