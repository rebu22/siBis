<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    business_name: '',
    business_type: '',
    city: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Daftar" />

        <p class="eyebrow">Akun baru</p>
        <h1 class="mt-2 text-sub font-bold text-slate-900">Daftar</h1>

        <form @submit.prevent="submit" class="mt-6">
            <div>
                <InputLabel for="name" value="Nama lengkap" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-2 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-5">
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-2 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-5">
                <InputLabel for="business_name" value="Nama usaha" />

                <TextInput
                    id="business_name"
                    type="text"
                    class="mt-2 block w-full"
                    v-model="form.business_name"
                    required
                    autocomplete="organization"
                />

                <InputError class="mt-2" :message="form.errors.business_name" />
            </div>

            <div class="mt-5">
                <InputLabel for="business_type" value="Bidang usaha (opsional)" />

                <TextInput
                    id="business_type"
                    type="text"
                    class="mt-2 block w-full"
                    v-model="form.business_type"
                />

                <InputError class="mt-2" :message="form.errors.business_type" />
            </div>

            <div class="mt-5">
                <InputLabel for="city" value="Kota (opsional)" />

                <TextInput
                    id="city"
                    type="text"
                    class="mt-2 block w-full"
                    v-model="form.city"
                />

                <InputError class="mt-2" :message="form.errors.city" />
            </div>

            <div class="mt-5">
                <InputLabel for="password" value="Kata sandi" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-2 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-5">
                <InputLabel
                    for="password_confirmation"
                    value="Ulangi kata sandi"
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-2 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="mt-6 flex items-center justify-between gap-4">
                <Link
                    :href="route('login')"
                    class="link-quiet inline-flex min-h-[44px] items-center text-body-sm"
                >
                    Sudah punya akun?
                </Link>

                <PrimaryButton
                    class="ms-auto"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Daftar
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
