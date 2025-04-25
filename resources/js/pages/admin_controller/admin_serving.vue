<script lang="ts" setup>
import axios from 'axios';
import Chip from 'primevue/chip';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Fieldset from 'primevue/fieldset';
import modal_transfer from '@/pages/modal/modal_transfer.vue';
import modal_priority from '../modal/modal_priority.vue';
import modal_holding_area from '../modal/modal_holding_area.vue';
import modal_recall from '../modal/modal_recall.vue';
import { useToast } from 'primevue/usetoast';
import { nextTick, onMounted, onUnmounted, ref } from 'vue';
import { type SharedData, type User } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { useSpeechSynthesis } from '@vueuse/core';
const { isSupported } = useSpeechSynthesis();
let speaking = false;

const page = usePage<SharedData>();
const user = page.props.auth.user as User;
const toast = useToast();
const clients = ref<any[]>([]);
const queue = ref<any[]>([]);
const selectedProduct = ref();
const servingTime = ref('00:00:00');
const transferModal = ref(false)
const priorityModal = ref(false)
const holdingModal = ref(false)
const recallModal = ref(false)
const selectedVoice = ref<SpeechSynthesisVoice | null>(null);
const maxQueue = 50;
let priorityCounter = 1;
let currentPriorityIndex = -1;

const loadVoices = () => {
    const voices = window.speechSynthesis.getVoices();
    const preferredVoices = voices.filter((voice) =>
        /female|hazel|zira|susan|samantha|siri/i.test(voice.name)
    );
    selectedVoice.value = preferredVoices[0] || voices.find(v => /en/i.test(v.lang)) || voices[0];
};
if (window.speechSynthesis.onvoiceschanged !== undefined) {
    window.speechSynthesis.onvoiceschanged = loadVoices;
}
loadVoices();

const call_next_client = async () => {
    if (clients.value.length === 0) {
        toast.add({ severity: 'warn', summary: 'Queue Empty', detail: 'No more clients in the queue!', life: 3000 });
        return;
    }

    // Mark the current client as completed in the frontend
    const currentClient = clients.value[0];
    currentClient.status = 'Completed';  // Update the status to 'Completed'
    // Send API request to update the client status in the database
    try {
        await axios.post('/api/update-client-status', {
            queue_id: currentClient.queue_id,
            status: 'serving',
        });

        clients.value.shift();
        queue.value.shift();

        // Re-assign the array to trigger reactivity
        clients.value = [...clients.value];

        // Toast message to notify success
        toast.add({ severity: 'success', summary: 'Client Served', detail: `Client ${currentClient.queue_number} has been completed`, life: 3000 });
        get_client(user.service_counter_id);
        save_queue_logs(currentClient.queue_id, currentClient.counter_name);
    } catch (error) {
        console.error('Error updating client status:', error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'Could not update client status', life: 3000 });
    }
};

const btn_completed_transaction = async () => {
    try {
        const currentClient = clients.value[0];
        await axios.post('/api/update-client-transaction', {
            queue_id: currentClient.queue_id,
            status: 'completed',
        });
        get_client(user.service_counter_id);
    } catch (error) {
        console.error('Error updating client transaction:', error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'Could not update client transaction', life: 3000 });
    }
}

const priorityLane = async () => {
    if (currentPriorityIndex !== -1) {
        // Fade out the current PAC-PRIO
        queue.value[currentPriorityIndex].isPriorityLane = false;
        // Wait for the animation to complete
        await nextTick();
    }

    const nextPacNumber = priorityCounter++; // Increment priority PAC counter
    if (nextPacNumber <= maxQueue) {
        // Insert the priority PAC at the front with `isPriorityLane` flag
        queue.value.unshift({ number: nextPacNumber, isPriorityLane: true, isNormal: false });
        currentPriorityIndex = 0; // Update the index of the new PAC-PRIO
    }

    // Now, sort the `PAC-PRIO` items only, ensuring they are always in order at the front
    const priorityItems = queue.value.filter((item) => item.isPriorityLane);
    const normalItems = queue.value.filter((item) => item.isNormal);

    // Sort the priority PACs by number in ascending order
    priorityItems.sort((a, b) => a.number - b.number);
    queue.value = [...priorityItems, ...normalItems];
};

