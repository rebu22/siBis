<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';

const props = defineProps({
    reports: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => ['baru', 'proses', 'selesai'] },
    summary: { type: Object, default: () => ({}) },
});

const LABEL_STATUS = { baru: 'Baru', proses: 'Proses', selesai: 'Selesai' };

const drafts = reactive({});

function isiDraf(items) {
    items.forEach((item) => {
        if (!drafts[item.id]) {
            drafts[item.id] = { status: item.status, admin_note: item.admin_note ?? '' };
        }
    });
}

isiDraf(props.reports.data);
watch(() => props.reports.data, isiDraf);

function saring(status) {
    router.get(
        route('admin.bugs'),
        { status: status || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function simpan(item) {
    router.patch(route('admin.bugs.update', item.id), drafts[item.id], {
        preserveScroll: true,
    });
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
        <Head title="Laporan Bug" />

        <template #header>
            <p class="eyebrow">Admin</p>
            <h1 class="mt-3 text-h-sm font-bold text-slate-900">Laporan bug</h1>
            <p class="mt-2 max-w-2xl text-body-sm text-slate-500">
                Kendala teknis yang dikirim pengguna. Beri catatan tindak lanjut dan
                ubah statusnya sampai selesai.
            </p>
        </template>

        <div class="mx-auto max-w-[1040px] px-6 py-10">
            <dl class="grid gap-px overflow-hidden rounded-card border border-slate-100 bg-slate-100 sm:grid-cols-3">
                <div v-for="status in statuses" :key="status" class="bg-white p-6">
                    <dt class="eyebrow">{{ LABEL_STATUS[status] ?? status }}</dt>
                    <dd class="mt-2 text-h-md font-bold tabular-nums text-slate-900">
                        {{ summary[status] ?? 0 }}
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

            <p v-if="!reports.data.length" class="mt-8 text-body-sm text-slate-500">
                Tidak ada laporan bug pada filter ini.
            </p>

            <ul v-else class="mt-6 space-y-5">
                <li
                    v-for="item in reports.data"
                    :key="item.id"
                    class="rounded-card border border-slate-100 bg-white p-6 shadow-soft"
                >
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sub-sm font-semibold text-slate-900">
                                {{ item.name ?? 'Tanpa nama' }}
                            </p>
                            <p class="mt-1 text-caption text-slate-500">
                                {{ item.email }} · {{ tanggal(item.created_at) }}
                            </p>
                        </div>
                        <span
                            class="badge"
                            :class="{
                                'bg-red-50 text-red-700': item.status === 'baru',
                                'bg-amber-50 text-amber-700': item.status === 'proses',
                                'bg-emerald-50 text-emerald-700': item.status === 'selesai',
                            }"
                            >{{ LABEL_STATUS[item.status] ?? item.status }}</span
                        >
                    </div>

                    <p class="mt-4 whitespace-pre-line text-body-sm text-slate-700">
                        {{ item.description }}
                    </p>

                    <p v-if="item.screenshot_url" class="mt-3 text-caption">
                        <a
                            :href="item.screenshot_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="link-quiet"
                            >Buka screenshot</a
                        >
                    </p>

                    <form
                        class="mt-6 grid gap-4 border-t border-slate-200 pt-5 sm:grid-cols-[180px_1fr_auto] sm:items-end"
                        @submit.prevent="simpan(item)"
                    >
                        <div>
                            <label :for="`status-${item.id}`" class="eyebrow">Status</label>
                            <select
                                :id="`status-${item.id}`"
                                v-model="drafts[item.id].status"
                                class="input mt-2"
                            >
                                <option v-for="status in statuses" :key="status" :value="status">
                                    {{ LABEL_STATUS[status] ?? status }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label :for="`catatan-${item.id}`" class="eyebrow"
                                >Catatan admin</label
                            >
                            <input
                                :id="`catatan-${item.id}`"
                                v-model="drafts[item.id].admin_note"
                                type="text"
                                class="input mt-2"
                                maxlength="1000"
                                placeholder="Tindak lanjut atau penyebab masalah"
                            />
                        </div>
                        <button type="submit" class="btn-primary btn-sm">Simpan</button>
                    </form>
                </li>
            </ul>

            <nav
                v-if="reports.links.length > 3"
                class="mt-8 flex flex-wrap gap-2"
                aria-label="Navigasi halaman"
            >
                <template v-for="link in reports.links" :key="link.label">
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
