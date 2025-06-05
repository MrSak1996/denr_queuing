<script lang="ts" setup>
import { usePage } from '@inertiajs/vue3';
import { useSpeechSynthesis } from '@vueuse/core';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { nextTick, onMounted, ref } from 'vue';

// PrimeVue Components
import Chip from 'primevue/chip';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';

// Modals
import ModalTransfer from '@/pages/modal/modal_transfer.vue';
import ModalHoldingArea from '../modal/modal_holding_area.vue';
import ModalPriority from '../modal/modal_priority.vue';
import ModalRecall from '../modal/modal_recall.vue';

import { type SharedData, type User } from '@/types';

// === STATE ===
const page = usePage<SharedData>();
const user = page.props.auth.user as User;
const toast = useToast();

const clients = ref<any[]>([]);
const queue = ref<any[]>([]);
const selectedProduct = ref<any>(null);
const servingTime = ref('00:00:00');

const transferModal = ref(false);
const priorityModal = ref(false);
const holdingModal = ref(false);
const recallModal = ref(false);

const selectedVoice = ref<SpeechSynthesisVoice | null>(null);
const spokenTimestamps = ref<Record<number, string>>({});
let speaking = false;

const maxQueue = 50;
let priorityCounter = 1;
let currentPriorityIndex = -1;

// === SPEECH SYNTHESIS ===
const { isSupported } = useSpeechSynthesis();

const loadVoices = () => {
    const voices = window.speechSynthesis.getVoices();
    const preferredVoices = voices.filter((voice) => /female|hazel|zira|susan|samantha|siri/i.test(voice.name));
    selectedVoice.value = preferredVoices[0] || voices.find((v) => /en/i.test(v.lang)) || voices[0];
};

if (window.speechSynthesis.onvoiceschanged !== undefined) {
    window.speechSynthesis.onvoiceschanged = loadVoices;
}
loadVoices();

// === FUNCTIONS ===

const getClient = async (counterId: number) => {
    try {
        const { data } = await axios.get(`/api/clients?counter_id=${counterId}`);
        clients.value = data;

        queue.value = data.slice(0, 2).map((client: any) => ({
            queue_id: client.queue_id,
            queue_number: client.queue_number,
            status: client.status,
            priority_level_id: client.priority_level_id,
            priority_level_name: client.priority_level?.name ?? '',
        }));

        window.Echo.channel(`clients.counter.${counterId}`).listen('ClientUpdated', (e: any) => {
            console.log('Client updated received:', e.client);

            const updatedClient = e.client;

            // Check if client still belongs to this counter
            if (updatedClient.counter_id === counterId) {
                // Either update existing OR push new
                const index = clients.value.findIndex((c) => c.queue_id === updatedClient.queue_id);
                if (index !== -1) {
                    clients.value[index] = updatedClient;
                } else {
                    clients.value.push(updatedClient);
                }
            } else {
                // Remove client from list if it transferred to another counter
                clients.value = clients.value.filter((c) => c.queue_id !== updatedClient.queue_id);
            }

            // Update the queue again (display first 2)
            queue.value = clients.value.slice(0, 2).map((client: any) => ({
                queue_id: client.queue_id,
                queue_number: client.queue_number,
                status: client.status,
                priority_level_id: client.priority_level_id,
                priority_level_name: client.priority_level?.name ?? '',
            }));

            console.log('Updated client list:', clients.value);
        });
    } catch (error) {
        console.error('Error fetching client data:', error);
    }
};

const callNextClient = async () => {
    if (!clients.value.length) {
        toast.add({ severity: 'warn', summary: 'Queue Empty', detail: 'No more clients in the queue!', life: 3000 });
        return;
    }

    const currentClient = clients.value[0];
    // currentClient.status = 'Completed';

    try {
        await axios.post('/api/update-client-status', {
            queue_id: currentClient.queue_id,
            status: 'ongoing',
        });

        clients.value.shift();
        queue.value.shift();
        clients.value = [...clients.value];
        await saveQueueLogs(currentClient.queue_id, currentClient.counter_name);
        await getClient(user.service_counter_id);

        toast.add({ severity: 'success', summary: 'Client Served', detail: `Client ${currentClient.queue_number} has been completed`, life: 3000 });
    } catch (error) {
        console.error('Error updating client status:', error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'Could not update client status', life: 3000 });
    }
};

