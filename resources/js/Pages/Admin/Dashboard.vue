<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    stats: { type: Object, required: true },
    reports: { type: Array, default: () => [] },
});

const LABELS = {
    users: 'Pengguna',
    chats: 'Percakapan',
    messages: 'Pesan',
    reports_open: 'Laporan baru',
    articles: 'Artikel',
};

// Bagian yang UI-nya disiapkan tetapi backend-nya belum dibangun.
const PENDING = [
    'Unggah PDF regulasi ke basis pengetahuan',
    'Tambah dan ubah artikel',
    'Monitoring pembayaran premium',
    'Laporan bug dari pengguna',
    'Pesan pengguna ke admin',
];
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Dasbor Admin" />

        <template #header>
            <p class="eyebrow">Admin</p>
            <h1 class="mt-3 text-h-sm font-bold text-slate-900">Dasbor</h1>
            <p class="mt-2 text-body-sm text-slate-500">
                Ringkasan data siBis. Semua aksi di bawah dicatat pada audit log.
            </p>
        </template>

        <div class="mx-auto max-w-[1040px] px-6 py-10">
            <dl class="grid gap-4 sm:grid-cols-3 lg:grid-cols-5">
                <div
                    v-for="(value, key) in stats"
                    :key="key"
                    class="rounded-card border border-slate-100 bg-white p-6 shadow-soft transition-all duration-200 hover:-translate-y-1 hover:shadow-soft-hover"
                >
                    <span
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-50 text-indigo-600"
                        aria-hidden="true"
                    >
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M3 3v18h18" />
                            <path d="M7 15v-4" />
                            <path d="M12 15V7" />
                            <path d="M17 15v-6" />
                        </svg>
                    </span>
                    <dt class="eyebrow mt-4">{{ LABELS[key] ?? key }}</dt>
                    <dd class="mt-2 text-h-md font-bold text-slate-900">{{ value }}</dd>
                </div>
            </dl>

            <section class="mt-12">
                <p class="eyebrow">Laporan jawaban terbaru</p>
                <p v-if="!reports.length" class="mt-4 text-body-sm text-slate-500">
                    Belum ada laporan masuk.
                </p>

                <ul
                    v-else
                    class="mt-6 divide-y divide-slate-200 rounded-card border border-slate-100 bg-white shadow-soft"
                >
                    <li v-for="item in reports" :key="item.id" class="p-6">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="badge">{{ item.reason }}</span>
                            <span class="text-caption text-slate-500">status: {{ item.status }}</span>
                        </div>
                        <p class="mt-3 line-clamp-2 text-body-sm text-slate-700">
                            {{ item.message?.body }}
                        </p>
                        <p v-if="item.note" class="mt-2 text-caption text-slate-500">
                            Catatan: {{ item.note }}
                        </p>
                    </li>
                </ul>
            </section>

            <section class="mt-12 rounded-card border border-slate-100 bg-white p-8 shadow-soft">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sub-sm font-semibold text-slate-900">
                        Belum aktif
                    </p>
                    <span class="badge">Tahap berikutnya</span>
                </div>
                <p class="mt-3 max-w-2xl text-body-sm text-slate-500">
                    Tampilan untuk bagian ini disiapkan, tetapi backend-nya belum
                    dibangun. Tidak ada tombol yang bisa menyesatkan pengguna.
                </p>
                <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                    <li
                        v-for="label in PENDING"
                        :key="label"
                        class="border-t border-slate-200 pt-3 text-body-sm text-slate-500"
                    >
                        {{ label }}
                    </li>
                </ul>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
