<script lang="ts" setup>
import { useToast } from 'primevue/usetoast';
import { onMounted, ref,nextTick } from 'vue';
import axios from 'axios';
const emit = defineEmits(['close', 'proceed']);

import modal_generate_queue from './modal_generate_queue.vue';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
  serviceType: Number, // You'll get 1, 2 or 3 here

});



const showQueueGenerateModal = ref(false);
const selectedCounterId = ref('');
const selectedQueueNo = ref('');

const openQueueGenerateModal = async (counterId: number, priority_level_id: number) => {
    await nextTick();
    try {
        const response = await axios.post('/api/transaction', {
            counter_id: counterId,
            priorityLevel: priority_level_id
        });

        const { message, queue_number: qNum } = response.data;
        if (message) {

            selectedCounterId.value = response.data.counter_name;
            selectedQueueNo.value = qNum;
            showQueueGenerateModal.value = true;
    emit('close')
        } else {
            console.warn('Response received but no message found.');
        }
    } catch (error) {
        console.error('❌ Error during transaction:', error);
    }
  // Open second modal with data
 
};

const closeQueueGenerateModal = () => {
  showQueueGenerateModal.value = false;
};


const closeModal = () => {
    emit('close');
};

onMounted(() => {

});
</script>

<template>



    <!-- This is your first modal -->
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" role="dialog"
        tabindex="-1" aria-labelledby="progress-modal">
        <div
            class="mx-4 w-full max-w-lg transform rounded-xl border bg-white shadow-sm transition-transform duration-500 dark:border-neutral-700 dark:bg-neutral-800">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b px-4 py-3 dark:border-neutral-700">
                <h3 class="text-lg font-semibold"></h3>
                <button @click="closeModal" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">✖</button>
            </div>

            <div class="qr-batch-generator">
                <div class="input-section">
                    <div
                        class="flex flex-col justify-center items-center mb-4 rounded-lg p-8 text-slate-800 dark:text-slate-100  dark:bg-gray-800"
                        role="alert">
                        <p class="text-xl mb-2 font-medium text-blue-800">Please choose Client Type</p>

                        <Button
                            class="h-[100px] w-full border !bg-gradient-to-r from-blue-950 to-green-500 text-white md:w-full text-center p-2 mb-4"
                            @click="openQueueGenerateModal(props.serviceType,4)">
                            <div class="flex flex-col items-center justify-center leading-tight">
                                <span class="font-extrabold text-[40px]">REGULAR</span>
                            </div>
                        </Button>

                        <Button
                            class="h-[100px] w-full border !bg-gradient-to-r from-blue-950 to-green-700 text-white md:w-full text-center p-2"
                            @click="openQueueGenerateModal(props.serviceType,1)">
                            <div class="flex flex-col items-center justify-center leading-tight">
                                <span class="font-extrabold text-[40px]">PRIORITY LANE</span>
                                <span class="text-[30px] md:text-[14px] text-center">
                                    (PWD/SENIOR CITIZEN/PREGNANT)
                                </span>
                            </div>
                        </Button>
                    </div>
                </div>
                <div class="actions"></div>
            </div>
        </div>
    </div>

    <modal_generate_queue
    v-if="showQueueGenerateModal"
    :queueGen="showQueueGenerateModal"
    :counterId="selectedCounterId"
    :queue_no="selectedQueueNo"
    
    @close="closeQueueGenerateModal"
  />
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