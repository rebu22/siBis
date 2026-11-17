<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Lupa kata sandi" />

        <p class="eyebrow">Pemulihan akses</p>
        <h1 class="mt-2 text-sub font-bold text-slate-900">Lupa kata sandi?</h1>

        <p class="mt-3 max-w-xl text-body text-slate-700">
            Masukkan email Anda, kami akan mengirim tautan untuk membuat kata
            sandi baru.
        </p>

        <div
            v-if="status"
            class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-body-sm text-emerald-700"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="mt-6">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-2 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-6 flex items-center justify-end">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Kirim tautan reset
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
