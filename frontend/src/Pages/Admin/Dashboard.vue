<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: { type: Object, required: true },
    reports: { type: Array, default: () => [] },
});

const LABELS = {
    users: 'Pengguna',
    chats: 'Percakapan',
    messages: 'Pesan chat',
    reports_open: 'Laporan jawaban',
    regulations: 'Kebijakan',
    bugs_open: 'Bug baru',
    messages_unread: 'Pesan belum dibaca',
    premium_users: 'Pengguna premium',
    transactions_pending: 'Pembayaran tertunda',
};
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
            <dl class="grid gap-4 sm:grid-cols-3 lg:grid-cols-4">
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
                <p class="eyebrow">Kelola</p>
                <div class="mt-4 grid gap-px overflow-hidden rounded-card border border-slate-100 bg-slate-100 sm:grid-cols-2">
                    <Link
                        :href="route('admin.users')"
                        class="group bg-white p-6 transition-colors duration-200 hover:bg-slate-50"
                    >
                        <p class="text-sub-sm font-semibold text-slate-900 group-hover:text-indigo-600">
                            Pengguna
                        </p>
                        <p class="mt-2 text-body-sm text-slate-500">
                            Paket, kuota hari ini, dan status verifikasi tiap akun.
                        </p>
                    </Link>
                    <Link
                        :href="route('admin.regulations')"
                        class="group bg-white p-6 transition-colors duration-200 hover:bg-slate-50"
                    >
                        <p class="text-sub-sm font-semibold text-slate-900 group-hover:text-indigo-600">
                            Kebijakan
                        </p>
                        <p class="mt-2 text-body-sm text-slate-500">
                            Unggah PDF regulasi: tampil di halaman publik dan jadi rujukan chatbot.
                        </p>
                    </Link>
                    <Link
                        :href="route('admin.profile.edit')"
                        class="group bg-white p-6 transition-colors duration-200 hover:bg-slate-50"
                    >
                        <p class="text-sub-sm font-semibold text-slate-900 group-hover:text-indigo-600">
                            Profil perusahaan
                        </p>
                        <p class="mt-2 text-body-sm text-slate-500">
                            Visi, misi, layanan, dan kontak yang tampil di halaman publik.
                        </p>
                    </Link>
                    <Link
                        :href="route('admin.reports')"
                        class="group bg-white p-6 transition-colors duration-200 hover:bg-slate-50"
                    >
                        <p class="text-sub-sm font-semibold text-slate-900 group-hover:text-indigo-600">
                            Laporan jawaban
                        </p>
                        <p class="mt-2 text-body-sm text-slate-500">
                            Tinjau jawaban yang ditandai tidak akurat dan ubah statusnya.
                        </p>
                    </Link>
                    <Link
                        :href="route('admin.bugs')"
                        class="group bg-white p-6 transition-colors duration-200 hover:bg-slate-50"
                    >
                        <p class="text-sub-sm font-semibold text-slate-900 group-hover:text-indigo-600">
                            Laporan bug
                        </p>
                        <p class="mt-2 text-body-sm text-slate-500">
                            Kendala teknis dari pengguna, lengkap dengan screenshot.
                        </p>
                    </Link>
                    <Link
                        :href="route('admin.messages')"
                        class="group bg-white p-6 transition-colors duration-200 hover:bg-slate-50"
                    >
                        <p class="text-sub-sm font-semibold text-slate-900 group-hover:text-indigo-600">
                            Pesan masuk
                        </p>
                        <p class="mt-2 text-body-sm text-slate-500">
                            Pesan pengguna ke admin; tandai sudah dibaca setelah ditangani.
                        </p>
                    </Link>
                    <Link
                        :href="route('admin.transactions')"
                        class="group bg-white p-6 transition-colors duration-200 hover:bg-slate-50"
                    >
                        <p class="text-sub-sm font-semibold text-slate-900 group-hover:text-indigo-600">
                            Pembayaran premium
                        </p>
                        <p class="mt-2 text-body-sm text-slate-500">
                            Transaksi Midtrans, status pembayaran, dan pendapatan lunas.
                        </p>
                    </Link>
                    <Link
                        :href="route('admin.audit')"
                        class="group bg-white p-6 transition-colors duration-200 hover:bg-slate-50"
                    >
                        <p class="text-sub-sm font-semibold text-slate-900 group-hover:text-indigo-600">
                            Audit log
                        </p>
                        <p class="mt-2 text-body-sm text-slate-500">
                            Riwayat perubahan konten: siapa, kapan, dan apa yang diubah.
                        </p>
                    </Link>
                </div>
            </section>

            <section class="mt-12">
                <div class="flex flex-wrap items-baseline justify-between gap-3">
                    <p class="eyebrow">Laporan jawaban terbaru</p>
                    <Link :href="route('admin.reports')" class="link-quiet text-body-sm">
                        Lihat semua
                        <span aria-hidden="true">→</span>
                    </Link>
                </div>
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
        </div>
    </AuthenticatedLayout>
</template>
