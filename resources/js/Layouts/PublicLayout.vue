<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const company = computed(() => page.props.company);
</script>

<template>
    <div class="relative min-h-screen overflow-x-hidden bg-slate-50 text-slate-900">
        <Head>
            <meta
                name="description"
                content="siBis: konsultasi hukum bisnis berbasis AI dengan rujukan regulasi resmi untuk pelaku usaha di Indonesia."
            />
        </Head>

        <!-- Top bar tunggal: logo kiri, menu tengah, CTA kanan -->
        <header
            class="sticky top-0 z-30 border-b border-slate-200 bg-white/80 backdrop-blur-md"
        >
            <div
                class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6"
            >
                <Link href="/" class="flex items-baseline gap-2">
                    <span class="gradient-text text-sub-sm font-extrabold tracking-tight"
                        >siBis</span
                    >
                    <span class="hidden text-caption font-semibold text-slate-500 sm:inline"
                        >Hukum bisnis</span
                    >
                </Link>

                <nav class="flex items-center gap-6">
                    <Link href="/artikel" class="nav-link">Artikel</Link>
                    <Link href="/#layanan" class="nav-link">Layanan</Link>
                    <Link href="/#biaya" class="nav-link">Biaya</Link>
                </nav>

                <div class="flex items-center gap-4">
                    <template v-if="user">
                        <Link :href="route('consultation.index')" class="nav-link"
                            >Konsultasi</Link
                        >
                        <Link :href="route('history.index')" class="nav-link">Riwayat</Link>
                        <Link
                            v-if="user.role === 'admin'"
                            :href="route('admin.dashboard')"
                            class="nav-link"
                            >Admin</Link
                        >
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="btn-ghost btn-sm"
                            >Keluar</Link
                        >
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="nav-link">Masuk</Link>
                        <Link :href="route('register')" class="btn-primary btn-sm"
                            >Daftar gratis</Link
                        >
                    </template>
                </div>
            </div>
        </header>

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

        <main>
            <slot />
        </main>

        <footer class="mt-16 border-t border-slate-200 bg-white sm:mt-24">
            <div
                class="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:grid-cols-3 sm:px-6 lg:py-24"
            >
                <div>
                    <p class="gradient-text text-sub-sm font-extrabold">siBis</p>
                    <p class="mt-4 max-w-xs text-body-sm text-slate-500">
                        Informasi edukasi awal, bukan nasihat mengikat. Konsultasikan ke advokat
                        berlisensi.
                    </p>
                    <p class="mt-4 max-w-xs text-caption text-slate-500">
                        Data percakapan diperlakukan sebagai data pribadi (UU No. 27 Tahun 2022).
                    </p>
                </div>

                <div>
                    <p class="text-body-sm font-semibold text-slate-900">Navigasi</p>
                    <ul class="mt-4 space-y-3">
                        <li>
                            <Link
                                href="/artikel"
                                class="text-body-sm text-slate-500 transition-colors duration-200 hover:text-indigo-600"
                                >Artikel</Link
                            >
                        </li>
                        <li>
                            <Link
                                :href="route('consultation.index')"
                                class="text-body-sm text-slate-500 transition-colors duration-200 hover:text-indigo-600"
                                >Konsultasi</Link
                            >
                        </li>
                        <li>
                            <Link
                                :href="route('register')"
                                class="text-body-sm text-slate-500 transition-colors duration-200 hover:text-indigo-600"
                                >Daftar</Link
                            >
                        </li>
                    </ul>
                </div>

                <div>
                    <p class="text-body-sm font-semibold text-slate-900">Kontak</p>
                    <ul class="mt-4 space-y-3 text-body-sm text-slate-500">
                        <li>{{ company?.contact_email ?? '—' }}</li>
                        <li>{{ company?.contact_phone ?? '—' }}</li>
                        <li>{{ company?.address ?? '—' }}</li>
                    </ul>
                </div>
            </div>

            <div class="mx-auto max-w-7xl border-t border-slate-200 px-4 py-6 sm:px-6">
                <p class="text-caption text-slate-500">
                    © {{ new Date().getFullYear() }} siBis — Sistem Informasi Konsultasi Hukum
                    Bisnis Berbasis AI.
                </p>
            </div>
        </footer>

        <!-- Ikon chatbot kanan-bawah (wajib SRS 3.1) -->
        <Link
            :href="user ? route('consultation.index') : route('login')"
            class="btn-primary fixed bottom-6 right-6 z-40 shadow-button"
            aria-label="Buka konsultasi AI"
        >
            <svg
                class="h-5 w-5"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <path d="M8 10h8M8 14h5m-9 6V6a2 2 0 012-2h12a2 2 0 012 2v9a2 2 0 01-2 2H7l-3 3z" />
            </svg>
            Tanya AI
        </Link>
    </div>
</template>
