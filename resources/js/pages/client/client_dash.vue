<script lang="ts" setup>
import { useSpeechSynthesis } from '@vueuse/core';
import axios from 'axios';
import { onMounted, ref } from 'vue';
import bg3 from '../../../images/bg3.png';
import bg4 from '../../../images/bg4.png';
import bg5 from '../../../images/bg5.png';

const images = [bg3, bg4, bg5];
const queueList = ref<any[]>([]);
const currentBg = ref(images[0]);
const counterIds = [1, 2, 3, 4];
const lastSpoken = ref<string[]>([]);
const spokenTimestamps = ref({}); // key: queue_number, value: last called_at timestamp
const { isSupported } = useSpeechSynthesis();
let speaking = false;

interface CounterClient {
  counter: number;
  ticket_number: number;
}

const counters = ref<CounterClient[]>([]);

// VOICE SELECTION: Pick a female voice like Hazel, Zira, Siri
const selectedVoice = ref<SpeechSynthesisVoice | null>(null);
const loadVoices = () => {
  const voices = window.speechSynthesis.getVoices();
  const preferredVoices = voices.filter((voice) => /female|hazel|zira|susan|samantha|siri/i.test(voice.name));
  selectedVoice.value = preferredVoices[0] || voices.find((v) => /en/i.test(v.lang)) || voices[0];
};
if (window.speechSynthesis.onvoiceschanged !== undefined) {
  window.speechSynthesis.onvoiceschanged = loadVoices;
}
loadVoices();

const fetchCurrentClients = async () => {
  try {
    const response = await axios.get(`/api/current-clients`);
    const queueData = response.data.data;

    // Map counters based on counterIds
    counters.value = counterIds.map((id) => {
      const match = queueData.find((q) => q.service_counter_id === id);
      return match || { service_counter_id: id, queue_number: null };
    });

    // Subscribe to WebSocket channels for each counter
    counterIds.forEach((id) => {
      window.Echo.channel(`client.counter.${id}`).listen('.CounterEvent', (e: any) => {
        const updatedCounterId = e.service_counter_id;
        const updatedQueueNumber = e.queue_number;

        const index = counters.value.findIndex((counter) => counter.service_counter_id === updatedCounterId);

        if (index !== -1) {
          counters.value[index].queue_number = updatedQueueNumber;
          // counters.value[index].status = e.status; // if needed
        } else {
          counters.value.push({
            service_counter_id: updatedCounterId,
            queue_number: updatedQueueNumber,
            // status: e.status, // if needed
          });
        }
      });
    });
  } catch (error) {
    console.error('Error fetching counters:', error);
  }
};

const fetchQueueList = async () => {
  try {
    const response = await axios.get(`/api/queue_list`);
    const newList = response.data.data;

    queueList.value = newList;

    if (!isSupported.value || speaking) return;

    newList.forEach((item) => {
      const currentNumber = item.queue_number;
      const counterId = item.counter_id;
      const calledAt = item.called_at;

      if (!currentNumber || !calledAt) return;

      const lastSpokenTimestamp = spokenTimestamps.value[currentNumber];

      // Speak only if `called_at` is new or updated
      if (lastSpokenTimestamp === calledAt) return;
      const utterance = new SpeechSynthesisUtterance(`Queue number ${currentNumber}, please proceed to counter ${counterId || 1}.`);

      if (selectedVoice.value) {
        utterance.voice = selectedVoice.value;
      }

      speaking = true;
      window.speechSynthesis.cancel();
      window.speechSynthesis.speak(utterance);

      // Save the latest called_at timestamp
      spokenTimestamps.value[currentNumber] = calledAt;

      utterance.onstart = () => {
        console.log('Speech started');
      };

      utterance.onend = () => {
        console.log('Speech ended');
        speaking = false;
      };
    });
  } catch (error) {
    console.error('Error fetching queue list:', error);
  }
};

onMounted(() => {
  fetchCurrentClients();
  fetchQueueList();
  // const QueueList = setInterval(fetchQueueList, 1000);

  // onUnmounted(() => {
  //   clearInterval(QueueList);
  // });

  // let index = 0;
  // setInterval(() => {
  //   index = (index + 1) % images.length;
  //   currentBg.value = images[index];
  // }, 5000);
});
</script>

<template>
  <div class="grid grid-cols-3 gap-6">
    <div v-for="counter in counters.slice(0, 3)" :key="counter.service_counter_id"
      class="mx-2 inline-block h-24 rounded-sm bg-[#0d4917] p-3 shadow-md">
      <div class="text-center text-6xl font-semibold leading-none text-white">
        {{ counter.counter_name }}
      </div>
      <div v-if="(counter.service_counter_id == 1)">
        <span class="text-white">(LICENSED, PERMITTING, CERTIFICATION AND OTHER CONCERNS)</span>
      </div>
      <div class="mt-1">
        <div class="flex items-center justify-center">
          <div
            class="rounded-lg border-4 border-[#0d4917] bg-white px-2 py-1 text-[190px] font-bold leading-none text-[#132b57]"
            :class="{ 'mt-6': counter.service_counter_id === 2 || counter.service_counter_id === 3 }">
            {{ counter.queue_number }}

          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-span-3 mt-[200px] flex max-w-full flex-col gap-4">
    <div class="mt-3 flex flex-col gap-5 rounded-xl text-[#132b57] md:flex-row">
      <!-- Left Content -->
      <div
        class="h-[500px] w-full flex-1 overflow-hidden rounded border bg-cover bg-center p-4 text-center text-white shadow transition-all duration-1000 ease-in-out"
        :style="{ backgroundImage: `url(${currentBg})` }"></div>

      <!-- Right Sidebar -->
      <div class="flex w-full flex-col gap-0 rounded px-6 py-5 text-center md:w-1/3" v-for="queue in queueList"
        :key="queue.id">
        <div class="mt-3 text-5xl font-semibold text-[#132b57]">Ticket Number</div>
        <div class="blink text-[150px] font-bold leading-none text-red-700 md:text-[200px]">
          {{ queue.queue_number }}
        </div>
        <div class="rounded-lg bg-slate-900 px-4 py-2 text-6xl text-white">
          Please proceed to <span class="font-bold">{{ queue.counter_name }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes blink {

  0%,
  100% {
    opacity: 1;
  }

  50% {
    opacity: 0;
  }
}

.blink {
  animation: blink 1s infinite;
}

.bg-gradient-to-r {
  background: linear-gradient(to right, #f59e0b, #d97706);
}

.transition-transform {
  transition: transform 0.3s ease-in-out;
}

.transform {
  transform: scale(1);
}

.hover\:scale-105:hover {
  transform: scale(1.05);
}

@keyframes fadeIn {
  0% {
    opacity: 0;
  }

  100% {
    opacity: 1;
  }
}

.counter-box {
  animation: fadeIn 0.5s ease-in-out;
}

.ticket-count {
  font-size: 1.125rem;
  font-weight: 600;
  color: #4b5563;
}
</style>
