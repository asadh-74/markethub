<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);
const flash = computed(() => page.props.flash || {});
const cartCount = computed(() => page.props.cartCount || 0);

const logout = () => router.post('/logout');
</script>

<template>
    <div class="min-h-screen">
        <nav class="border-b bg-white">
            <div class="mx-auto flex min-h-14 max-w-6xl flex-wrap items-center justify-between gap-x-4 gap-y-3 px-4 py-3">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                    <Link href="/shop" class="text-lg font-bold text-indigo-600">🛍️ MarketHub</Link>
                    <Link href="/shop" class="text-sm hover:text-indigo-600">Shop</Link>
                    <Link v-if="user" href="/orders" class="text-sm hover:text-indigo-600">My orders</Link>
                    <Link v-if="user?.role === 'vendor'" href="/vendor" class="text-sm hover:text-indigo-600">Vendor</Link>
                    <Link v-if="user?.role === 'admin'" href="/admin" class="text-sm hover:text-indigo-600">Admin</Link>
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                    <Link v-if="user" href="/cart" class="relative hover:text-indigo-600">
                        Cart
                        <span
                            v-if="cartCount"
                            class="ml-1 rounded-full bg-indigo-600 px-2 py-0.5 text-xs text-white"
                        >{{ cartCount }}</span>
                    </Link>
                    <template v-if="user">
                        <span class="text-slate-500">{{ user.name }} ({{ user.role }})</span>
                        <button class="rounded border px-3 py-1 hover:bg-slate-100" @click="logout">Log out</button>
                    </template>
                    <template v-else>
                        <Link href="/login" class="hover:text-indigo-600">Log in</Link>
                        <Link href="/register" class="rounded bg-indigo-600 px-3 py-1 text-white hover:bg-indigo-700">Register</Link>
                    </template>
                </div>
            </div>
        </nav>

        <main class="mx-auto max-w-6xl px-4 py-8">
            <div v-if="flash.success" class="mb-4 rounded border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-800">
                {{ flash.success }}
            </div>
            <div v-if="flash.error" class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">
                {{ flash.error }}
            </div>
            <slot />
        </main>
    </div>
</template>
