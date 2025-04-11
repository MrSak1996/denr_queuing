<script lang="ts" setup>
import axios from 'axios';
import Chip from 'primevue/chip';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import modal_transfer from '@/pages/modal/modal_transfer.vue';
import { useToast } from 'primevue/usetoast';
import { nextTick, onMounted, onUnmounted, ref } from 'vue';
const toast = useToast();

const clients = ref<any[]>([]);
const queue = ref<any[]>([]);
const servingTime = ref('00:00:00');
const transferModal = ref(false)

const maxQueue = 50;
let priorityCounter = 1;
let currentPriorityIndex = -1;



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
            status: 'Completed',
        });

        clients.value.shift();
        queue.value.shift();

        // Re-assign the array to trigger reactivity
        clients.value = [...clients.value];

        // Toast message to notify success
        toast.add({ severity: 'success', summary: 'Client Served', detail: `Client ${currentClient.queue_number} has been completed`, life: 3000 });
        get_client();
        save_queue_logs(currentClient.queue_id, currentClient.counter_name);
    } catch (error) {
        console.error('Error updating client status:', error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'Could not update client status', life: 3000 });
    }
};

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

const get_client = async () => {
    try {
        const response = await axios.get('/api/clients');
        clients.value = response.data;

        // Limit to first 5 items before mapping
        queue.value = response.data.slice(0, 2).map((client: any) => ({
            queue_id: client.queue_id,
            queue_number: client.queue_number,
            status: client.status,
        }));

        console.log(clients.value); // All clients
        console.log(queue.value);   // First 5 queues only
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
onMounted(() => {
    get_client();
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
        <modal_transfer v-if="transferModal" :open="transferModal" @close="transferModal = false"></modal_transfer>

        <transition-group name="fade-slide" tag="div" class="flex w-full flex-col items-center gap-4">
            <div v-for="(pac, index) in queue" :key="pac.queue_id + '-' + pac.isPriorityLane"
                class="w-full max-w-sm rounded-xl border p-6 text-center shadow-md"
                :class="pac.isPriorityLane ? 'bg-yellow-200' : 'bg-gray-50'">
                <div class="flex flex-col items-center gap-2">
                    <div v-if="index === 0" class="text-sm font-semibold text-orange-600">Current Serving</div>
                    <div class="text-lg font-bold text-blue-900">Token Number</div>

                    <div class="mt-2 rounded-lg border-4 border-orange-500 px-10 py-6 text-6xl font-bold"
                        :class="pac.isPriorityLane ? 'text-yellow-800' : 'text-orange-500'">
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
    <div class="col-span-3 flex w-full max-w-full flex-col gap-4">
        <div class="flex w-full flex-col gap-4 rounded-xl border  bg-gray-100 p-6 shadow-sm">
            <div class="text-xl font-semibold text-gray-800">Account Information</div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div class="flex items-center gap-3">
                    <span class="w-36 text-sm font-medium text-gray-600">Account Name:</span>
                    <span class="rounded-md bg-slate-800 px-3 py-1 text-sm text-white">Mark Kim A. Sacluti</span>
                </div>

                <div class="flex items-center gap-3">
                    <span class="w-36 text-sm font-medium text-gray-600">Account Type:</span>
                    <span class="rounded-md bg-slate-800 px-3 py-1 text-sm text-white">Administrator</span>
                </div>

                <div class="flex items-center gap-3">
                    <span class="w-36 text-sm font-medium text-gray-600">Email Address:</span>
                    <span class="rounded-md bg-slate-800 px-3 py-1 text-sm text-white">marksacluti@example.com</span>
                </div>

                <div class="flex items-center gap-3">
                    <span class="w-36 text-sm font-medium text-gray-600">Status:</span>
                    <span class="rounded-md bg-green-600 px-3 py-1 text-sm text-white">Active</span>
                </div>
            </div>
        </div>


        <div class="flex flex-col md:flex-row gap-5 rounded-xl text-slate-900 h-screen">
            <!-- DataTable Section (Larger Width) -->
            <div class="flex-1 overflow-hidden rounded p-4 shadow max-h-[70vh]">
                <DataTable size="small" paginator showGridlines :rows="8" dataKey="id" filterDisplay="menu"
                    :value="clients" scrollable scrollHeight="flex">
                    <Column field="counter_name" header="Counter"></Column>
                    <Column field="queue_number" header="Queue No."></Column>
                    <Column field="service_name" header="Service"></Column>
                    <Column field="priority_level" header="Priority Level">
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
                    </Column>
                    <Column field="status" header="Status"></Column>
                    <Column field="queued_at" header="Date/Time"></Column>
                    <Column header="Action">
                        <template #body="{ data }">
                            <SplitButton label="Set Priority" dropdownIcon="pi pi-cog" severity="secondary"
                                :model="items(data)" />
                        </template>
                    </Column>
                </DataTable>
            </div>

            <!-- Button Section (Smaller Width) -->
            <div class="w-full md:w-1/4 md:h-1/4 grid grid-cols-3 gap-5 p-4">
                <button @click="call_next_client" class="button-action">
                    <i class="pi pi-forward mb-1 text-base"></i> NEXT
                </button>
                <button @click="priorityLane" class="button-action">
                    <i class="pi pi-star mb-1 text-base"></i> PRIORITY
                </button>
                <button @click="transferModal=true" class="button-action">
                    <i class="pi pi-share-alt mb-1 text-base"></i> TRANSFER
                </button>
                <button @click="call_next_client" class="button-action">
                    <i class="pi pi-pause mb-1 text-base"></i> HOLD
                </button>
                <button @click="call_next_client" class="button-action">
                    <i class="pi pi-refresh mb-1 text-base"></i> RECALL
                </button>
                <button @click="call_next_client" class="button-action">
                    <i class="pi pi-exclamation-triangle mb-1 text-base"></i> ERROR
                </button>
            </div>
        </div>

    </div>
</template>

<style scoped>
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