const completeTransaction = async () => {
    if (!clients.value.length) return;
    try {
        const currentClient = clients.value[0];
        await axios.post('/api/update-client-transaction', {
            queue_id: currentClient.queue_id,
            status: 'completed',
        });
        await getClient(user.service_counter_id);
    } catch (error) {
        console.error('Error completing transaction:', error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'Could not complete transaction', life: 3000 });
    }
};

const setClientPriority = async (client: any, priorityLevel: number) => {
    try {
        await axios.post('/api/set_client_priority', {
            client_id: client.client_id,
            priority_level_id: priorityLevel,
        });
        clients.value.shift();
        queue.value.shift();
        await getClient(user.service_counter_id);

        toast.add({ severity: 'success', summary: 'Priority Set', detail: 'Client set to priority lane', life: 3000 });
    } catch (error) {
        console.error('Error setting client priority:', error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'Could not set client priority', life: 3000 });
    }
};

const saveQueueLogs = async (queueId: number, counterName: string) => {
    try {
        await axios.post('/api/save_queue_logs', {
            queue_id: queueId,
            served_by: counterName,
        });
    } catch (error) {
        console.error('Error saving queue logs:', error);
    }
};

const startServingTimer = () => {
    let seconds = 0;
    setInterval(() => {
        seconds++;
        const h = String(Math.floor(seconds / 3600)).padStart(2, '0');
        const m = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
        const s = String(seconds % 60).padStart(2, '0');
        servingTime.value = `${h}:${m}:${s}`;
    }, 1000);
};

const onRowSelect = (event: any) => {
    selectedProduct.value = event.data;
    toast.add({ severity: 'info', summary: 'Client Selected', detail: `Queue Number: ${event.data.queue_number}`, life: 3000 });
    transferModal.value = true;
};

const btn_call = async (queue_id) => {
    try {
        const response = await axios.post('/api/recallClient', {
            queue_id: queue_id,
        });

        const { queue_number, counter_name, called_at } = response.data;

        if (!queue_number || !counter_name) {
            throw new Error('Invalid response from server');
        }

        // // Now speak it
        // if (response.data){
        //     speakQueueCall(queue_number, counter_name, called_at);
        // }

        toast.add({
            severity: 'success',
            summary: 'Recalled',
            detail: `Queue number ${queue_number} has been recalled.`,
            life: 3000,
        });
    } catch (error) {
        console.error('Error calling client:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Could not call client',
            life: 3000,
        });
    }
};

const speakQueueCall = (queueNumber: number, counterId: number, calledAt: string) => {
    const lastSpokenTimestamp = spokenTimestamps.value[queueNumber];
    if (lastSpokenTimestamp === calledAt) return; // Skip if already spoken

    const utterance = new SpeechSynthesisUtterance(`Queue number ${queueNumber}, please proceed to ${counterId || 1}.`);
    if (selectedVoice.value) {
        utterance.voice = selectedVoice.value;
    }

    speaking = true;
    window.speechSynthesis.cancel();
    window.speechSynthesis.speak(utterance);

    spokenTimestamps.value[queueNumber] = calledAt;

    utterance.onend = () => {
        speaking = false;
    };
};

const priorityLane = async () => {
    if (currentPriorityIndex !== -1) {
        queue.value[currentPriorityIndex].isPriorityLane = false;
        await nextTick();
    }

    const nextPriorityNumber = priorityCounter++;
    if (nextPriorityNumber <= maxQueue) {
        queue.value.unshift({ number: nextPriorityNumber, isPriorityLane: true, isNormal: false });
        currentPriorityIndex = 0;
    }

    const priorityItems = queue.value.filter((item) => item.isPriorityLane).sort((a, b) => a.number - b.number);
    const normalItems = queue.value.filter((item) => item.isNormal);

    queue.value = [...priorityItems, ...normalItems];
};

const clientOptions = (client: any) => [
    { label: 'PWD', icon: 'pi pi-exclamation-circle', command: () => setClientPriority(client, 1) },
    { label: 'Senior Citizen', icon: 'pi pi-user', command: () => setClientPriority(client, 2) },
    { label: 'Pregnant Women', icon: 'pi pi-user-plus', command: () => setClientPriority(client, 3) },
    { label: 'Regular Client', icon: 'pi pi-user-edit', command: () => setClientPriority(client, 4) },
];

