<script lang="ts" setup>
import axios from 'axios';
import Chip from 'primevue/chip';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import io from 'socket.io-client';
import { useToast } from 'primevue/usetoast';
import { nextTick, onMounted,onUnmounted, ref } from 'vue';

const toast = useToast();

const clients = ref<any[]>([]);
const queue = ref<any[]>([]);

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
            set_client_priority(client,1);
        },
    },
    {
        label: 'Senior Citizen', 
        icon: 'pi pi-user',
        command: () => {
            set_client_priority(client,2);
        },
    },
    {
        label: 'Pregnant Women', 
        icon: 'pi pi-user-plus',
        command: () => {
            set_client_priority(client,3);
        },
    },
    {
        label: 'Regular Client', 
        icon: 'pi pi-user-edit',
        command: () => {
            set_client_priority(client,4);
        },
    },
   
];

const get_client = async () => {
    try {
        const response = await axios.get('/api/clients');
        clients.value = response.data; // Update clients with the fetched data
        // Set queue data, using queue_number and queue_id directly from the response
        queue.value = response.data.map((client: any) => ({
            queue_id: client.queue_id,
            queue_number: client.queue_number,
            status: client.status,
        }));
        console.log(clients.value); // Check the queue data here for debugging
    } catch (error) {
        console.error('Error fetching client data:', error);
    }
};

