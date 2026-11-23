<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="font-medium text-gray-600 transition hover:text-blue-600"
    >
        <slot />
    </Link>
</template>