<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <div class="relative min-h-screen overflow-x-hidden bg-slate-50 text-slate-900">
        <nav class="sticky top-0 z-30 border-b border-slate-200 bg-white/80 backdrop-blur-md">
            <div
                class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6"
            >
                <Link :href="route('consultation.index')" class="flex items-baseline gap-2">
                    <span class="gradient-text text-sub-sm font-extrabold tracking-tight"
                        >siBis</span
                    >
                    <span class="hidden text-caption font-semibold text-slate-500 sm:inline"
                        >Konsultasi</span
                    >
                </Link>

                <div class="flex flex-wrap items-center gap-6">
                    <Link
                        :href="route('consultation.index')"
                        :class="route().current('consultation.*') ? 'nav-link-active' : 'nav-link'"
                        >Konsultasi</Link
                    >
                    <Link
                        :href="route('history.index')"
                        :class="route().current('history.*') ? 'nav-link-active' : 'nav-link'"
                        >Riwayat</Link
                    >
                    <Link href="/artikel" class="nav-link">Artikel</Link>
                    <Link
                        v-if="user?.role === 'admin'"
                        :href="route('admin.dashboard')"
                        :class="route().current('admin.*') ? 'nav-link-active' : 'nav-link'"
                        >Admin</Link
                    >
                </div>

                <div class="flex items-center gap-4">
                    <span class="hidden text-body-sm font-medium text-slate-500 sm:inline">{{
                        user?.name
                    }}</span>
                    <Link :href="route('profile.edit')" class="nav-link">Profil</Link>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="btn-ghost btn-sm"
                        >Keluar</Link
                    >
                </div>
            </div>
        </nav>

        <div
            v-if="page.props.flash?.error || page.props.flash?.success"
            class="mx-auto max-w-7xl px-4 pt-6 sm:px-6"
            role="status"
        >
            <div
                class="flex items-start gap-3 rounded-card border px-5 py-4 shadow-soft"
                :class="
                    page.props.flash?.error
                        ? 'border-red-200 bg-red-50'
                        : 'border-emerald-200 bg-emerald-50'
                "
            >
                <span
                    class="mt-1 h-2 w-2 shrink-0 rounded-full"
                    :class="page.props.flash?.error ? 'bg-red-600' : 'bg-emerald-500'"
                    aria-hidden="true"
                />
                <div>
                    <p
                        class="text-caption font-semibold uppercase tracking-eyebrow"
                        :class="page.props.flash?.error ? 'text-red-600' : 'text-emerald-700'"
                    >
                        {{ page.props.flash?.error ? 'Perhatian' : 'Tersimpan' }}
                    </p>
                    <p class="mt-1 text-body-sm text-slate-700">
                        {{ page.props.flash?.error ?? page.props.flash?.success }}
                    </p>
                </div>
            </div>
        </div>

        <header
            v-if="$slots.header"
            class="relative overflow-hidden border-b border-slate-200 bg-white"
        >
            <!-- Blob atmosfer: memberi kedalaman tanpa mengganggu keterbacaan. -->
            <div
                class="orb -left-24 -top-32 h-[400px] w-[400px] bg-indigo-200 opacity-40"
                aria-hidden="true"
            />
            <div
                class="orb -right-24 -top-40 h-[500px] w-[500px] bg-violet-200 opacity-30"
                aria-hidden="true"
            />
            <div class="relative mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-12">
                <slot name="header" />
            </div>
        </header>

        <main>
            <slot />
        </main>
    </div>
</template>
