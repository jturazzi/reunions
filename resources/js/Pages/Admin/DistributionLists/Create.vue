<template>
    <AppLayout title="Nouveau groupe de destinataires">
        <template #breadcrumb>
            <nav class="flex items-center gap-1.5 text-xs text-gray-400">
                <span class="font-medium text-gray-600">Administration</span>
                <span>/</span>
                <Link :href="route('admin.distribution-lists.index')" class="hover:text-gray-600 transition-colors">Destinataires</Link>
                <span>/</span>
                <span>Nouveau groupe</span>
            </nav>
        </template>

        <div class="max-w-lg">
            <h1 class="text-xl font-semibold text-gray-900 mb-5">Nouveau groupe de destinataires</h1>

            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                <form @submit.prevent="submit">
                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                Nom <span class="text-red-500">*</span>
                            </label>
                            <input v-model="form.name" type="text" id="name" required
                                   :class="['block w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500',
                                            form.errors.name ? 'border-red-300' : 'border-gray-200']"
                                   placeholder="Ex : Direction générale, Comité de pilotage…" />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <input v-model="form.description" type="text" id="description"
                                   class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                   placeholder="Optionnel" />
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-2">
                        <Link :href="route('admin.distribution-lists.index')"
                              class="rounded-lg border border-gray-200 px-3.5 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                            Annuler
                        </Link>
                        <button type="submit" :disabled="form.processing"
                                class="rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                            {{ form.processing ? 'Création...' : 'Créer le groupe' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

const form = useForm({ name: '', description: '' });

function submit() {
    form.post(route('admin.distribution-lists.store'));
}
</script>
