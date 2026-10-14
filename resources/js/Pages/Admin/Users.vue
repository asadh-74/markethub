<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import Pagination from '../../Components/Pagination.vue';

const props = defineProps({
    users: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    current_user_id: { type: Number, required: true },
});

const search = ref(props.filters.q || '');
const roleFilter = ref(props.filters.role || '');
const editing = ref(null);
const change = useForm({ role: 'admin', password: '' });

watch(() => props.filters, (value) => {
    search.value = value.q || '';
    roleFilter.value = value.role || '';
});

const applyFilters = () => router.get('/admin/users',
    { q: search.value, role: roleFilter.value }, { preserveState: true });

const openRoleChange = (user) => {
    editing.value = user;
    change.role = user.role === 'admin' ? 'customer' : 'admin';
    change.password = '';
    change.clearErrors();
};
const cancelRoleChange = () => {
    editing.value = null;
    change.reset('password');
    change.clearErrors();
};
const saveRole = () => {
    if (!editing.value) return;
    change.patch('/admin/users/' + editing.value.id + '/role', {
        preserveScroll: true,
        onSuccess: cancelRoleChange,
        onFinish: () => change.reset('password'),
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Admin — users" />
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <Link href="/admin" class="text-sm font-medium text-indigo-600 hover:underline">← Admin overview</Link>
                <h1 class="mt-2 text-2xl font-bold">User management</h1>
                <p class="mt-1 text-sm text-slate-500">View customers and vendors, review accounts, and securely manage administrator access.</p>
            </div>
            <Link href="/register" class="rounded-lg border border-indigo-200 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50">Customer &amp; vendor registration</Link>
        </div>

        <div class="mb-5 rounded-lg border border-indigo-100 bg-indigo-50 p-4 text-sm text-indigo-900">
            New customers and vendors create their own passwords on the registration page. Only an existing administrator can grant administrator access. No passwords or database credentials are shown here.
        </div>

        <form class="mb-5 flex flex-wrap gap-2" @submit.prevent="applyFilters">
            <input v-model="search" type="search" maxlength="100" placeholder="Search name or email"
                aria-label="Search accounts" class="min-w-0 flex-1 rounded-lg border px-3 py-2 text-sm" />
            <select v-model="roleFilter" aria-label="Filter role" class="rounded-lg border px-3 py-2 text-sm">
                <option value="">All roles</option>
                <option value="customer">Customers</option>
                <option value="vendor">Vendors</option>
                <option value="admin">Admins</option>
            </select>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white">Search</button>
        </form>

        <div class="overflow-x-auto rounded-xl border bg-white shadow-sm">
            <table class="min-w-[720px] w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">User</th><th class="px-4 py-3">Role</th><th class="px-4 py-3">Store</th><th class="px-4 py-3">Orders</th><th class="px-4 py-3">Joined</th><th class="px-4 py-3 text-right">Action</th></tr>
                </thead>
                <tbody>
                    <tr v-if="!users.data.length"><td colspan="6" class="p-8 text-center text-slate-500">No matching accounts.</td></tr>
                    <tr v-for="user in users.data" :key="user.id" class="border-t border-slate-100">
                        <td class="px-4 py-3"><div class="font-medium">{{ user.name }}</div><div class="text-xs text-slate-500">{{ user.email }}</div></td>
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-1 text-xs"
                            :class="user.role === 'admin' ? 'bg-indigo-100 text-indigo-700' : user.role === 'vendor' ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-700'">{{ user.role }}</span></td>
                        <td class="px-4 py-3">{{ user.store || '—' }}</td>
                        <td class="px-4 py-3">{{ user.orders_count }}</td>
                        <td class="px-4 py-3">{{ user.joined_at }}</td>
                        <td class="px-4 py-3 text-right">
                            <button v-if="user.role !== 'vendor' && user.id !== current_user_id" class="rounded border border-indigo-200 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-50" @click="openRoleChange(user)">
                                {{ user.role === 'admin' ? 'Revoke admin' : 'Grant admin' }}
                            </button>
                            <span v-else class="text-xs text-slate-400">{{ user.id === current_user_id ? 'Your account' : 'Vendor' }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mt-5"><Pagination :links="users.links" /></div>

        <div v-if="editing" class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-5">
            <h2 class="text-lg font-semibold">Confirm administrator permission change</h2>
            <p class="mt-2 text-sm">Change <strong>{{ editing.name }}</strong> ({{ editing.email }}) to <strong>{{ change.role }}</strong>?</p>
            <p class="mt-1 text-xs text-amber-800">Administrator accounts can access marketplace users and orders. Only grant this role to someone you trust.</p>
            <form class="mt-4 flex flex-wrap items-end gap-3" @submit.prevent="saveRole">
                <div class="min-w-[220px] flex-1">
                    <label for="admin-current-password" class="mb-1 block text-sm font-medium">Enter your own admin password to confirm</label>
                    <input id="admin-current-password" v-model="change.password" type="password" autocomplete="current-password" required class="w-full rounded-lg border px-3 py-2" />
                    <p v-if="change.errors.password" class="mt-1 text-sm text-rose-600">{{ change.errors.password }}</p>
                </div>
                <button :disabled="change.processing" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">Confirm role</button>
                <button type="button" class="rounded-lg border px-4 py-2 text-sm" @click="cancelRoleChange">Cancel</button>
            </form>
        </div>
        <p class="mt-4 text-xs text-slate-500">For your security, vendor roles cannot be changed through this screen because they own stores and existing order records.</p>
    </AppLayout>
</template>
