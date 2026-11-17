<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const aksi = ref(props.filters.action ?? '');

const LABEL_AKSI = {
    'regulation.create': 'Tambah kebijakan',
    'regulation.update': 'Ubah kebijakan',
    'regulation.reindex': 'Indeks ulang kebijakan',
    'regulation.delete': 'Hapus kebijakan',
    'company_profile.update': 'Ubah profil perusahaan',
    'answer_report.update': 'Tindak lanjut laporan jawaban',
    'bug_report.update': 'Tindak lanjut laporan bug',
};

function cari() {
    router.get(
        route('admin.audit'),
        { action: aksi.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function meta(item) {
    if (!item.meta) return '—';

    return Object.entries(item.meta)
        .map(([kunci, nilai]) => `${kunci}: ${Array.isArray(nilai) ? nilai.join(', ') : nilai}`)
        .join(' · ');
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Audit Log" />

        <template #header>
            <p class="eyebrow">Admin</p>
            <h1 class="mt-3 text-h-sm font-bold text-slate-900">Audit log</h1>
            <p class="mt-2 max-w-2xl text-body-sm text-slate-500">
                Jejak siapa mengubah konten apa dan kapan. Metadata saja — isi
                percakapan pengguna tidak disimpan di sini.
            </p>
        </template>

        <div class="mx-auto max-w-[1040px] px-6 py-10">
            <form class="flex flex-wrap gap-3" @submit.prevent="cari">
                <input
                    v-model="aksi"
                    type="search"
                    maxlength="60"
                    placeholder="Saring aksi, mis. regulation"
                    aria-label="Saring aksi audit"
                    class="input flex-1 sm:min-w-[280px]"
                />
                <button type="submit" class="btn-ghost">Cari</button>
                <Link
                    v-if="filters.action"
                    :href="route('admin.audit')"
                    class="link-quiet self-center text-body-sm"
                    preserve-scroll
                    >Hapus filter</Link
                >
            </form>

            <p v-if="!logs.data.length" class="mt-8 text-body-sm text-slate-500">
                Belum ada aktivitas yang tercatat.
            </p>

            <div
                v-else
                class="mt-6 overflow-x-auto rounded-card border border-slate-100 bg-white shadow-soft"
            >
                <table class="w-full min-w-[820px] border-collapse text-left">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="eyebrow px-6 py-4 font-semibold">Waktu</th>
                            <th class="eyebrow px-6 py-4 font-semibold">Aktor</th>
                            <th class="eyebrow px-6 py-4 font-semibold">Aksi</th>
                            <th class="eyebrow px-6 py-4 font-semibold">Subjek</th>
                            <th class="eyebrow px-6 py-4 font-semibold">Metadata</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="item in logs.data" :key="item.id" class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-6 py-4 text-caption text-slate-500">
                                {{ item.created_at }}
                            </td>
                            <td class="px-6 py-4 text-body-sm text-slate-700">
                                {{ item.actor ?? 'Sistem' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="badge">{{ LABEL_AKSI[item.action] ?? item.action }}</span>
                            </td>
                            <td class="px-6 py-4 text-body-sm text-slate-700">
                                {{ item.subject ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-caption text-slate-500">
                                {{ meta(item) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <nav
                v-if="logs.links.length > 3"
                class="mt-8 flex flex-wrap gap-2"
                aria-label="Navigasi halaman"
            >
                <template v-for="link in logs.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        preserve-scroll
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
