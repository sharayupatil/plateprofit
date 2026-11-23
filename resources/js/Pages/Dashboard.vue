<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>

        <section>
            <h1 class="text-3xl font-bold text-gray-900">
                Restaurant Analytics Dashboard
            </h1>

            <p class="mt-3 text-gray-600">
                Welcome to PlateProfit.
            </p>

            <p class="mt-2 text-gray-600">
                Import your restaurant POS data and turn it into
                useful sales and profitability insights.
            </p>
        </section>

        <section
            class="mt-10 rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
        >
            <h2 class="text-xl font-semibold text-gray-800">
                POS Imports
            </h2>

            <p class="mt-2 text-gray-600">
                View your previous POS imports or upload new
                restaurant sales data.
            </p>

            <div class="mt-6 flex flex-wrap gap-4">

                <Link
                    href="/pos-imports"
                    class="rounded-lg bg-blue-600 px-5 py-2.5 font-semibold text-white transition hover:bg-blue-700"
                >
                    View POS Imports
                </Link>

                <Link
                    href="/pos-imports/create"
                    class="rounded-lg bg-green-600 px-5 py-2.5 font-semibold text-white transition hover:bg-green-700"
                >
                    Import New POS File
                </Link>

            </div>

        </section>

    </AppLayout>
</template>