const items = (client: any) => [
    {
        label: 'PWD',
        icon: 'pi pi-exclamation-circle',
        command: () => {
            set_client_priority(client, 1);
        },
    },
    {
        label: 'Senior Citizen',
        icon: 'pi pi-user',
        command: () => {
            set_client_priority(client, 2);
        },
    },
    {
        label: 'Pregnant Women',
        icon: 'pi pi-user-plus',
        command: () => {
            set_client_priority(client, 3);
        },
    },
    {
        label: 'Regular Client',
        icon: 'pi pi-user-edit',
        command: () => {
            set_client_priority(client, 4);
        },
    },

];

const get_client = async (counter_id) => {
    try {
        const response = await axios.get(`/api/clients?counter_id=${counter_id}`);
        clients.value = response.data;

        // Limit to first 5 items before mapping
        queue.value = response.data.slice(0, 2).map((client: any) => ({
            queue_id: client.queue_id,
            queue_number: client.queue_number,
            status: client.status,
        }));

    } catch (error) {
        console.error('Error fetching client data:', error);
    }
};

const set_client_priority = async (client: any, priority_level: any) => {
    if (clients.value.length === 0) {
        toast.add({ severity: 'warn', summary: 'Queue Empty', detail: 'No more clients in the queue!', life: 3000 });
        return;
    }
    try {
        await axios.post('/api/set_client_priority',
            {
                client_id: client.client_id,
                priority_level_id: priority_level
            });
        clients.value.shift();
        queue.value.shift();
        get_client(user.service_counter_id);
        toast.add({ severity: 'success', summary: 'Priority Set', detail: 'Client set to priority lane', life: 3000 });
    } catch (error) {
        console.error('Error setting client priority:', error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'Could not set client to priority lane', life: 3000 });
    }
};

const save_queue_logs = async (queue_id: any, counter_name: any) => {
    try {
        await axios.post('/api/save_queue_logs', {
            queue_id: queue_id,
            served_by: counter_name,
        });
    } catch (error) {
        console.error('Error saving queue logs:', error);
    }
}

const serving_tile = async () => {
    let seconds = 0;
    setInterval(() => {
        seconds++;
        const h = String(Math.floor(seconds / 3600)).padStart(2, '0');
        const m = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
        const s = String(seconds % 60).padStart(2, '0');
        servingTime.value = `${h}:${m}:${s}`;
    }, 1000);
}

const onRowSelect = (event) => {
    selectedProduct.value = event.data;
    toast.add({ severity: 'info', summary: 'Client Selected', detail: 'Queue Number: ' + event.data.queue_number, life: 3000 });
    transferModal.value = true;
};

