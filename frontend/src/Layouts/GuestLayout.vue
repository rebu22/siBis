<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="relative isolate flex min-h-screen flex-col items-center justify-center overflow-hidden bg-slate-50 px-6 py-12"
    >
        <div
            class="orb left-1/2 top-0 h-96 w-96 -translate-x-1/2 bg-indigo-500/10"
            aria-hidden="true"
        />
        <div
            class="orb bottom-0 right-0 h-80 w-80 translate-x-1/3 translate-y-1/3 bg-violet-500/10"
            aria-hidden="true"
        />

        <Link href="/" class="flex items-baseline gap-2" aria-label="siBis">
            <ApplicationLogo class="h-10 w-10 text-indigo-600" />
            <span class="gradient-text text-sub-sm font-bold tracking-tight"
                >siBis</span
            >
        </Link>

        <div
            class="mt-8 w-full max-w-md rounded-card border border-slate-100 bg-white p-6 shadow-soft sm:p-8"
        >
            <slot />
        </div>
    </div>
</template>
