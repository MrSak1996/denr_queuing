<script lang="ts" setup>
import { useSpeechSynthesis } from '@vueuse/core';
import axios from 'axios';
import { onMounted, onUnmounted, ref } from 'vue';
import bg3 from '../../../images/bg3.png';
import bg4 from '../../../images/bg4.png';
import bg5 from '../../../images/bg5.png';
import arta from '../../../images/arta_1.jpg';
import arta_2 from '../../../images/arta_2.jpg';
import eodb from '../../../images/eaodb_month.png';

// === Configs & Constants ===
const images = [bg3, eodb, arta, arta_2];
const counterIds = [1, 2, 3, 4];
const QUEUE_LIST_INTERVAL = 1000; // ms
const BG_ROTATE_INTERVAL = 5000; // ms

// === Reactive States ===
const queueList = ref<any[]>([]);
const counters = ref<CounterClient[]>([]);
const currentBg = ref(images[0]);
const spokenTimestamps = ref<Record<number, string>>({});
const selectedVoice = ref<SpeechSynthesisVoice | null>(null);
const { isSupported } = useSpeechSynthesis();
let speaking = false;

// For transferring state
const is_transferring = ref(false);
const transferInfo = ref<{ queue_number: number | null;old_counter: string | null; counter_name: string | null }>({
  queue_number: null,
  counter_name: null,
  old_counter: null
});

// === Interfaces ===
interface CounterClient {
  service_counter_id: number;
  queue_number: number | null;
  counter_name?: string;
}

// === Helpers ===
const rotateBackground = () => {
  let index = 0;
  return setInterval(() => {
    index = (index + 1) % images.length;
    currentBg.value = images[index];
  }, BG_ROTATE_INTERVAL);
};

const loadVoices = () => {
  const voices = window.speechSynthesis.getVoices();
  const preferredVoices = voices.filter((voice) => /female|hazel|zira|susan|samantha|siri/i.test(voice.name));
  selectedVoice.value = preferredVoices[0] || voices.find((v) => /en/i.test(v.lang)) || voices[0];
};

const speakText = (text: string, callback?: () => void) => {
  if (!isSupported.value) return;
  window.speechSynthesis.cancel(); // Cancel any ongoing speech

  const utterance = new SpeechSynthesisUtterance(text);
  if (selectedVoice.value) {
    utterance.voice = selectedVoice.value;
  }

  window.speechSynthesis.speak(utterance);

  utterance.onend = () => {
    if (callback) callback();
  };
};

const speakQueueCall = (queueNumber: number, counterId: number, calledAt: string) => {
  const lastSpokenTimestamp = spokenTimestamps.value[queueNumber];
  if (lastSpokenTimestamp === calledAt) return; // Skip if already spoken

  const text = `Queue number ${queueNumber}, please proceed to counter ${counterId || 1}.`;
  speaking = true;
  speakText(text, () => {
    speaking = false;
  });

  spokenTimestamps.value[queueNumber] = calledAt;
};

const speakClientTransfer = (queue_number: number, oldCounter: string, newCounter: string) => {
  const text = `Queue number ${queue_number} has been transferred from counter ${oldCounter} to counter ${newCounter}.`;

  is_transferring.value = true;
  transferInfo.value = { queue_number,old_counter:oldCounter, counter_name: newCounter };
  speakText(text, () => {
    is_transferring.value = false;
    transferInfo.value = { queue_number: null, old_counter: null, counter_name: null };
  });
};

