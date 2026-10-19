<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    articles: { type: Array, default: () => [] },
});

const company = computed(() => usePage().props.company);
const user = computed(() => usePage().props.auth.user);
const ctaHref = computed(() =>
    user.value ? route('consultation.index') : route('register'),
);
</script>

<template>
    <PublicLayout>
        <Head title="Konsultasi Hukum Bisnis Berbasis AI" />

        <!-- Hero: dua kolom, teks dulu, mockup isometrik di kanan -->
        <section class="relative overflow-hidden bg-gradient-to-b from-indigo-100 to-violet-100">
            <div
                class="orb -left-32 -top-40 h-[500px] w-[500px] bg-indigo-300 opacity-40"
                aria-hidden="true"
            />
            <div
                class="orb -right-40 top-10 h-[600px] w-[600px] bg-violet-300 opacity-30"
                aria-hidden="true"
            />

            <div
                class="relative mx-auto grid max-w-7xl items-center gap-16 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-2 lg:py-24"
            >
                <div>
                    <p class="eyebrow">Pajak · Izin usaha · Badan usaha</p>

                    <h1 class="mt-6 text-h-sm font-extrabold sm:text-h-md lg:text-h-lg">
                        Tanya syarat PT Perorangan.
                        <span class="gradient-text">Jawaban lengkap dengan dasar hukumnya.</span>
                    </h1>

                    <p class="mt-6 max-w-xl text-body text-slate-600">
                        Gratis 5 pertanyaan per hari. Setiap jawaban menyebut dasar hukum, lengkap
                        dengan tautan sumbernya, dan ditandai sebagai materi edukasi.
                    </p>

                    <div class="mt-10 flex flex-wrap items-center gap-4">
                        <Link :href="ctaHref" class="btn-primary">
                            Tanya sekarang
                            <svg
                                class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-1"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M5 12h14m-6-6 6 6-6 6" />
                            </svg>
                        </Link>
                        <Link :href="route('articles.index')" class="btn-ghost">
                            Lihat artikel
                        </Link>
                    </div>

                    <p class="mt-6 text-caption text-slate-500">
                        Tanpa kartu kredit. Nama, email, dan profil usaha tidak ikut dikirim ke
                        model bahasa.
                    </p>
                </div>

                <!-- Mockup produk: membingkai UI asli, bukan ilustrasi -->
                <div class="iso-parent">
                    <div
                        class="iso-card rounded-surface border border-slate-200 bg-white p-4 shadow-soft-hover"
                    >
                        <div class="flex items-center gap-2 px-2 pb-4">
                            <span class="h-2.5 w-2.5 rounded-full bg-slate-200" />
                            <span class="h-2.5 w-2.5 rounded-full bg-slate-200" />
                            <span class="h-2.5 w-2.5 rounded-full bg-slate-200" />
                            <p class="ml-2 text-caption text-slate-500">
                                sibis.test/konsultasi
                            </p>
                        </div>

                        <div class="space-y-4 rounded-card bg-slate-50 p-5">
                            <div class="flex justify-end">
                                <p
                                    class="max-w-[80%] rounded-2xl rounded-br-sm bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-3 text-body-sm font-medium text-white shadow-button"
                                >
                                    Apa syarat mendirikan PT Perorangan?
                                </p>
                            </div>

                            <div
                                class="max-w-[92%] rounded-2xl rounded-bl-sm border border-slate-200 bg-white px-4 py-3 shadow-soft"
                            >
                                <p class="text-body-sm leading-relaxed text-slate-700">
                                    PT Perorangan didirikan oleh satu orang, Warga Negara
                                    Indonesia, melalui pernyataan pendirian yang disahkan sistem
                                    perseroan perorangan.
                                </p>

                                <div class="mt-4 rounded-card bg-indigo-50 p-4">
                                    <p class="eyebrow">Referensi</p>
                                    <ol class="mt-3 space-y-2 text-caption text-slate-600">
                                        <li>[1] PP No. 8 Tahun 2021 — Perseroan Perorangan</li>
                                        <li>
                                            [2] UU No. 11 Tahun 2020 — Cipta Kerja, Pasal 109A
                                        </li>
                                    </ol>
                                </div>

                                <p class="mt-3 text-caption text-slate-500">
                                    Informasi edukasi awal, bukan nasihat mengikat. Konsultasikan
                                    ke advokat berlisensi.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tiga kartu: klaim spesifik, bukan angka kosong -->
        <section class="bg-slate-50">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:py-24">
                <div class="max-w-2xl">
                    <p class="eyebrow">Cara kerja</p>
                    <h2 class="mt-4 text-h-sm font-bold sm:text-h-md">
                        Jawaban yang bisa Anda periksa sendiri
                    </h2>
                </div>

                <div class="mt-12 grid gap-8 sm:grid-cols-3">
                    <article class="card p-6">
                        <span
                            class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"
                            aria-hidden="true"
                        >
                            <svg
                                class="h-6 w-6"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M4 19.5V6a2 2 0 0 1 2-2h11a1 1 0 0 1 1 1v13" />
                                <path d="M6 17h12v3H6a2 2 0 0 1 0-4z" />
                                <path d="M8.5 8h6M8.5 11h4" />
                            </svg>
                        </span>
                        <h3 class="text-sub-sm font-semibold text-slate-900">
                            Dasar hukum disebut, bukan disiratkan
                        </h3>
                        <p class="mt-4 text-body-sm text-slate-500">
                            Setiap jawaban menutup dengan blok referensi: nama dokumen, nomor, dan
                            tahun. Tanpa rujukan di basis pengetahuan, jawaban dinyatakan
                            terbatas.
                        </p>
                    </article>

                    <article class="card p-6">
                        <span
                            class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"
                            aria-hidden="true"
                        >
                            <svg
                                class="h-6 w-6"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="m10.29 3.86-8.47 14.14A2 2 0 0 0 3.53 21h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                                <path d="M12 9v4M12 17h.01" />
                            </svg>
                        </span>
                        <h3 class="text-sub-sm font-semibold text-slate-900">
                            Batas yang tidak kami sembunyikan
                        </h3>
                        <p class="mt-4 text-body-sm text-slate-500">
                            siBis memberi titik awal, bukan putusan. Untuk dokumen yang mengikat,
                            advokat berlisensi tetap diperlukan.
                        </p>
                    </article>

                    <article class="card p-6">
                        <span
                            class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"
                            aria-hidden="true"
                        >
                            <svg
                                class="h-6 w-6"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />
                                <path d="m9 12 2 2 4-4" />
                            </svg>
                        </span>
                        <h3 class="text-sub-sm font-semibold text-slate-900">
                            Pertanyaan Anda tidak membawa identitas
                        </h3>
                        <p class="mt-4 text-body-sm text-slate-500">
                            Ke model bahasa hanya teks pertanyaan dan riwayat peran yang dikirim.
                            Nama, email, dan profil usaha tidak ikut.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <!-- Profil perusahaan (RF-01) -->
        <section id="layanan" class="border-y border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:py-24">
                <p class="eyebrow">Profil</p>
                <h2 class="mt-4 max-w-2xl text-sub font-bold text-slate-900">
                    {{ company?.name ?? 'siBis' }}
                </h2>

                <div class="mt-12 grid gap-10 sm:grid-cols-3">
                    <div>
                        <p class="eyebrow">Visi</p>
                        <p class="mt-4 text-body-sm text-slate-500">
                            {{ company?.vision ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="eyebrow">Misi</p>
                        <p class="mt-4 text-body-sm text-slate-500">
                            {{ company?.mission ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="eyebrow">Layanan</p>
                        <p class="mt-4 whitespace-pre-line text-body-sm text-slate-500">
                            {{ company?.services ?? '—' }}
                        </p>
                        <p class="mt-4 text-caption text-slate-500">
                            {{ company?.contact_email }} · {{ company?.contact_phone }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Biaya: kuota nyata dari sistem, tanpa harga karangan -->
        <section id="biaya" class="bg-slate-50">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:py-24">
                <div class="max-w-2xl">
                    <p class="eyebrow">Biaya</p>
                    <h2 class="mt-4 text-h-sm font-bold sm:text-h-md">
                        Kuota gratis harian,
                        <span class="gradient-text">tanpa kartu kredit</span>
                    </h2>
                </div>

                <div class="mt-12 grid gap-8 lg:grid-cols-2">
                    <div class="card p-8">
                        <p class="text-sub-sm font-semibold text-slate-900">Gratis</p>
                        <p class="mt-6 text-h-md font-extrabold text-slate-900">5</p>
                        <p class="text-body-sm text-slate-500">pertanyaan per hari</p>
                        <ul class="mt-8 space-y-3 text-body-sm text-slate-500">
                            <li class="flex gap-3">
                                <span
                                    class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600"
                                    aria-hidden="true"
                                />
                                Blok referensi di setiap jawaban
                            </li>
                            <li class="flex gap-3">
                                <span
                                    class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600"
                                    aria-hidden="true"
                                />
                                Riwayat percakapan tersimpan
                            </li>
                            <li class="flex gap-3">
                                <span
                                    class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600"
                                    aria-hidden="true"
                                />
                                Seluruh artikel edukasi
                            </li>
                        </ul>
                        <Link :href="ctaHref" class="btn-primary mt-8 w-full">
                            {{ user ? 'Buka konsultasi' : 'Daftar gratis' }}
                        </Link>
                    </div>

                    <div class="card border-indigo-100 p-8">
                        <div class="flex items-center justify-between gap-4">
                            <p class="text-sub-sm font-semibold text-slate-900">Premium</p>
                            <span class="badge">Tahap berikutnya</span>
                        </div>
                        <p class="mt-6 text-h-md font-extrabold text-slate-900">100</p>
                        <p class="text-body-sm text-slate-500">pertanyaan per hari</p>
                        <ul class="mt-8 space-y-3 text-body-sm text-slate-500">
                            <li class="flex gap-3">
                                <span
                                    class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600"
                                    aria-hidden="true"
                                />
                                Semua yang ada di paket gratis
                            </li>
                            <li class="flex gap-3">
                                <span
                                    class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600"
                                    aria-hidden="true"
                                />
                                Kuota harian lebih besar untuk konsultasi intensif
                            </li>
                            <li class="flex gap-3">
                                <span
                                    class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600"
                                    aria-hidden="true"
                                />
                                Pembayaran QRIS dan virtual account
                            </li>
                        </ul>
                        <button type="button" class="btn-ghost mt-8 w-full" disabled>
                            Segera hadir
                        </button>
                        <p class="mt-3 text-caption text-slate-500">
                            Pembayaran belum diaktifkan. Hubungi {{ company?.contact_email }}
                            untuk informasi.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Artikel terbaru -->
        <section class="bg-slate-50">
            <div class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 sm:pb-20 lg:pb-24">
                <div class="flex flex-wrap items-baseline justify-between gap-4">
                    <div>
                        <p class="eyebrow">Artikel terbaru</p>
                        <h2 class="mt-4 text-h-sm font-bold sm:text-h-md">
                            Bacaan singkat sebelum bertanya
                        </h2>
                    </div>
                    <Link :href="route('articles.index')" class="link-quiet">
                        Semua artikel
                        <span aria-hidden="true">→</span>
                    </Link>
                </div>

                <p v-if="!articles.length" class="mt-8 text-body-sm text-slate-500">
                    Belum ada artikel yang dipublikasikan.
                </p>

                <div class="mt-12 grid gap-8 sm:grid-cols-3">
                    <article
                        v-for="article in articles"
                        :key="article.slug"
                        class="card flex flex-col p-6"
                    >
                        <p class="eyebrow">
                            {{ article.category ?? 'Umum' }} · {{ article.published_at }}
                        </p>
                        <h3 class="mt-4 text-sub-sm font-semibold text-slate-900">
                            <Link
                                :href="route('articles.show', article.slug)"
                                class="transition-colors duration-200 hover:text-indigo-600"
                                >{{ article.title }}</Link
                            >
                        </h3>
                        <p class="mt-4 text-body-sm text-slate-500">
                            {{ article.excerpt }}
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <!-- Penutup dramatis: gradient indigo gelap, satu CTA nyata -->
        <section class="bg-gradient-to-br from-indigo-900 to-indigo-950">
            <div
                class="mx-auto flex max-w-7xl flex-col items-start gap-8 px-4 py-16 sm:px-6 sm:py-20 lg:flex-row lg:items-center lg:justify-between lg:py-24"
            >
                <div class="max-w-2xl">
                    <h2 class="text-h-sm font-extrabold text-white sm:text-h-md">
                        Mulai dari satu pertanyaan soal usaha Anda
                    </h2>
                    <p class="mt-5 text-body text-indigo-100">
                        Gratis, tanpa kartu kredit, dan setiap jawaban bisa Anda telusuri sampai
                        ke dokumen hukumnya.
                    </p>
                </div>
                <Link
                    :href="ctaHref"
                    class="inline-flex shrink-0 items-center gap-2 rounded-full bg-white px-6 py-3 text-body-sm font-semibold text-indigo-900 shadow-soft transition-all duration-200 hover:-translate-y-0.5 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-indigo-900"
                >
                    {{ user ? 'Buka konsultasi' : 'Daftar gratis' }}
                </Link>
            </div>
        </section>
    </PublicLayout>
</template>
