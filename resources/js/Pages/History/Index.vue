<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ sessions: { type: Object, required: true } });
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Riwayat Konsultasi" />

        <template #header>
            <p class="eyebrow">Riwayat</p>
            <h1 class="mt-3 text-h-sm font-bold text-slate-900">
                Percakapan tersimpan
            </h1>
            <p class="mt-2 max-w-2xl text-body-sm text-slate-500">
                Isi percakapan diperlakukan sebagai data pribadi (UU No. 27 Tahun
                2022) dan tidak dibagikan ke pihak lain.
            </p>
        </template>

        <div class="mx-auto max-w-[880px] px-6 py-10">
            <p v-if="!sessions.data.length" class="text-body-sm text-slate-500">
                Belum ada percakapan tersimpan.
            </p>

            <ul v-else class="space-y-4">
                <li
                    v-for="session in sessions.data"
                    :key="session.id"
                    class="card flex flex-wrap items-center justify-between gap-4 p-6"
                >
                    <div>
                        <Link
                            :href="route('history.show', session.id)"
                            class="rounded text-sub-sm font-semibold text-slate-900 transition-colors duration-200 hover:text-indigo-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"
                        >
                            {{ session.title ?? 'Percakapan tanpa judul' }}
                        </Link>
                        <p class="mt-2 text-caption text-slate-500">
                            {{ session.messages_count }} pesan · {{ session.updated_at }}
                        </p>
                    </div>
                    <Link
                        :href="route('history.show', session.id)"
                        class="btn-ghost btn-sm min-h-[44px]"
                        >Buka</Link
                    >
                </li>
            </ul>

            <nav
                v-if="sessions.links.length > 3"
                class="mt-10 flex flex-wrap gap-2"
                aria-label="Navigasi halaman"
            >
                <template v-for="link in sessions.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="inline-flex min-h-[44px] items-center rounded-full border px-4 py-2 text-caption font-semibold transition-all duration-200"
                        :class="
                            link.active
                                ? 'border-transparent bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-button'
                                : 'border-slate-200 bg-white text-slate-600 hover:border-indigo-300 hover:text-indigo-600'
                        "
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="inline-flex min-h-[44px] items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-caption text-slate-400"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </div>
    </AuthenticatedLayout>
</template>
