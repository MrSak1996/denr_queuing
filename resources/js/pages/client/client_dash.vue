<script lang="ts" setup>
import axios from 'axios';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { useSpeechSynthesis } from '@vueuse/core';
import bg3 from '../../../images/bg3.png';
import bg4 from '../../../images/bg4.png';
import bg5 from '../../../images/bg5.png';

const images = [bg3, bg4, bg5];
const queueList = ref<any[]>([]);
const currentBg = ref(images[0]);
const counterIds = [1, 2, 3, 4];
const lastSpoken = ref<string[]>([]);
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
  const preferredVoices = voices.filter((voice) =>
    /female|hazel|zira|susan|samantha|siri/i.test(voice.name)
  );
  selectedVoice.value = preferredVoices[0] || voices.find(v => /en/i.test(v.lang)) || voices[0];
};
if (window.speechSynthesis.onvoiceschanged !== undefined) {
  window.speechSynthesis.onvoiceschanged = loadVoices;
}
loadVoices();

const fetchCurrentClients = async () => {
  try {
    const response = await axios.get(`/api/current-clients`);
    const queueData = response.data.data;
    counters.value = counterIds.map((id) => {
      const match = queueData.find((q) => q.service_counter_id === id);
      return match || { service_counter_id: id, queue_number: null };
    });
  } catch (error) {
    console.error('Error fetching counters:', error);
  }
};

const fetchQueueList = async () => {
  try {
    const response = await axios.get(`/api/queue_list`);
    queueList.value = response.data.data;
  } catch (error) {
    console.error('Error fetching queue list:', error);
  }
};

watch(
  queueList,
  (newList) => {
    if (!isSupported.value || speaking) return;

    newList.forEach((item) => {
      const currentNumber = item.queue_number;
      const counterId = item.counter_id;

      if (!currentNumber || lastSpoken.value.includes(currentNumber)) return;

      const utterance = new SpeechSynthesisUtterance(
        `Queue number ${currentNumber}, please proceed to counter ${counterId || 1}.`
      );

      if (selectedVoice.value) {
        utterance.voice = selectedVoice.value;
      }

      speaking = true;
      window.speechSynthesis.cancel();
      window.speechSynthesis.speak(utterance);
      lastSpoken.value.push(currentNumber);

      utterance.onstart = () => {
        console.log('Speech started');
      };

      utterance.onend = () => {
        console.log('Speech ended');
        speaking = false;
      };
    });
  },
  { deep: true }
);

onMounted(() => {
  if (!window.speechSynthesis) {
    console.error('Speech synthesis is not supported in this browser.');
    return;
  }

  fetchCurrentClients();
  const intervalId = setInterval(fetchCurrentClients, 1000);
  const QueueList = setInterval(fetchQueueList, 1000);

  onUnmounted(() => {
    clearInterval(intervalId);
    clearInterval(QueueList);
  });

  let index = 0;
  setInterval(() => {
    index = (index + 1) % images.length;
    currentBg.value = images[index];
  }, 5000);
});
</script>

<template>
  <div class="grid grid-cols-4 gap-6">
    <div v-for="counter in counters" :key="counter.service_counter_id"
      class="mx-2 inline-block h-24 rounded-sm bg-[#0d4917] p-3 shadow-md">
      <div class="text-center text-6xl font-semibold leading-none text-white">
        {{ 'COUNTER ' + counter.service_counter_id }}
      </div>
      <div class="mt-4">
        <div class="flex items-center justify-center">
          <div
            class="mt-3 rounded-lg border-4 border-[#0d4917] bg-white px-2 py-1 text-[150px] font-bold leading-none text-[#132b57]">
            {{ counter.queue_number ?? '----' }}
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
        :style="{ backgroundImage: `url(${currentBg})` }">
      </div>

      <!-- Right Sidebar -->
      <div class="flex w-full flex-col gap-0 rounded px-6 py-5 text-center md:w-1/3" v-for="queue in queueList"
        :key="queue.id">
        <div class="mt-3 text-5xl font-semibold text-[#132b57]">Ticket Number</div>
        <div class="blink text-[150px] font-bold leading-none text-red-700 md:text-[200px]">
          {{ queue.queue_number }}
        </div>
        <div class="rounded-lg bg-slate-900 px-4 py-2 text-6xl text-white">
          Please proceed to <span class="font-bold">COUNTER {{ queue.counter_id }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes blink {
  0%, 100% {
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
