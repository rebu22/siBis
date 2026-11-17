<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

defineProps({
    verified_at: { type: String, default: null },
    masked_email: { type: String, required: true },
});

const form = useForm({ code: '' });

const sendCode = () =>
    router.post(route('admin.2fa.send'), {}, { preserveScroll: true });

const submit = () =>
    form.post(route('admin.2fa.verify'), { onFinish: () => form.reset('code') });
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Verifikasi Dua Faktor" />

        <template #header>
            <p class="eyebrow">Admin · keamanan</p>
            <h1 class="mt-3 text-h-sm font-bold text-slate-900">
                Verifikasi dua faktor
            </h1>
            <p class="mt-2 max-w-2xl text-body-sm text-slate-500">
                Area admin butuh kode sekali pakai yang dikirim ke email admin.
            </p>
        </template>

        <div class="mx-auto max-w-[520px] px-6 py-10">
            <div class="rounded-card border border-slate-100 bg-white p-8 shadow-soft">
                <p class="text-body-sm text-slate-500">
                    Kode 6 digit dikirim ke
                    <span class="font-semibold text-slate-900">{{ masked_email }}</span>, berlaku 5
                    menit, maksimal 5 percobaan per kode.
                </p>

                <p v-if="verified_at" class="mt-3 text-caption text-slate-500">
                    Verifikasi terakhir: {{ verified_at }}
                </p>

                <button type="button" class="btn-ghost mt-6" @click="sendCode">
                    Kirim kode
                </button>

                <form class="mt-8 space-y-3" @submit.prevent="submit">
                    <label for="code" class="eyebrow">Kode verifikasi</label>
                    <input
                        id="code"
                        v-model="form.code"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="6"
                        class="input text-center text-sub-sm tracking-[0.4em]"
                    />
                    <InputError :message="form.errors.code" />
                    <button
                        type="submit"
                        class="btn-primary w-full"
                        :disabled="form.processing"
                    >
                        Verifikasi
                    </button>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
