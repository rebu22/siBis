<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    transactions: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
    summary: {
        type: Object,
        default: () => ({ paid: 0, revenue: 0, pending: 0 }),
    },
});

const LABEL_STATUS = {
    pending: 'Menunggu',
    settlement: 'Lunas',
    capture: 'Lunas',
    expire: 'Kedaluwarsa',
    cancel: 'Dibatalkan',
    deny: 'Ditolak',
    failure: 'Gagal',
    refund: 'Dikembalikan',
};

const rupiah = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

function saring(status) {
    router.get(
        route('admin.transactions'),
        { status: status || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

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
        <Head title="Pembayaran Premium" />

        <template #header>
            <p class="eyebrow">Admin</p>
            <h1 class="mt-3 text-h-sm font-bold text-slate-900">Pembayaran premium</h1>
            <p class="mt-2 max-w-2xl text-body-sm text-slate-500">
                Monitoring transaksi Midtrans: siapa yang membayar, berapa, dan
                statusnya. Aktivasi premium dilakukan otomatis saat lunas.
            </p>
        </template>

        <div class="mx-auto max-w-[1040px] px-6 py-10">
            <dl class="grid gap-px overflow-hidden rounded-card border border-slate-100 bg-slate-100 sm:grid-cols-3">
                <div class="bg-white p-6">
                    <dt class="eyebrow">Pendapatan lunas</dt>
                    <dd class="mt-2 text-h-md font-bold tabular-nums text-slate-900">
                        {{ rupiah.format(summary.revenue) }}
                    </dd>
                </div>
                <div class="bg-white p-6">
                    <dt class="eyebrow">Transaksi lunas</dt>
                    <dd class="mt-2 text-h-md font-bold tabular-nums text-slate-900">
                        {{ summary.paid }}
                    </dd>
                </div>
                <div class="bg-white p-6">
                    <dt class="eyebrow">Menunggu pembayaran</dt>
                    <dd class="mt-2 text-h-md font-bold tabular-nums text-slate-900">
                        {{ summary.pending }}
                    </dd>
                </div>
            </dl>

            <div class="mt-8 flex flex-wrap gap-2" role="group" aria-label="Saring status">
                <button
                    type="button"
                    class="btn-ghost btn-sm"
                    :class="{ 'border-indigo-300 text-indigo-600': !filters.status }"
                    @click="saring('')"
                >
                    Semua
                </button>
                <button
                    v-for="status in statuses"
                    :key="status"
                    type="button"
                    class="btn-ghost btn-sm"
                    :class="{ 'border-indigo-300 text-indigo-600': filters.status === status }"
                    @click="saring(status)"
                >
                    {{ LABEL_STATUS[status] ?? status }}
                </button>
            </div>

            <p v-if="!transactions.data.length" class="mt-8 text-body-sm text-slate-500">
                Tidak ada transaksi pada filter ini.
            </p>

            <div
                v-else
                class="mt-6 overflow-x-auto rounded-card border border-slate-100 bg-white shadow-soft"
            >
                <table class="w-full min-w-[880px] border-collapse text-left">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="eyebrow px-6 py-4 font-semibold">Order</th>
                            <th class="eyebrow px-6 py-4 font-semibold">Pembeli</th>
                            <th class="eyebrow px-6 py-4 font-semibold">Jumlah</th>
                            <th class="eyebrow px-6 py-4 font-semibold">Status</th>
                            <th class="eyebrow px-6 py-4 font-semibold">Metode</th>
                            <th class="eyebrow px-6 py-4 font-semibold">Dibayar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="item in transactions.data" :key="item.id" class="hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <p class="font-mono text-caption text-slate-600">
                                    {{ item.order_id }}
                                </p>
                                <p class="mt-1 text-caption text-slate-400">
                                    {{ tanggal(item.created_at) }}
                                </p>
                            </td>
                            <td class="px-6 py-4 text-body-sm text-slate-700">
                                <template v-if="item.buyer">
                                    <p class="font-semibold text-slate-900">{{ item.buyer.name }}</p>
                                    <p class="text-caption text-slate-500">{{ item.buyer.email }}</p>
                                </template>
                                <template v-else>Pengguna terhapus</template>
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
                                {{ item.paid_at ? tanggal(item.paid_at) : '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <nav
                v-if="transactions.links.length > 3"
                class="mt-8 flex flex-wrap gap-2"
                aria-label="Navigasi halaman"
            >
                <template v-for="link in transactions.links" :key="link.label">
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
