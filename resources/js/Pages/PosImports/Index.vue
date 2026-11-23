<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
    imports: {
        type: Object,
        required: true,
    },

    success: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <Head title="POS Imports" />
    <AppLayout>

    <main class="mx-auto max-w-7xl px-6 py-12">

        <!-- Header -->
        <div
            class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-3xl font-bold text-blue-600">
                    POS Imports
                </h1>

                <p class="mt-2 text-gray-600">
                    View and manage your restaurant POS data imports.
                </p>
            </div>

            <Link
                href="/pos-imports/create"
                class="self-start rounded-lg bg-green-600 px-5 py-2.5 font-semibold text-white transition hover:bg-green-700"
            >
                Import New CSV
            </Link>
        </div>

        <!-- Success -->
        <div
            v-if="success"
            class="mb-6 rounded-lg border border-green-200 bg-green-50 px-5 py-4 text-green-800"
        >
            {{ success }}
        </div>

        <!-- Table -->
        <div
            class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm"
        >
            <table class="w-full border-collapse text-left">

                <thead class="bg-gray-50 text-sm text-gray-700">
                    <tr>
                        <th class="px-4 py-4 font-semibold">
                            File
                        </th>

                        <th class="px-4 py-4 font-semibold">
                            Status
                        </th>

                        <th class="px-4 py-4 font-semibold">
                            Processed
                        </th>

                        <th class="px-4 py-4 font-semibold">
                            Successful
                        </th>

                        <th class="px-4 py-4 font-semibold">
                            Failed
                        </th>

                        <th class="px-4 py-4 font-semibold">
                            Uploaded
                        </th>

                        <th class="px-4 py-4 font-semibold">
                            Errors
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">

                    <tr
                        v-for="posImport in imports.data"
                        :key="posImport.id"
                        class="hover:bg-gray-50"
                    >
                        <td class="px-4 py-4 text-gray-800">
                            {{ posImport.original_filename }}
                        </td>

                        <td class="px-4 py-4 text-gray-600">
                            {{ posImport.status }}
                        </td>

                        <td class="px-4 py-4 text-gray-600">
                            {{ posImport.rows_processed }}
                        </td>

                        <td class="px-4 py-4 font-semibold text-green-600">
                            {{ posImport.rows_successful }}
                        </td>

                        <td
                            class="px-4 py-4 font-semibold"
                            :class="
                                posImport.rows_failed > 0
                                    ? 'text-red-600'
                                    : 'text-gray-600'
                            "
                        >
                            {{ posImport.rows_failed }}
                        </td>

                        <td class="px-4 py-4 text-gray-600">
                            {{ posImport.created_at }}
                        </td>

                        <td class="px-4 py-4">

                            <Link
                                v-if="posImport.rows_failed > 0"
                                :href="`/pos-imports/${posImport.id}/errors`"
                                class="font-semibold text-red-600 hover:text-red-800 hover:underline"
                            >
                                View errors
                            </Link>

                            <span
                                v-else
                                class="font-medium text-green-600"
                            >
                                No errors
                            </span>

                        </td>
                    </tr>

                    <tr v-if="imports.data.length === 0">
                        <td
                            colspan="7"
                            class="px-6 py-10 text-center text-gray-500"
                        >
                            No POS files have been imported.
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div
            v-if="imports.links"
            class="mt-6 flex flex-wrap gap-2"
        >
            <template
                v-for="link in imports.links"
                :key="link.label"
            >
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="rounded-md border px-3 py-2 text-sm transition"
                    :class="
                        link.active
                            ? 'border-blue-600 bg-blue-600 text-white'
                            : 'border-gray-300 text-blue-600 hover:bg-gray-50'
                    "
                    v-html="link.label"
                />

                <span
                    v-else
                    class="cursor-not-allowed rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-400"
                    v-html="link.label"
                />
            </template>
        </div>

        <!-- Dashboard -->
        <div class="mt-8">
            <Link
                href="/dashboard"
                class="font-semibold text-purple-600 hover:text-purple-800 hover:underline"
            >
                ← Back to Dashboard
            </Link>
        </div>

    </main>
    </AppLayout>
</template>