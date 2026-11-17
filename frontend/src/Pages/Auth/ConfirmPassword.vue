<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Konfirmasi kata sandi" />

        <p class="eyebrow">Keamanan</p>
        <h1 class="mt-2 text-sub font-bold text-slate-900">Konfirmasi kata sandi</h1>

        <p class="mt-3 max-w-xl text-body text-slate-700">
            Ini area terbatas. Konfirmasi kata sandi Anda sebelum melanjutkan.
        </p>

        <form @submit.prevent="submit" class="mt-6">
            <div>
                <InputLabel for="password" value="Kata sandi" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-2 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-6 flex justify-end">
                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Konfirmasi
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
