<template>
    <AppLayout title="Modifier l'événement">
        <template #breadcrumb>
            <nav class="flex items-center gap-1.5 text-xs text-gray-400">
                <span class="font-medium text-gray-600">Administration</span>
                <span>/</span>
                <Link :href="route('admin.events.index')" class="hover:text-gray-600 transition-colors">Événements</Link>
                <span>/</span>
                <span>Modifier</span>
            </nav>
        </template>

        <div class="max-w-2xl mx-auto">
            <h1 class="text-xl font-semibold text-gray-900 mb-5">Modifier l'événement</h1>

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
                            {{ form.processing ? 'Enregistrement...' : 'Enregistrer' }}
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

const props = defineProps({
    event:      Object,
    eventTypes: Array,
});

const form = useForm({
    title:         props.event.title ?? '',
    event_type_id: props.event.event_type_id ?? '',
    start_at:      props.event.start_at_input ?? '',
    end_at:        props.event.end_at_input ?? '',
    location:      props.event.location ?? '',
    description:   props.event.description ?? '',
});

function submit() {
    form.put(route('admin.events.update', props.event.id));
}
</script>