const set_client_priority = async (client:any,priority_level:any) => {
    if (clients.value.length === 0) {
        toast.add({ severity: 'warn', summary: 'Queue Empty', detail: 'No more clients in the queue!', life: 3000 });
        return;
    }
    try {
        await axios.post('/api/set_client_priority',
         { 
            client_id:client.client_id,
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

onMounted(() => {
    get_client();
//     const intervalId = setInterval(get_client,10000); // Fetch data every 5 seconds
//     onUnmounted(() => {
//     clearInterval(intervalId); 
//   });
});
</script>

<template>
    <div class="col-span-1 flex flex-col items-center justify-start gap-4">
        <Toast />
        <transition-group name="fade-slide" tag="div" class="flex w-full flex-col items-center gap-4">
            <!-- Check if the queue array has items -->
            <div
                v-for="(pac, index) in queue"
                :key="pac.number + '-' + pac.isPriorityLane"
                class="w-full rounded-xl border p-10 text-center"
                :class="{
                    'bg-yellow-300': pac.isPriorityLane, // Yellow background for priority PACs
                    'bg-gray-100': !pac.isPriorityLane, // Default background for normal PACs
                }"
            >
                <div class="flex flex-col items-center gap-1">
                    <div  v-if="pac.queue_number" :class="['text-6xl font-bold', pac.isPriorityLane ? 'text-yellow-800' : index === 0 ? 'text-[#1a2d42]' : 'text-slate-900']">
                        {{ pac.queue_number }}
                    </div>
                    <div  class="text-sm font-bold text-slate-900">
                    </div>
                    <button  class="w-auto p-3 rounded-md bg-[#1a2d42] py-2 text-sm font-bold text-green-200" @click="call_next_client">
                        {{ pac.status.toUpperCase() }}
                    </button>
                </div>
            </div>
        </transition-group>
    </div>

    <!-- CONTROLS -->
    <div class="col-span-3 flex w-full max-w-full flex-col gap-4">
        <div class="flex w-full flex-col gap-2 rounded-xl border bg-gray-100 p-4">
            <div class="flex items-center">
                <div class="mr-5 text-lg">No. of Client Served:</div>
                <div class="rounded-md border bg-white px-4 py-1 font-bold">143</div>
            </div>
            <div class="mt-2 flex gap-2">
                <button class="rounded-md bg-slate-800 px-3 py-1 text-sm text-white">Average Waiting Time:</button>
                <button class="rounded-md bg-slate-800 px-3 py-1 text-sm text-white">Skipped Count</button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 rounded-xl p-0 text-slate-900 md:grid-cols-3">
            <!-- DataTable Section (Larger Width) -->
            <div class="col-span-2 overflow-hidden rounded p-4 shadow">
                <DataTable :value="clients" scrollable scrollHeight="flex">
                    <Column field="counter_name" header="Counter"></Column>
                    <Column field="queue_number" header="Queue No."></Column>
                    <Column field="service_name" header="Service"></Column>
                    <Column field="priority_level" header="Priority Level">
                        <template #body="{ data }">
                            <Chip class="py-0 pl-0 pr-4">
                                <span
                                    :class="
                                        data.priority_level === 'PWD' || data.priority_level == 'Senior'
                                            ? 'bg-yellow-200'
                                            : 'bg-gradient-to-b from-[#2E4156] to-[#1A2D42] text-white'
                                    "
                                    class="text-primary-contrast flex h-8 w-8 items-center justify-center rounded-full"
                                    >C</span
                                >
                                <span class="ml-2 font-medium">{{ data.priority_level }}</span>
                            </Chip>
                        </template>
                    </Column>
                    <Column field="status" header="Status"></Column>
                    <Column field="queued_at" header="Date/Time"></Column>

                    <Column header="Action">
                        <template #body="{ data }">
                            <SplitButton label="Actions" icon="pi pi-check" dropdownIcon="pi pi-cog" :model="items(data)" />
                        </template>
                    </Column>
                </DataTable>
            </div>

            <!-- Button Section (Smaller Width) -->
            <div class="grid grid-cols-1 gap-5 rounded-xl p-0 text-slate-900 md:grid-cols-3">
                <!-- NEXT -->
                <button
                    class="flex flex-col items-center justify-center rounded-xl bg-gradient-to-b from-[#2E4156] to-[#1A2D42] p-3 text-sm font-bold text-white shadow-md transition-all hover:brightness-110"
                    @click="call_next_client"
                >
                    <i class="pi pi-forward mb-1 text-base"></i>
                    NEXT
                </button>

                <!-- PRIORITY -->
                <button
                    class="flex flex-col items-center justify-center rounded-xl bg-gradient-to-b from-[#2E4156] to-[#1A2D42] p-3 text-sm font-bold text-white shadow-md transition-all hover:brightness-110"
                    @click="priorityLane"
                >
                    <i class="pi pi-star mb-1 text-base"></i>
                    PRIORITY
                </button>

                <!-- TRANSFER -->
                <button
                    class="flex flex-col items-center justify-center rounded-xl bg-gradient-to-b from-[#2E4156] to-[#1A2D42] p-3 text-sm font-bold text-white shadow-md transition-all hover:brightness-110"
                    @click="call_next_client"
                >
                    <i class="pi pi-share-alt mb-1 text-base"></i>
                    TRANSFER
                </button>

                <!-- HOLD -->
                <button
                    class="flex flex-col items-center justify-center rounded-xl bg-gradient-to-b from-[#2E4156] to-[#1A2D42] p-3 text-sm font-bold text-white shadow-md transition-all hover:brightness-110"
                    @click="call_next_client"
                >
                    <i class="pi pi-pause mb-1 text-base"></i>
                    HOLD
                </button>

                <!-- RECALL -->
                <button
                    class="flex flex-col items-center justify-center rounded-xl bg-gradient-to-b from-[#2E4156] to-[#1A2D42] p-3 text-sm font-bold text-white shadow-md transition-all hover:brightness-110"
                    @click="call_next_client"
                >
                    <i class="pi pi-refresh mb-1 text-base"></i>
                    RECALL
                </button>

                <!-- ERROR -->
                <button
                    class="flex flex-col items-center justify-center rounded-xl bg-gradient-to-b from-[#2E4156] to-[#1A2D42] p-0 text-sm font-bold text-white shadow-md transition-all hover:brightness-110"
                    @click="call_next_client"
                >
                    <i class="pi pi-exclamation-triangle mb-1 text-base"></i>
                    ERROR
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
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
