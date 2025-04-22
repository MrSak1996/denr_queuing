<script lang="ts" setup>
import { ref, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
const toast = useToast();

import axios from 'axios';
const emit = defineEmits(['close', 'proceed']);
const clients = ref<any[]>([]);
const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    queue: {
        type: Object,
    },
    counterId: {
        type:Number
    }
});
const items = (client: any) => [
    {
        label: 'PWD',
        icon: 'pi pi-exclamation-circle',
        command: () => {
            set_client_priority(client, 1);
            closeModal();
        },
    },
    {
        label: 'Senior Citizen',
        icon: 'pi pi-user',
        command: () => {
            set_client_priority(client, 2);
            closeModal();
        },
    },
    {
        label: 'Pregnant Women',
        icon: 'pi pi-user-plus',
        command: () => {
            set_client_priority(client, 3);
            closeModal();
        },
    },
    {
        label: 'Regular Client',
        icon: 'pi pi-user-edit',
        command: () => {
            set_client_priority(client, 4);
            closeModal();
        },
    },

];
const get_client = async (counterId) => {
    try {
        const response = await axios.get(`/api/clients?counter_id=${counterId}`);
        clients.value = response.data;
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
        toast.add({ severity: 'success', summary: 'Priority Set', detail: 'Client set to priority lane', life: 3000 });
    } catch (error) {
        console.error('Error setting client priority:', error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'Could not set client to priority lane', life: 3000 });
    }
};
const closeModal = () => {
    emit('close');
};


onMounted(() => {
    get_client(props.counterId  );
});
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" role="dialog"
        tabindex="-1" aria-labelledby="progress-modal">
        <div
            class="mx-4 w-full max-w-lg transform rounded-xl border bg-white shadow-sm transition-transform duration-500 dark:border-neutral-700 dark:bg-neutral-800">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b px-4 py-3 dark:border-neutral-700">
                <h3 class="text-lg font-semibold">Setting-Up Priority </h3>
                <button @click="closeModal"
                    class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">✖</button>
            </div>

            <div class="qr-batch-generator">
                <div class="input-section">
                    <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-orange-800 dark:bg-gray-800 dark:text-red-400"
                        role="alert">
                        Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been
                        the industry's standard dummy
                        text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to
                        make a type specimen book. It ha
                    </div>
                    <div class="grid grid-cols-5 gap-2 max-h-[250px] overflow-y-auto p-2">

                        <SplitButton v-for="client in clients" :key="client.id" :label="client.queue_number"
                            :model="items(client)" outlined severity="warn"></SplitButton>

                    </div>

                    <Button label="Save" severity="primary" />

                </div>

                <div class="actions"></div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.qr-batch-generator {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    max-width: 800px;
    margin: 0 auto;
}

.input-section {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.number-inputs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.input-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

label {
    font-weight: 500;
    color: #2c3e50;
}

input {
    padding: 0.8rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
}

input:disabled {
    background-color: #f5f5f5;
    cursor: not-allowed;
}

.logo-preview {
    text-align: center;
    margin-top: 1rem;
}

.logo-img {
    width: 100px;
    height: auto;
    margin-top: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.preview {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1.5rem;
}

.error {
    color: #dc3545;
    margin-bottom: 1rem;
    padding: 0.5rem;
    background-color: #f8d7da;
    border-radius: 4px;
}

.generate-btn {
    background-color: #0f766e;
    color: white;
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    width: 100%;
}

.generate-btn:hover:not(:disabled) {
    background-color: #3aa876;
}

.generate-btn:disabled {
    background-color: #a8d5c2;
    cursor: not-allowed;
}

.progress-bar {
    margin-top: 1rem;
    background-color: #f0f0f0;
    border-radius: 4px;
    height: 20px;
    position: relative;
    overflow: hidden;
}

.progress-fill {
    background-color: #42b883;
    height: 100%;
    transition: width 0.3s ease;
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: #000;
    font-size: 0.8rem;
    font-weight: 500;
}
</style>