const btn_call = async (queue_number) => {
    try {
        // const currentClient = clients.value[0];
        // if (!currentClient || !isSupported.value || speaking) return;

        // const utterance = new SpeechSynthesisUtterance(
        //     `Queue number ${queue_number}, please proceed to ${currentClient.counter_name || 'counter 1'}.`
        // );

        // if (selectedVoice.value) {
        //     utterance.voice = selectedVoice.value;
        // }

        // speaking = true;
        // window.speechSynthesis.cancel(); // Cancel any ongoing speech
        // window.speechSynthesis.speak(utterance);

        // utterance.onstart = () => {
        //     console.log('Calling client...');
        // };

        // utterance.onend = () => {
        //     console.log('Finished calling.');
        //     speaking = false;
        // };

        await axios.post('/api/recallClient', {
            queue_id: queue_number,
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






onMounted(() => {
    get_client(user.service_counter_id);
    serving_tile();
    //     const intervalId = setInterval(get_client,10000); // Fetch data every 5 seconds
    //     onUnmounted(() => {
    //     clearInterval(intervalId); 
    //   });
});
</script>

<template>
    <div class="col-span-1 flex flex-col items-center justify-start gap-4">
        <Toast />
        <modal_transfer v-if="transferModal" :queue="selectedProduct" :open="transferModal"
            @close="transferModal = false"></modal_transfer>
        <modal_priority v-if="priorityModal" :counterId="user.service_counter_id" :queue="selectedProduct"
            :open="priorityModal" @close="priorityModal = false"></modal_priority>
        <modal_holding_area v-if="holdingModal" :counterId="user.service_counter_id" :queue="selectedProduct"
            :open="holdingModal" @close="holdingModal = false"></modal_holding_area>
        <modal_recall v-if="recallModal" :counterId="user.service_counter_id" :queue="selectedProduct"
            :open="recallModal" @close="recallModal = false"></modal_recall>


        <transition-group name="fade-slide" tag="div" class="flex w-full flex-col items-center gap-4">
            <div v-for="(pac, index) in queue" :key="pac.queue_id + '-' + pac.isPriorityLane"
                class="w-full max-w-lg rounded border p-6 text-center shadow"
                :class="pac.isPriorityLane ? 'bg-yellow-200' : 'bg-gray-50'">
                <div class="flex flex-col items-center gap-2">
                    <div v-if="index === 0" class="text-sm font-semibold text-green-600">Current Serving</div>
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
                        class="mt-4  w-24 rounded-md bg-[#1a2d42] py-2 text-sm font-bold text-white transition-all hover:brightness-110"
                        @click="call_next_client">
                        {{ pac.status.toUpperCase() }}
                    </button>
                </div>
            </div>
        </transition-group>

    </div>

    <!-- CONTROLS -->
    <div class="col-span-3 flex max-w-full flex-col gap-4">
        <div class="flex flex-col md:flex-row gap-5 rounded-xl text-slate-900 h-screen">
            <div
                class="flex-1 overflow-hidden rounded p-4 shadow max-h-[100vh] w-full   border  text-center  bg-gray-50">

                <DataTable v-model:selection="selectedProduct" :value="clients" selectionMode="single" size="small"
                    paginator showGridlines :rows="20" filterDisplay="menu" scrollable scrollHeight="flex"
                    dataKey="queue_number" :metaKeySelection="false" @rowSelect="onRowSelect">

                    <Column field="counter_name" header="Counter"></Column>
                    <Column field="queue_number" header="Queue No.">
                        {{ data.queue_number }}
                    </Column>
                    <!-- <Column field="priority_level" header="Priority Level">
                        <template #body="{ data }">
                            <Chip class="py-0 pl-0 pr-4">
                                <span :class="data.priority_level === 'PWD' || data.priority_level == 'Senior'
                                    ? 'bg-orange-500 text-white'
                                    : 'bg-gradient-to-b from-[#2E4156] to-[#1A2D42] text-white'"
                                    class="text-primary-contrast flex h-8 w-8 items-center justify-center rounded-full">
                                    {{ data.priority_level.charAt(0) }}
                                </span>
                                <span class="ml-2 font-medium">{{ data.priority_level }}</span>
                            </Chip>
                        </template>
</Column> -->
                    <Column field="status" header="Status"></Column>
                    <Column field="queued_at" header="Date/Time"></Column>
                    <Column header="Action" style="width: 130px;">
                        <template #body="{ data }">
                            <!-- <SplitButton label="Set Priority" dropdownIcon="pi pi-cog" :model="items(data)" class="mr-2"/> -->
                            <Button icon="pi pi-check" aria-label="Save" @click="btn_completed_transaction"
                                class="p-button-sm mr-2" />
                            <Button severity="warn" icon="pi pi-megaphone" aria-label="Save"
                                @click="btn_call(data.queue_number)" class="p-button-sm mr-2" />
                            <Button severity="danger" icon="pi pi-arrow-circle-right" aria-label="Save"
                                class="p-button-sm" />
                        </template>
                    </Column>
                </DataTable>
            </div>

            <div class="w-full md:w-1/4 p-4  bg-gray-50 rounded shadow flex flex-col gap-5 border" style="height: 100%">
                <div class="grid grid-cols-1 gap-3">
                    <button @click="call_next_client" class="button-action">
                        <i class="pi pi-forward mb-1 text-base"></i> NEXT
                    </button>
                    <button @click="priorityModal = true" class="button-action">
                        <i class="pi pi-star mb-1 text-base"></i> PRIORITY
                    </button>
                    <button @click="transferModal = true" class="button-action">
                        <i class="pi pi-share-alt mb-1 text-base"></i> TRANSFER
                    </button>
                    <!-- <button @click="holdingModal = true" class="button-action">
                        <i class="pi pi-pause mb-1 text-base"></i> HOLD
                    </button> -->
                    <button @click="recallModal = true" class="button-action">
                        <i class="pi pi-refresh mb-1 text-base"></i> CALL AGAIN
                    </button>
                    <button @click="call_next_client" class="button-action">
                        <i class="pi pi-exclamation-triangle mb-1 text-base"></i> ERROR
                    </button>
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
    @apply flex flex-col items-center justify-center rounded-xl bg-gradient-to-b from-[#2E4156] to-[#1A2D42] p-3 text-sm font-bold text-white shadow-md transition-all hover:brightness-110;
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