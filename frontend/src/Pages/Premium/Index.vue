<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    plan: { type: String, default: 'free' },
    premiumUntil: { type: String, default: null },
    quota: { type: Object, default: () => ({ remaining: 0, limit: 5 }) },
    price: { type: Number, default: 49000 },
    days: { type: Number, default: 30 },
    configured: { type: Boolean, default: false },
    transactions: { type: Array, default: () => [] },
});

const memproses = ref(false);

function bayar() {
    if (!props.configured || memproses.value) return;

    memproses.value = true;
    router.post(
        route('premium.checkout'),
        {},
        { onFinish: () => (memproses.value = false) },
    );
}

const rupiah = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

const LABEL_STATUS = {
    pending: 'Menunggu pembayaran',
    settlement: 'Lunas',
    capture: 'Lunas',
    expire: 'Kedaluwarsa',
    cancel: 'Dibatalkan',
    deny: 'Ditolak',
    failure: 'Gagal',
    refund: 'Dikembalikan',
};

function tanggal(iso) {
    if (!iso) return '—';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(iso));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Paket Premium" />

        <template #header>
            <p class="eyebrow">Langganan</p>
            <h1 class="mt-3 text-h-sm font-bold text-slate-900">Paket Premium</h1>
            <p class="mt-2 max-w-2xl text-body-sm text-slate-500">
                Naikkan kuota konsultasi dari 5 menjadi 100 pertanyaan per hari.
                Pembayaran diproses Midtrans; paket aktif otomatis setelah pembayaran
                terkonfirmasi.
            </p>
        </template>

        <div class="mx-auto max-w-[1040px] px-6 py-10">
            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-card border border-slate-100 bg-white p-6 shadow-soft sm:p-8">
                    <p class="eyebrow">Paket saat ini</p>
                    <p class="mt-3 text-h-md font-extrabold text-slate-900">
                        {{ plan === 'premium' ? 'Premium' : 'Gratis' }}
                    </p>
                    <dl class="mt-6 space-y-3 text-body-sm">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Sisa kuota hari ini</dt>
                            <dd class="font-semibold tabular-nums text-slate-900">
                                {{ quota.remaining }} / {{ quota.limit }}
                            </dd>
                        </div>
                        <div v-if="plan === 'premium'" class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Aktif sampai</dt>
                            <dd class="font-semibold text-slate-900">
                                {{ premiumUntil ? tanggal(premiumUntil) : 'Tanpa batas' }}
                            </dd>
                        </div>
                    </dl>
                    <p class="mt-6 text-caption text-slate-500">
                        Kuota direset setiap hari. Riwayat konsultasi tetap tersimpan
                        walaupun kuota habis.
                    </p>
                </section>

                <section class="rounded-card border border-indigo-100 bg-white p-6 shadow-soft sm:p-8">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="eyebrow">Premium</p>
                        <span class="badge">{{ days }} hari</span>
                    </div>
                    <p class="mt-3 text-h-md font-extrabold text-slate-900">
                        {{ rupiah.format(price) }}
                    </p>
                    <p class="text-body-sm text-slate-500">per {{ days }} hari</p>

                    <ul class="mt-6 space-y-3 text-body-sm text-slate-500">
                        <li class="flex gap-3">
                            <span
                                class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600"
                                aria-hidden="true"
                            />
                            100 pertanyaan per hari
                        </li>
                        <li class="flex gap-3">
                            <span
                                class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600"
                                aria-hidden="true"
                            />
                            Semua rujukan kebijakan dan riwayat konsultasi
                        </li>
                        <li class="flex gap-3">
                            <span
                                class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600"
                                aria-hidden="true"
                            />
                            Pembayaran QRIS, virtual account, dan e-wallet
                        </li>
                    </ul>

                    <button
                        type="button"
                        class="btn-primary mt-8 w-full"
                        :disabled="!configured || memproses"
                        @click="bayar"
                    >
                        {{ memproses ? 'Menyiapkan pembayaran…' : 'Bayar dengan Midtrans' }}
                    </button>

                    <p v-if="!configured" class="mt-3 text-caption text-amber-700">
                        Pembayaran belum dikonfigurasi. Isi MIDTRANS_SERVER_KEY di
                        environment, lalu coba lagi.
                    </p>
                    <p v-else class="mt-3 text-caption text-slate-500">
                        Kamu akan diarahkan ke halaman pembayaran Midtrans. Setelah
                        selesai, kembali ke siBis untuk melihat statusnya.
                    </p>
                </section>
            </div>

            <section class="mt-12">
                <p class="eyebrow">Riwayat pembayaran</p>

                <p v-if="!transactions.length" class="mt-4 text-body-sm text-slate-500">
                    Belum ada pembayaran. Riwayat akan muncul di sini setelah kamu
                    memulai pembayaran premium.
                </p>

                <div
                    v-else
                    class="mt-4 overflow-x-auto rounded-card border border-slate-100 bg-white shadow-soft"
                >
                    <table class="w-full min-w-[720px] border-collapse text-left">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="eyebrow px-6 py-4 font-semibold">Order</th>
                                <th class="eyebrow px-6 py-4 font-semibold">Jumlah</th>
                                <th class="eyebrow px-6 py-4 font-semibold">Status</th>
                                <th class="eyebrow px-6 py-4 font-semibold">Metode</th>
                                <th class="eyebrow px-6 py-4 font-semibold">Dibuat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-for="item in transactions" :key="item.order_id" class="hover:bg-slate-50">
                                <td class="px-6 py-4 font-mono text-caption text-slate-600">
                                    {{ item.order_id }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-body-sm font-semibold text-slate-900">
                                    {{ rupiah.format(item.amount) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="badge"
                                        :class="{
                                            'bg-amber-50 text-amber-700': item.status === 'pending',
                                            'bg-emerald-50 text-emerald-700': item.paid_at,
                                            'bg-slate-100 text-slate-600':
                                                !item.paid_at && item.status !== 'pending',
                                        }"
                                    >
                                        {{ LABEL_STATUS[item.status] ?? item.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-body-sm text-slate-600">
                                    {{ item.payment_type ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-caption text-slate-500">
                                    {{ tanggal(item.created_at) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
