<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Masuk" />

        <p class="eyebrow">Akun</p>
        <h1 class="mt-2 text-sub font-bold text-slate-900">Masuk</h1>

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

            <div class="mt-5">
                <InputLabel for="password" value="Kata sandi" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-2 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-5 block">
                <label class="flex min-h-[44px] items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-body-sm text-slate-700">Ingat saya</span>
                </label>
            </div>

            <div class="mt-6 flex items-center justify-between gap-4">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="link-quiet inline-flex min-h-[44px] items-center text-body-sm"
                >
                    Lupa kata sandi?
                </Link>

                <PrimaryButton
                    class="ms-auto"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Masuk
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
