<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: { type: Object, required: true },
    search: { type: String, default: '' },
    summary: { type: Object, required: true },
});

const q = ref(props.search);

function cari() {
    router.get(
        route('admin.users'),
        { q: q.value || undefined },
        { preserveState: true, replace: true },
    );
}

const angka = new Intl.NumberFormat('id-ID');
const ringkas = [
    { kunci: 'total', label: 'Pengguna' },
    { kunci: 'verified', label: 'Terverifikasi' },
    { kunci: 'premium', label: 'Paket premium' },
    { kunci: 'asked_today', label: 'Pertanyaan hari ini' },
];

function tanggal(iso) {
    if (!iso) return '—';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(iso));
}

function persen(user) {
    if (user.quota_limit < 1) return 0;

    return Math.min(100, Math.round((user.quota_used / user.quota_limit) * 100));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Pengguna" />

        <template #header>
            <p class="eyebrow">Admin</p>
            <h1 class="mt-3 text-h-sm font-bold text-slate-900">Pengguna</h1>
            <p class="mt-2 text-body-sm text-slate-500">
                Paket, pemakaian kuota hari ini, dan status verifikasi tiap akun.
            </p>
        </template>

        <div class="mx-auto max-w-[1040px] px-6 py-10">
            <dl class="grid gap-px overflow-hidden rounded-card border border-slate-100 bg-slate-100 sm:grid-cols-4">
                <div v-for="item in ringkas" :key="item.kunci" class="bg-white p-6">
                    <dt class="eyebrow">{{ item.label }}</dt>
                    <dd class="mt-2 text-h-md font-bold tabular-nums text-slate-900">
                        {{ angka.format(summary[item.kunci]) }}
                    </dd>
                </div>
            </dl>

            <form class="mt-8 flex flex-wrap items-end gap-3" @submit.prevent="cari">
                <div class="min-w-[240px] flex-1">
                    <label for="q" class="eyebrow">Cari</label>
                    <input
                        id="q"
                        v-model="q"
                        type="search"
                        class="input mt-2"
                        placeholder="Nama atau email"
                        autocomplete="off"
                    />
                </div>
                <button type="submit" class="btn-ghost btn-sm">Cari</button>
                <Link
                    v-if="search"
                    :href="route('admin.users')"
                    class="link-quiet text-body-sm"
                    preserve-scroll
                    >Hapus filter</Link
                >
            </form>

            <p v-if="!users.data.length" class="mt-8 text-body-sm text-slate-500">
                {{ search ? `Tidak ada akun yang cocok dengan "${search}".` : 'Belum ada akun terdaftar.' }}
            </p>

            <div
                v-else
                class="mt-6 overflow-x-auto rounded-card border border-slate-100 bg-white shadow-soft"
            >
                <table class="w-full min-w-[720px] border-collapse text-left">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="eyebrow px-6 py-4 font-semibold">Pengguna</th>
                            <th class="eyebrow px-6 py-4 font-semibold">Paket</th>
                            <th class="eyebrow px-6 py-4 font-semibold">Kuota hari ini</th>
                            <th class="eyebrow px-6 py-4 font-semibold">Verifikasi</th>
                            <th class="eyebrow px-6 py-4 text-right font-semibold">Percakapan</th>
                            <th class="eyebrow px-6 py-4 text-right font-semibold">Gabung</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="user in users.data" :key="user.id" class="hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <p class="text-sub-sm font-semibold text-slate-900">
                                    {{ user.name }}
                                    <span v-if="user.role === 'admin'" class="badge ml-2">admin</span>
                                </p>
                                <p class="mt-1 text-caption text-slate-500">{{ user.email }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="badge"
                                    :class="user.plan === 'premium' ? 'bg-emerald-50 text-emerald-700' : ''"
                                    >{{ user.plan }}</span
                                >
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-body-sm tabular-nums text-slate-700">
                                    {{ user.quota_used }} / {{ user.quota_limit }}
                                </p>
                                <div
                                    v-if="user.quota_used > 0"
                                    class="mt-2 h-1 w-24 overflow-hidden rounded-full bg-slate-100"
                                >
                                    <div
                                        class="h-1 rounded-full bg-indigo-500"
                                        :style="{ width: `${persen(user)}%` }"
                                    />
                                </div>
                            </td>
                            <td class="px-6 py-4 text-body-sm text-slate-700">
                                <span v-if="user.verified">{{ tanggal(user.verified) }}</span>
                                <span v-else class="text-slate-500">Belum</span>
                            </td>
                            <td class="px-6 py-4 text-right text-body-sm tabular-nums text-slate-700">
                                {{ user.sessions }}
                            </td>
                            <td class="px-6 py-4 text-right text-body-sm text-slate-700">
                                {{ tanggal(user.joined) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <nav
                v-if="users.links.length > 3"
                class="mt-8 flex flex-wrap gap-2"
                aria-label="Navigasi halaman"
            >
                <template v-for="link in users.links" :key="link.label">
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
