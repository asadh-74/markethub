<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({ showDemoAccounts: { type: Boolean, default: false } });
const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post('/login', { onFinish: () => form.reset('password') });
const fill = (email) => {
    form.email = email;
    form.password = 'password';
};
</script>

<template>
    <AppLayout>
        <Head title="Log in" />
        <div class="mx-auto max-w-sm rounded-lg border bg-white p-6">
            <h1 class="mb-4 text-xl font-semibold">Log in</h1>
            <form class="space-y-3" @submit.prevent="submit">
                <div>
                    <input v-model="form.email" type="email" placeholder="Email" required autofocus class="w-full rounded border px-3 py-2" />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                </div>
                <input v-model="form.password" type="password" placeholder="Password" required class="w-full rounded border px-3 py-2" />
                <label class="flex items-center gap-2 text-sm"><input v-model="form.remember" type="checkbox" /> Remember me</label>
                <button :disabled="form.processing" class="w-full rounded bg-indigo-600 py-2 text-white hover:bg-indigo-700 disabled:opacity-50">Log in</button>
            </form>
            <p class="mt-4 text-sm text-slate-500">
                No account? <Link href="/register" class="text-indigo-600">Register</Link>
            </p>
            <div v-if="showDemoAccounts" class="mt-4 border-t pt-3 text-xs text-slate-500">
                <p class="mb-1 font-medium">Demo accounts (password: password)</p>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="rounded border px-2 py-1 hover:bg-slate-100" @click="fill('customer@markethub.test')">Customer</button>
                    <button type="button" class="rounded border px-2 py-1 hover:bg-slate-100" @click="fill('vendor1@markethub.test')">Vendor</button>
                    <button type="button" class="rounded border px-2 py-1 hover:bg-slate-100" @click="fill('admin@markethub.test')">Admin</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
