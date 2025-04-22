<script lang="ts" setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import bg3 from '../../../images/bg3.png';
import bg4 from '../../../images/bg4.png';
import bg5 from '../../../images/bg5.png';

const images = [bg3, bg4, bg5]
const currentBg = ref(images[0])



interface CounterClient {
  counter: number;
  ticket_number: number;
}

const counters = ref<CounterClient[]>([]);

const fetchCurrentClients = async () => {
  try {
    const response = await axios.get('/api/current-clients');
    counters.value = response.data;
  } catch (error) {
    console.error('Error fetching counters:', error);
  }
};




onMounted(() => {
  let index = 0
  setInterval(() => {
    index = (index + 1) % images.length
    currentBg.value = images[index]
  }, 5000) // change every 4 seconds
})
</script>

<template>
  <div class="grid grid-cols-4 gap-6">
    <div class="inline-block rounded-sm bg-[#0d4917] p-3 shadow-md h-24">
      <div class="text-center text-6xl font-semibold leading-none text-white">COUNTER 1</div>
      <div class="mt-4">
        <div class="flex items-center justify-center">
          <div
            class="rounded-lg border-4 border-[#0d4917] bg-white px-2 py-1 text-[150px] font-bold leading-none text-[#132b57] mt-3">
            1108</div>
        </div>
      </div>
    </div>

    <div class="inline-block rounded-sm bg-[#0d4917] p-3 shadow-md h-24">
      <div class="text-center text-6xl font-semibold leading-none text-white">COUNTER 2</div>
      <div class="mt-4">
        <div class="flex items-center justify-center">
          <div
            class="rounded-lg border-4 border-[#0d4917] bg-white px-2 py-1 text-[150px] font-bold leading-none text-[#132b57] mt-3">
            1109</div>
        </div>
      </div>
    </div>

    <div class="inline-block rounded-sm bg-[#0d4917] p-3 shadow-md h-24">
      <div class="text-center text-6xl font-semibold leading-none text-white">COUNTER 3</div>
      <div class="mt-4">
        <div class="flex items-center justify-center">
          <div
            class="rounded-lg border-4 border-[#0d4917] bg-white px-2 py-1 text-[150px] font-bold leading-none text-[#132b57] mt-3">
            1110</div>
        </div>
      </div>
    </div>

    <div class="inline-block rounded-sm bg-[#0d4917] p-3 shadow-md h-24">
      <div class="text-center text-6xl font-semibold leading-none text-white">COUNTER 4</div>
      <div class="mt-4">
        <div class="flex items-center justify-center">
          <div
            class="rounded-lg border-4 border-[#0d4917] bg-white px-2 py-1 text-[150px] font-bold leading-none text-[#132b57] mt-3">
            1111</div>
        </div>
      </div>
    </div>

  </div>
  <div class="col-span-3 flex max-w-full  flex-col gap-4 mt-[200px]">
    <div class="flex flex-col md:flex-row gap-5 rounded-xl text-[#132b57] mt-3">
      <!-- Left Content -->
      <div
    class="flex-1 overflow-hidden rounded p-4 shadow w-full h-[500px] border text-center bg-cover bg-center text-white transition-all duration-1000 ease-in-out"
    :style="{ backgroundImage: `url(${currentBg})` }"
  >
    <!-- Optional overlay content -->
    <!-- <h1 class="text-4xl font-bold mt-40 bg-black bg-opacity-50 px-6 py-3 rounded inline-block"></h1> -->
  </div>



      <!-- Right Sidebar -->
      <div class="w-full md:w-1/3 px-6 py-5  rounded  flex flex-col text-center gap-0 ">
        <div class="text-5xl font-semibold  text-[#132b57] mt-3">Ticket Number</div>
        <div class="text-[200px] md:text-[250px] font-bold leading-none text-red-700">1108</div>
        <div class="text-6xl text-white bg-slate-900 py-2 px-4 rounded-lg">
          Please proceed to <span class="font-bold ">COUNTER 1</span>
        </div>
      </div>


    </div>
  </div>

</template>

<style scoped>
/* Header Styling */
.bg-gradient-to-r {
  background: linear-gradient(to right, #f59e0b, #d97706);
}

/* Hover animation on the counter boxes */
.transition-transform {
  transition: transform 0.3s ease-in-out;
}

.transform {
  transform: scale(1);
}

.hover\:scale-105:hover {
  transform: scale(1.05);
}

/* Animation for ticket count */
@keyframes fadeIn {
  0% {
    opacity: 0;
  }

  100% {
    opacity: 1;
  }
}

/* Counter box inner styling */
.counter-box {
  animation: fadeIn 0.5s ease-in-out;
}

/* Ticket Numbers and Count Styling */
.ticket-count {
  font-size: 1.125rem;
  font-weight: 600;
  color: #4b5563;
}
</style>
