<template>
    <div class="space-y-5">
        <!-- Titre -->
        <div>
            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                Titre <span class="text-red-500">*</span>
            </label>
            <input v-model="form.title" type="text" id="title"
                   :class="inputClass(errors.title)"
                   placeholder="Ex : Réunion du Conseil d'Administration">
            <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title }}</p>
        </div>

        <!-- Type d'événement -->
        <div>
            <label for="event_type_id" class="block text-sm font-medium text-gray-700 mb-1">
                Type <span class="text-red-500">*</span>
            </label>
            <select v-model="form.event_type_id" id="event_type_id" :class="inputClass(errors.event_type_id)">
                <option value="">-- Sélectionner un type --</option>
                <option v-for="type in eventTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
            </select>
            <p v-if="errors.event_type_id" class="mt-1 text-sm text-red-600">{{ errors.event_type_id }}</p>
        </div>

        <!-- Dates -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="start_at" class="block text-sm font-medium text-gray-700 mb-1">
                    Début <span class="text-red-500">*</span>
                </label>
                <input v-model="form.start_at" type="datetime-local" id="start_at" :class="inputClass(errors.start_at)">
                <p v-if="errors.start_at" class="mt-1 text-sm text-red-600">{{ errors.start_at }}</p>
            </div>
            <div>
                <label for="end_at" class="block text-sm font-medium text-gray-700 mb-1">
                    Fin <span class="text-red-500">*</span>
                </label>
                <input v-model="form.end_at" type="datetime-local" id="end_at" :class="inputClass(errors.end_at)">
                <p v-if="errors.end_at" class="mt-1 text-sm text-red-600">{{ errors.end_at }}</p>
            </div>
        </div>

        <!-- Lieu -->
        <div>
            <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Lieu</label>
            <input v-model="form.location" type="text" id="location"
                   :class="inputClass(errors.location)"
                   placeholder="Ex : Salle du Conseil, 10 rue de la Paix…">
            <p v-if="errors.location" class="mt-1 text-sm text-red-600">{{ errors.location }}</p>
        </div>

        <!-- Description -->
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea v-model="form.description" id="description" rows="4"
                      class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                      placeholder="Ordre du jour, informations complémentaires..."></textarea>
        </div>
    </div>
</template>

<script setup>
defineProps({
    form:       Object,
    errors:     Object,
    eventTypes: Array,
});

function inputClass(hasError) {
    return [
        'block w-full rounded-lg border px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition',
        hasError ? 'border-red-300 bg-red-50/20' : 'border-gray-200',
    ];
}
</script>
