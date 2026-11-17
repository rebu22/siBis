<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    messages: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    summary: { type: Object, default: () => ({ unread: 0, total: 0 }) },
});

function saring(filter) {
    router.get(
        route('admin.messages'),
        { filter: filter || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function ubahBaca(item) {
    router.patch(
        route('admin.messages.update', item.id),
        { read: !item.read_at },
        { preserveScroll: true },
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
        <Head title="Pesan Masuk" />

        <template #header>
            <p class="eyebrow">Admin</p>
            <h1 class="mt-3 text-h-sm font-bold text-slate-900">Pesan masuk</h1>
            <p class="mt-2 max-w-2xl text-body-sm text-slate-500">
                Pesan dari pengguna. Balasan dikirim lewat email terdaftar; di sini
                admin cukup menandai sudah dibaca atau belum.
            </p>
        </template>

        <div class="mx-auto max-w-[1040px] px-6 py-10">
            <dl class="grid gap-px overflow-hidden rounded-card border border-slate-100 bg-slate-100 sm:grid-cols-2">
                <div class="bg-white p-6">
                    <dt class="eyebrow">Belum dibaca</dt>
                    <dd class="mt-2 text-h-md font-bold tabular-nums text-slate-900">
                        {{ summary.unread }}
                    </dd>
                </div>
                <div class="bg-white p-6">
                    <dt class="eyebrow">Total pesan</dt>
                    <dd class="mt-2 text-h-md font-bold tabular-nums text-slate-900">
                        {{ summary.total }}
                    </dd>
                </div>
            </dl>

            <div class="mt-8 flex flex-wrap gap-2" role="group" aria-label="Saring pesan">
                <button
                    type="button"
                    class="btn-ghost btn-sm"
                    :class="{ 'border-indigo-300 text-indigo-600': !filters.filter }"
                    @click="saring('')"
                >
                    Semua
                </button>
                <button
                    type="button"
                    class="btn-ghost btn-sm"
                    :class="{ 'border-indigo-300 text-indigo-600': filters.filter === 'unread' }"
                    @click="saring('unread')"
                >
                    Belum dibaca
                </button>
                <button
                    type="button"
                    class="btn-ghost btn-sm"
                    :class="{ 'border-indigo-300 text-indigo-600': filters.filter === 'read' }"
                    @click="saring('read')"
                >
                    Sudah dibaca
                </button>
            </div>

            <p v-if="!messages.data.length" class="mt-8 text-body-sm text-slate-500">
                Tidak ada pesan pada filter ini.
            </p>

            <ul v-else class="mt-6 space-y-5">
                <li
                    v-for="item in messages.data"
                    :key="item.id"
                    class="rounded-card border border-slate-100 bg-white p-6 shadow-soft"
                    :class="{ 'border-l-4 border-l-indigo-500': !item.read_at }"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-sub-sm font-semibold text-slate-900">
                                {{ item.subject }}
                            </p>
                            <p class="mt-1 text-caption text-slate-500">
                                <template v-if="item.sender">
                                    {{ item.sender.name }} ({{ item.sender.email }})
                                </template>
                                <template v-else>Pengguna terhapus</template>
                                · {{ tanggal(item.created_at) }}
                            </p>
                        </div>
                        <span
                            class="badge"
                            :class="
                                item.read_at
                                    ? 'bg-slate-100 text-slate-600'
                                    : 'bg-indigo-50 text-indigo-700'
                            "
                            >{{ item.read_at ? 'Sudah dibaca' : 'Belum dibaca' }}</span
                        >
                    </div>

                    <p class="mt-4 whitespace-pre-line text-body-sm text-slate-700">
                        {{ item.body }}
                    </p>

                    <div class="mt-5 border-t border-slate-200 pt-4">
                        <button type="button" class="btn-quiet btn-sm" @click="ubahBaca(item)">
                            {{ item.read_at ? 'Tandai belum dibaca' : 'Tandai sudah dibaca' }}
                        </button>
                        <span v-if="item.read_at" class="ml-3 text-caption text-slate-500">
                            Dibaca {{ tanggal(item.read_at) }}
                        </span>
                    </div>
                </li>
            </ul>

            <nav
                v-if="messages.links.length > 3"
                class="mt-8 flex flex-wrap gap-2"
                aria-label="Navigasi halaman"
            >
                <template v-for="link in messages.links" :key="link.label">
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
