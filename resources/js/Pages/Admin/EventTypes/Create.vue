<template>
    <AppLayout title="Nouveau type d'événement">
        <template #breadcrumb>
            <nav class="flex items-center gap-1.5 text-xs text-gray-400">
                <span class="font-medium text-gray-600">Administration</span>
                <span>/</span>
                <Link :href="route('admin.event-types.index')" class="hover:text-gray-600 transition-colors">Types</Link>
                <span>/</span>
                <span>Nouveau</span>
            </nav>
        </template>

        <div class="max-w-lg mx-auto">
            <h1 class="text-xl font-semibold text-gray-900 mb-5">Nouveau type d'événement</h1>

            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                            Nom <span class="text-red-500">*</span>
                        </label>
                        <input v-model="form.name" type="text" id="name" required
                               :class="['block w-full rounded-lg border px-3 py-2 text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500',
                                        form.errors.name ? 'border-red-300' : 'border-gray-300']"
                               placeholder="Ex : Conseil d'Administration">
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea v-model="form.description" id="description" rows="3"
                                  class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                  placeholder="Description optionnelle..."></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <Link :href="route('admin.event-types.index')"
                              class="rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                            Annuler
                        </Link>
                        <button type="submit" :disabled="form.processing"
                                class="rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors disabled:opacity-50">
                            {{ form.processing ? 'Création...' : 'Créer le type' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    description: '',
});

function submit() {
    form.post(route('admin.event-types.store'));
}
</script>
