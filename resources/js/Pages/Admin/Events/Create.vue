<template>
    <AppLayout title="Nouvel événement">
        <template #breadcrumb>
            <nav class="flex items-center gap-1.5 text-xs text-gray-400">
                <span class="font-medium text-gray-600">Administration</span>
                <span>/</span>
                <Link :href="route('admin.events.index')" class="hover:text-gray-600 transition-colors">Événements</Link>
                <span>/</span>
                <span>Nouveau</span>
            </nav>
        </template>

        <div class="max-w-2xl mx-auto">
            <h1 class="text-xl font-semibold text-gray-900 mb-5">Nouvel événement</h1>

            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <form @submit.prevent="submit">
                    <EventForm :form="form" :errors="form.errors" :event-types="eventTypes" />

                    <div class="mt-6 flex items-center justify-end gap-2">
                        <Link :href="route('admin.events.index')"
                              class="rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                            Annuler
                        </Link>
                        <button type="submit" :disabled="form.processing"
                                class="rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors disabled:opacity-50">
                            {{ form.processing ? 'Création...' : 'Créer l\'événement' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import EventForm from '@/Components/EventForm.vue';
import { useForm } from '@inertiajs/vue3';

defineProps({
    eventTypes: Array,
});

const form = useForm({
    title:         '',
    event_type_id: '',
    start_at:      '',
    end_at:        '',
    location:      '',
    description:   '',
});

function submit() {
    form.post(route('admin.events.store'));
}
</script>
