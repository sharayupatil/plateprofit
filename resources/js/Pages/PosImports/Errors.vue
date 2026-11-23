<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
    posImport: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Import Errors" />
    <AppLayout>

    <main class="mx-auto max-w-5xl px-6 py-12">

        <!-- Header -->
        <div
            class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between"
        >
            <div>
                <h1 class="text-3xl font-bold text-red-600">
                    Import Errors
                </h1>

                <p class="mt-2 text-gray-600">
                    {{ posImport.original_filename }}
                </p>
            </div>

            <Link
                href="/pos-imports"
                class="rounded-lg bg-blue-600 px-5 py-2.5 font-semibold text-white transition hover:bg-blue-700"
            >
                ← Back to POS Imports
            </Link>
        </div>

        <!-- Summary -->
        <div class="mb-8 grid gap-4 sm:grid-cols-3">

            <div
                class="rounded-xl border border-gray-200 bg-gray-50 p-5"
            >
                <span class="text-sm text-gray-500">
                    Processed
                </span>

                <p class="mt-2 text-2xl font-bold text-gray-800">
                    {{ posImport.rows_processed }}
                </p>
            </div>

            <div
                class="rounded-xl border border-green-200 bg-green-50 p-5"
            >
                <span class="text-sm text-green-700">
                    Successful
                </span>

                <p class="mt-2 text-2xl font-bold text-green-600">
                    {{ posImport.rows_successful }}
                </p>
            </div>

            <div
                class="rounded-xl border border-red-200 bg-red-50 p-5"
            >
                <span class="text-sm text-red-700">
                    Failed
                </span>

                <p class="mt-2 text-2xl font-bold text-red-600">
                    {{ posImport.rows_failed }}
                </p>
            </div>

        </div>

        <!-- Errors -->
        <div
            v-if="posImport.errors.length > 0"
            class="overflow-hidden rounded-xl border border-red-200 bg-white"
        >
            <table class="w-full border-collapse text-left">

                <thead class="bg-red-50">
                    <tr>
                        <th
                            class="w-28 px-4 py-4 font-semibold text-red-800"
                        >
                            Row
                        </th>

                        <th
                            class="px-4 py-4 font-semibold text-red-800"
                        >
                            Error
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-red-100">

                    <tr
                        v-for="error in posImport.errors"
                        :key="error.id"
                        class="hover:bg-red-50"
                    >
                        <td
                            class="px-4 py-4 font-semibold text-red-600"
                        >
                            {{ error.row_number }}
                        </td>

                        <td class="px-4 py-4 text-gray-700">
                            {{ error.message }}
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        <!-- No errors -->
        <div
            v-else
            class="rounded-lg border border-green-200 bg-green-50 p-5 text-green-800"
        >
            No errors were recorded for this import.
        </div>

    </main>
    </AppLayout>
</template>