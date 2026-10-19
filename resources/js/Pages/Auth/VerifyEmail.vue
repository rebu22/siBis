<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Verifikasi email" />

        <p class="eyebrow">Verifikasi</p>
        <h1 class="mt-2 text-sub font-bold text-slate-900">Verifikasi email</h1>

        <p class="mt-3 max-w-xl text-body text-slate-700">
            Terima kasih sudah mendaftar. Klik tautan yang baru kami kirim ke
            email Anda untuk memverifikasi. Bila belum menerima, kami bisa
            mengirim ulang.
        </p>

        <div
            v-if="verificationLinkSent"
            class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-body-sm text-emerald-700"
        >
            Tautan verifikasi baru sudah dikirim ke email yang Anda daftarkan.
        </div>

        <form @submit.prevent="submit">
            <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
                <SecondaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Kirim ulang email verifikasi
                </SecondaryButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="link-quiet inline-flex min-h-[44px] items-center text-body-sm"
                    >Keluar</Link
                >
            </div>
        </form>
    </GuestLayout>
</template>
