<template>
    <AppLayout :title="`${list.name} — Destinataires`">
        <template #breadcrumb>
            <nav class="flex items-center gap-1.5 text-xs text-gray-400">
                <span class="font-medium text-gray-600">Administration</span>
                <span>/</span>
                <Link :href="route('admin.distribution-lists.index')" class="hover:text-gray-600 transition-colors">Destinataires</Link>
                <span>/</span>
                <span>{{ list.name }}</span>
            </nav>
        </template>

        <div class="space-y-6 max-w-2xl">

            <!-- En-tête -->
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">{{ list.name }}</h1>
                    <p v-if="list.description" class="mt-0.5 text-sm text-gray-400">{{ list.description }}</p>
                </div>
                <Link :href="route('admin.distribution-lists.index')"
                      class="text-sm text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0">
                    ← Retour
                </Link>
            </div>

            <!-- Formulaire d'ajout -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Ajouter un membre</h2>

                <div v-if="msUsers.length === 0" class="rounded-lg border border-dashed border-gray-200 p-4 text-sm text-gray-400 text-center">
                    Impossible de charger l'annuaire Microsoft (vérifiez la permission <code class="font-mono text-xs">User.Read.All</code>).
                </div>

                <div v-else class="flex items-center gap-2 min-w-0">
                    <select v-model="selected"
                            class="min-w-0 flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="" disabled>-- Sélectionner un utilisateur --</option>
                        <option v-for="user in availableUsers" :key="user.email" :value="user">
                            {{ user.name }} - {{ user.email }}
                        </option>
                    </select>
                    <button type="button"
                            @click="addUser"
                            :disabled="!selected || form.processing"
                            class="flex-shrink-0 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 transition-colors whitespace-nowrap">
                        {{ form.processing ? '…' : 'Ajouter' }}
                    </button>
                </div>
                <p v-if="form.errors.email" class="mt-2 text-xs text-red-600">{{ form.errors.email }}</p>
            </div>

            <!-- Liste des destinataires -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700">Membres</h2>
                    <span class="text-xs text-gray-400">{{ recipients.length }} au total</span>
                </div>

                <div v-if="recipients.length === 0" class="p-10 text-center">
                    <p class="text-sm text-gray-400">Aucun membre. Ajoutez-en un ci-dessus.</p>
                </div>

                <ul v-else class="divide-y divide-gray-50">
                    <li v-for="recipient in recipients" :key="recipient.id"
                        class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50/60 transition-colors">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                            <span class="text-xs font-semibold text-indigo-600">{{ initials(recipient.name) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900">{{ recipient.name }}</p>
                            <p class="text-xs text-gray-400">{{ recipient.email }}</p>
                        </div>
                        <Link :href="route('admin.recipients.show', recipient.email)"
                              class="flex-shrink-0 rounded-lg p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
                              title="Voir les événements">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                        </Link>
                        <button @click="removeRecipient(recipient)"
                                class="flex-shrink-0 rounded-lg p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                title="Supprimer">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </li>
                </ul>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    list:       Object,
    recipients: Array,
    msUsers:    Array,
});

const form     = useForm({ name: '', email: '' });
const selected = ref('');

// Exclure les membres déjà dans le groupe
const availableUsers = computed(() => {
    const emails = new Set(props.recipients.map(r => r.email.toLowerCase()));
    return props.msUsers.filter(u => !emails.has(u.email.toLowerCase()));
});

function addUser() {
    if (!selected.value) return;
    form.name  = selected.value.name;
    form.email = selected.value.email;
    form.post(route('admin.distribution-lists.recipients.store', props.list.id), {
        onSuccess: () => { form.reset(); selected.value = ''; },
    });
}

function removeRecipient(recipient) {
    if (confirm(`Retirer ${recipient.name} du groupe ?`)) {
        router.delete(route('admin.distribution-lists.recipients.destroy', {
            distributionList: props.list.id,
            recipient:        recipient.id,
        }));
    }
}

function initials(name) {
    return name.split(' ').slice(0, 2).map(w => w[0]?.toUpperCase() ?? '').join('');
}
</script>

