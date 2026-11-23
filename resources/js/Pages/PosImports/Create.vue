<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const form = useForm({
    csv_file: null,
});

const submit = () => {
    form.post('/pos-imports', {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Import POS Sales" />
    <AppLayout>

    <main class="mx-auto max-w-4xl px-6 py-12">

        <!-- Header -->
        <div
            class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between"
        >
            <div>
                <h1 class="text-3xl font-bold text-blue-600">
                    Import POS Sales
                </h1>

                <p class="mt-3 text-gray-600">
                    Upload a CSV containing order, customer and product data.
                </p>

                <p class="mt-2 text-sm text-gray-500">
                    Slash-separated order dates use DD/MM/YYYY,
                    for example 21/11/2026 12:45.
                </p>
            </div>

            <Link
                href="/pos-imports"
                class="font-semibold text-purple-600 hover:text-purple-800 hover:underline"
            >
                ← Back to POS Imports
            </Link>
        </div>

        <!-- Errors -->
        <div
            v-if="Object.keys(form.errors).length"
            class="mb-6 rounded-lg border border-red-200 bg-red-50 p-5 text-red-800"
        >
            <strong>
                The file could not be imported.
            </strong>

            <ul class="mt-3 list-disc space-y-1 pl-5">
                <li
                    v-for="error in form.errors"
                    :key="error"
                >
                    {{ error }}
                </li>
            </ul>
        </div>

        <!-- Upload form -->
        <form
            class="rounded-xl border border-gray-200 bg-gray-50 p-8 shadow-sm"
            @submit.prevent="submit"
        >
            <div>
                <label
                    for="csv_file"
                    class="block font-semibold text-gray-700"
                >
                    POS CSV file
                </label>

                <input
                    id="csv_file"
                    type="file"
                    accept=".csv,text/csv"
                    required
                    class="mt-3 block w-full cursor-pointer rounded-lg border border-gray-300 bg-white p-3 text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                    @change="form.csv_file = $event.target.files[0]"
                />

                <p
                    v-if="form.csv_file"
                    class="mt-3 text-sm font-medium text-green-600"
                >
                    Selected: {{ form.csv_file.name }}
                </p>
            </div>

            <button
                type="submit"
                :disabled="form.processing || !form.csv_file"
                class="mt-7 rounded-lg bg-green-600 px-5 py-2.5 font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {{
                    form.processing
                        ? 'Importing...'
                        : 'Upload and Import'
                }}
            </button>
        </form>

    </main>
    </AppLayout>
</template>