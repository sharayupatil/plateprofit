<script setup>
import { Link } from '@inertiajs/vue3';
import NavLink from '../Components/NavLink.vue';
</script>

<template>
    <div class="min-h-screen bg-gray-50">

        <!-- Main navigation -->
        <header class="border-b border-gray-200 bg-white shadow-sm">
            <div
                class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4"
            >
                <!-- PlateProfit -->
                <Link
                    href="/dashboard"
                    class="text-2xl font-bold text-blue-600"
                >
                    PlateProfit
                </Link>

                <!-- Navigation -->
                <nav class="flex items-center gap-6">
                    <NavLink href="/dashboard">
                        Dashboard
                    </NavLink>

                    <NavLink href="/pos-imports">
                        POS Imports
                    </NavLink>

                    <NavLink href="/pos-imports/create">
                        Import CSV
                    </NavLink>
                </nav>
            </div>
        </header>

        <!-- Current page -->
        <main class="mx-auto max-w-7xl px-6 py-10">
            <slot />
        </main>

    </div>
</template>