const callClient = async (queueId: number) => {
    try {
        await axios.post('/api/recallClient', { queue_id: queueId });
    } catch (error) {
        console.error('Error recalling client:', error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'Could not call client', life: 3000 });
    }
};
// === LIFECYCLE ===
onMounted(() => {
    const counterId = user.service_counter_id ?? 1;
    getClient(counterId);
    startServingTimer();
});
</script>

<template>
    <div class="col-span-1 flex flex-col items-center justify-start gap-4">
        <Toast />
        <ModalTransfer v-if="transferModal" :queue="selectedProduct" :open="transferModal"
            @close="transferModal = false"></ModalTransfer>
        <ModalPriority v-if="priorityModal" :counterId="user.service_counter_id" :queue="selectedProduct"
            :open="priorityModal" @close="priorityModal = false"></ModalPriority>
        <ModalHoldingArea>
            v-if="holdingModal" :counterId="user.service_counter_id" :queue="selectedProduct" :open="holdingModal"
            @close="holdingModal =
            false"></ModalHoldingArea>
        <ModalRecall v-if="recallModal" :counterId="user.service_counter_id" :queue="selectedProduct"
            :open="recallModal" @close="recallModal = false"></ModalRecall>

        <div class="flex w-full flex-col items-center gap-4">
            <transition-group v-if="queue.length > 0" name="fade-slide" tag="div"
                class="flex w-full flex-col items-center gap-4">
                <div v-for="(pac, index) in queue" :key="pac.queue_id + '-' + pac.isPriorityLane"
                    class="w-full max-w-lg rounded border p-6 text-center shadow"
                    :class="pac.isPriorityLane ? 'bg-yellow-200' : 'bg-gray-50'">
                    <div class="flex flex-col items-center gap-2">
                        <div v-if="index === 0" class="text-lg font-semibold text-green-600">Current Serving</div>
                        <div class="text-lg font-bold text-blue-900">Token Number</div>

                        <div class="mt-2 rounded-lg border-4 border-green-500 px-10 py-6 text-6xl font-bold"
                            :class="pac.isPriorityLane ? 'text-blue-800' : 'text-green-500'">
                            {{ pac.queue_number }}
                        </div>

                        <div v-if="index === 0" class="mt-2 text-sm font-semibold text-blue-900">Serving Time</div>
                        <div v-if="index === 0" class="text-2xl font-bold text-blue-900">
                            {{ servingTime }}
                        </div>

                        <button
                            class="mt-4 w-24 rounded-md bg-[#1a2d42] py-2 text-sm font-bold text-white transition-all hover:brightness-110"
                            @click="callNextClient">
                            {{ pac.status.toUpperCase() }}
                        </button>
                    </div>
                </div>
            </transition-group>

            <div v-else class="w-full max-w-lg rounded border bg-gray-100 p-6 text-center text-gray-500 shadow">
                <div class="flex flex-col items-center gap-2">
                    <div class="text-xl font-semibold">No clients in queue</div>
                    <div class="text-sm">Please wait for the next client to arrive.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- CONTROLS -->
    <div class="col-span-3 flex max-w-full flex-col gap-4">
        <div class="flex h-[700px] flex-col gap-5 rounded-xl text-slate-900 md:flex-row">
            <div class="max-h-[72vh] w-full flex-1 overflow-hidden rounded border bg-gray-50 p-4 text-center shadow">
                <DataTable v-model:selection="selectedProduct" :value="clients" selectionMode="single" size="small"
                    paginator showGridlines :rows="20" filterDisplay="menu" scrollable scrollHeight="flex"
                    dataKey="queue_number" :metaKeySelection="false" @rowSelect="onRowSelect">
                    <Column field="counter_name" header="Counter"></Column>
                    <template #empty>
                        <div class="flex flex-col items-center justify-center py-6 text-gray-500">
                            <svg class="mb-2 h-12 w-12 text-gray-400" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 17v-2a4 4 0 014-4h6M9 17H7a4 4 0 01-4-4V7a4 4 0 014-4h6a4 4 0 014 4v6a4 4 0 01-4 4H9z" />
                            </svg>
                            <div class="text-lg font-semibold">No clients found</div>
                            <div class="text-sm">Please wait for new clients to appear in the queue.</div>
                        </div>
                    </template>
                    <Column field="queue_number" header="Queue No."> {{ data.queue_number }} </Column>
                    <Column field="priority_level" header="Priority Level" class="w-[80px]">
                        <template #body="{ data }">
                            <Chip class="py-0 pl-0 pr-4">
                                <span :class="{
                                    'bg-orange-600 text-white': data.priority_level !== 'Regular', // Default orange
                                    'bg-gradient-to-b from-[#2E4156] to-[#1A2D42] text-white': data.priority_level === 'Regular', // Blue gradient for Regular
                                }" class="text-primary-contrast flex h-8 w-8 items-center justify-center rounded-full">
                                    <!-- Optionally, display the first letter or some icon -->
                                    <!-- {{ data.priority_level.charAt(0) }} -->
                                </span>
                            </Chip>
                        </template>
                    </Column>
                    <Column field="status" header="Status"></Column>
                    <Column field="queued_at" header="Date/Time">
                        <template #body="{ data }">
                            {{ new Date(data.queued_at).toLocaleDateString('en-US', {
                                year: 'numeric', month: 'long',
                                day: '2-digit',
                                hour: '2-digit',
                                minute: '2-digit',
                                hour12: true
                            }) }}
                        </template>
                    </Column>

                    <Column header="Action" style="width: 130px">
                        <template #body="{ data }">
                            <!-- <SplitButton label="Set Priority" dropdownIcon="pi pi-cog" :model="items(data)" class="mr-2"/> -->
                            <Button icon="pi pi-check"
                                :disabled="data.status !== 'ongoing' && data.status !== 'serving'"
                                @click="completeTransaction" class="p-button-sm mr-2" />
                            <Button severity="warn" icon="pi pi-megaphone" aria-label="Save"
                                @click="btn_call(data.queue_id)" class="p-button-sm mr-2" />
                            <!-- <Button severity="danger" icon="pi pi-arrow-circle-right" aria-label="Save"
                                class="p-button-sm" /> -->
                        </template>
                    </Column>
                </DataTable>
            </div>

            <div class="flex w-full flex-col gap-5 rounded border bg-gray-50 p-4 shadow md:w-1/4" style="height: 72%">
                <div class="grid grid-cols-1 gap-3 p-3">
                    <button @click="callNextClient" class="button-action"><i class="pi pi-forward mb-1 text-base"></i>
                        NEXT</button>
                    <button @click="priorityModal = true" class="button-action"><i
                            class="pi pi-star mb-1 text-base"></i>
                        PRIORITY</button>
                    <button @click="transferModal = true" class="button-action"><i
                            class="pi pi-share-alt mb-1 text-base"></i>
                        TRANSFER</button>
                </div>
                <!-- <Fieldset legend="Holding Area">
                    <DataTable showGridlines size="small" :value="clients" :column="5" scrollHeight="30vh"
                        dataKey="queue_number" :metaKeySelection="false" @rowSelect="onRowSelect">
                        <Column field="counter_name" header="Counter" />
                        <Column field="queue_number" header="Queue No." />
                        <Column header="Action">
                            <template #body="{ data }">
                                <Button icon="pi pi-home" aria-label="Save" />

                            </template>
                        </Column>
                    </DataTable>
                </Fieldset>
                <Fieldset legend="Recall Area">

                    <DataTable showGridlines size="small" :value="clients" :rows="5" scrollHeight="30vh"
                        dataKey="queue_number" :metaKeySelection="false" @rowSelect="onRowSelect">
                        <Column field="counter_name" header="Counter" />
                        <Column field="queue_number" header="Queue No." />
                        <Column header="Action">
                            <template #body="{ data }">
                                <Button icon="pi pi-home" aria-label="Save" />

                            </template>
                        </Column>
                    </DataTable>
                </Fieldset> -->
            </div>
        </div>
    </div>
</template>
<style scoped>
.highlight-row {
    background-color: #e0f2fe !important;
    /* Light blue */
}

.button-action {
    @apply flex flex-col items-center justify-center rounded-xl bg-blue-950 p-3 text-sm font-bold text-white shadow-md transition-all hover:brightness-110;
}

.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.4s ease;
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(20px);
}

.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-20px);
}
</style>