// === API Calls ===
const fetchCurrentClients = async () => {
  try {
    const response = await axios.get(`/api/current-clients`);
    const queueData = response.data.data;

    counters.value = counterIds.map((id) => {
      const match = queueData.find((q) => q.service_counter_id === id);
      return match || { service_counter_id: id, queue_number: null };
    });

    counterIds.forEach((id) => {
      window.Echo.channel(`client.counter.${id}`).listen('.CounterEvent', (e: any) => {
        const updatedCounterId = e.service_counter_id;
        const updatedQueueNumber = e.queue_number;

        const index = counters.value.findIndex((counter) => counter.service_counter_id === updatedCounterId);
        if (index !== -1) {
          counters.value[index].queue_number = updatedQueueNumber;
        } else {
          counters.value.push({
            service_counter_id: updatedCounterId,
            queue_number: updatedQueueNumber,
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
    const { data } = await axios.get('/api/queue_list');
    const newList = data.data;
    queueList.value = newList;

    if (!isSupported.value || speaking) return;

    newList.forEach((item: any) => {
      const { queue_number, counter_name, called_at } = item;
      if (queue_number && called_at) {
        speakQueueCall(queue_number, counter_name, called_at);
      }
    });
  } catch (error) {
    console.error('Error fetching queue list:', error);
  }
};

const listen_transferring = () => {
  window.Echo.channel('admin.serving').listen('.ClientTransferred', (e: any) => {
    const { queue_number, old_counter_name, new_counter_name } = e;
    speakClientTransfer(queue_number, old_counter_name, new_counter_name);
  });
};

// === Lifecycle Hooks ===
let bgRotateInterval: ReturnType<typeof setInterval>;

onMounted(() => {
  loadVoices();
  if (window.speechSynthesis.onvoiceschanged !== undefined) {
    window.speechSynthesis.onvoiceschanged = loadVoices;
  }

  fetchCurrentClients();
  fetchQueueList();
  listen_transferring();

  window.Echo.channel('queue.list').listen('.QueueListUpdated', (e: any) => {
    const latestQueue = {
      queue_number: e.queue_number,
      counter_name: e.counter_name,
      called_at: e.called_at,
    };

    queueList.value = [latestQueue];

    if (!isSupported.value || speaking) return;

    speakQueueCall(e.queue_number, e.counter_name, e.called_at);
  });

  bgRotateInterval = rotateBackground();
});

onUnmounted(() => {
  clearInterval(bgRotateInterval);
});
</script>

<template>
  <div class="grid grid-cols-3 gap-6">
    <div
      v-for="counter in counters.slice(0, 3)"
      :key="counter.service_counter_id"
      class="mx-2 inline-block h-24 rounded-sm bg-blue-950 p-3 shadow-md"
    >
      <div class="text-center text-6xl font-semibold leading-none text-white">
        {{ counter.counter_name }}
      </div>
      <div v-if="counter.service_counter_id == 1">
        <span class="text-white">(LICENSED, PERMITTING, CERTIFICATION AND OTHER CONCERNS)</span>
      </div>
      <div class="mt-1">
        <div class="flex items-center justify-center">
          <div
            v-if="counter.queue_number != null"
            class="rounded-lg bg-white px-2 py-1 text-[190px] font-bold leading-none text-[#132b57]"
            :class="{ 'mt-6': counter.service_counter_id === 2 || counter.service_counter_id === 3 }"
          >
            {{ counter.queue_number }}
          </div>
          <div
            v-else
            class="rounded-lg border-4 border-[#0d4917] bg-white px-2 py-1 text-[190px] font-bold leading-none text-[#132b57]"
            :class="{ 'mt-6': counter.service_counter_id === 2 || counter.service_counter_id === 3 }"
          >
            ---
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
        :style="{ backgroundImage: `url(${currentBg})` }"
      ></div>

      <!-- Right Sidebar -->
      <div
        class="flex w-full flex-col gap-0 rounded px-6 py-5 text-center md:w-1/3"
        v-if="is_transferring"
      >
        <div class="mt-3 text-5xl font-semibold text-red-700">Queue Number</div>
        <div class="blink text-[300px] font-bold leading-none text-red-700">
          {{ transferInfo.queue_number }}
        </div>
     
        <div class="rounded-lg bg-slate-900 px-4 py-2 text-6xl text-white">
          Has been transferred from counter {{ transferInfo.old_counter }} to counter {{ transferInfo.counter_name }}
        </div>
        <div class="mt-4 text-2xl font-semibold text-orange-600 animate-pulse">
          Transferring... Please wait
        </div>
      </div>

      <div
        class="flex w-full flex-col gap-0 rounded px-6 py-5 text-center md:w-1/3"
        v-else
        v-for="queue in queueList"
        :key="queue.queue_number"
      >
        <div class="mt-3 text-5xl font-semibold text-[#132b57]">Ticket Number</div>
        <div class="blink text-[300px] font-bold leading-none text-red-700">
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

</style>
