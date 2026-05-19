<template>
    <AppLayout title="Tableau de bord">
        <div class="space-y-8">

            <!-- En-tête -->
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Bonjour, {{ firstName }}</h1>
                <p class="mt-0.5 text-sm text-gray-400">{{ todayFormatted }}</p>
            </div>

            <!-- Prochains événements -->
            <section>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Prochains événements</h2>
                    <span class="text-xs text-gray-300">{{ upcomingEvents.length }} à venir</span>
                </div>

                <div v-if="upcomingEvents.length === 0" class="bg-white rounded-2xl border border-dashed border-gray-200 py-14 text-center">
                    <div class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gray-100 mb-3">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-500">Aucun événement à venir</p>
                    <p class="text-xs text-gray-400 mt-1">Les prochains événements apparaîtront ici dès leur création.</p>
                </div>

                <div v-else class="bg-white rounded-2xl border border-gray-200 shadow-sm divide-y divide-gray-100">
                    <div v-for="event in upcomingEvents" :key="event.id" class="flex items-start gap-4 px-5 py-4">
                        <!-- Date -->
                        <div class="flex-shrink-0 w-11 text-center pt-0.5">
                            <p class="text-[10px] font-semibold text-indigo-400 uppercase tracking-wide">{{ event.month }}</p>
                            <p class="text-2xl font-bold text-gray-900 leading-tight">{{ event.day }}</p>
                            <p class="text-[10px] text-gray-400 leading-tight">{{ event.year }}</p>
                        </div>

                        <!-- Séparateur -->
                        <div class="w-px self-stretch bg-gray-100 flex-shrink-0"></div>

                        <!-- Contenu -->
                        <div class="flex-1 min-w-0 py-0.5">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-[11px] font-medium text-indigo-600">
                                    {{ event.event_type.name }}
                                </span>
                                <span v-if="event.days_until === 0"
                                      class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-semibold text-green-700">
                                    Aujourd'hui
                                </span>
                                <span v-else-if="event.days_until === 1"
                                      class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-700">
                                    Demain
                                </span>
                                <span v-else
                                      class="text-[11px] text-gray-400">
                                    dans {{ event.days_until }} jour{{ event.days_until > 1 ? 's' : '' }}
                                </span>
                            </div>
                            <p class="font-medium text-gray-900 text-sm">{{ event.title }}</p>
                            <p class="mt-0.5 text-xs text-gray-400">
                                {{ event.time_range }}
                                <template v-if="event.location"> · {{ event.location }}</template>
                                <template v-else-if="event.room_name"> · {{ event.room_name }}</template>
                            </p>
                            <p v-if="event.description" class="mt-1 text-xs text-gray-400 line-clamp-1">{{ event.description }}</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    upcomingEvents: Array,
});

const page = usePage();
const auth = computed(() => page.props.auth);

const firstName = computed(() => (auth.value?.user?.name ?? '').split(' ')[0]);

const todayFormatted = computed(() => {
    return new Intl.DateTimeFormat('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date());
});
</script>
