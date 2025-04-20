<script lang="ts" setup></script>

<template>
  <div class="grid grid-cols-4 gap-6">
    <!-- Counter Box 1 -->
    <div class="flex flex-col gap-4 rounded-lg border bg-gray-50 p-6 shadow-xl transition-transform transform hover:scale-105">
      <div class="bg-gradient-to-r from-orange-400 to-orange-600 p-3 rounded-lg shadow-md">
        <div class="text-center text-2xl font-semibold text-white">Counter 1</div>
      </div>
      <div class="mt-4 flex flex-col gap-2">
        <div v-for="n in 4" :key="n" class="flex flex-col items-center justify-center">
          <div class="rounded-lg border-4 border-orange-500 px-10 py-6 text-center text-5xl font-bold text-orange-500 mb-2">
            A001-{{ n }}
          </div>
        </div>
      </div>
    </div>

    <!-- Counter Box 2 -->
    <div class="flex flex-col gap-4 rounded-lg border bg-gray-50 p-6 shadow-xl transition-transform transform hover:scale-105">
      <div class="bg-gradient-to-r from-blue-400 to-blue-600 p-3 rounded-lg shadow-md">
        <div class="text-center text-2xl font-semibold text-white">Counter 2</div>
      </div>
      <div class="mt-4 flex flex-col gap-2">
        <div v-for="n in 4" :key="n" class="flex flex-col items-center justify-center">
          <div class="rounded-lg border-4 border-blue-500 px-10 py-6 text-center text-5xl font-bold text-blue-500 mb-2">
            A002-{{ n }}
          </div>
        </div>
      </div>
    </div>

    <!-- Counter Box 3 -->
    <div class="flex flex-col gap-4 rounded-lg border bg-gray-50 p-6 shadow-xl transition-transform transform hover:scale-105">
      <div class="bg-gradient-to-r from-green-400 to-green-600 p-3 rounded-lg shadow-md">
        <div class="text-center text-2xl font-semibold text-white">Counter 3</div>
      </div>
      <div class="mt-4 flex flex-col gap-2">
        <div v-for="n in 4" :key="n" class="flex flex-col items-center justify-center">
          <div class="rounded-lg border-4 border-green-500 px-10 py-6 text-center text-5xl font-bold text-green-500 mb-2">
            A003-{{ n }}
          </div>
        </div>
      </div>
    </div>

    <!-- Counter Box 4 -->
    <div class="flex flex-col gap-4 rounded-lg border bg-gray-50 p-6 shadow-xl transition-transform transform hover:scale-105">
      <div class="bg-gradient-to-r from-purple-400 to-purple-600 p-3 rounded-lg shadow-md">
        <div class="text-center text-2xl font-semibold text-white">Counter 4</div>
      </div>
      <div class="mt-4 flex flex-col gap-2">
        <div v-for="n in 4" :key="n" class="flex flex-col items-center justify-center">
          <div class="rounded-lg border-4 border-purple-500 px-10 py-6 text-center text-5xl font-bold text-purple-500 mb-2">
            A004-{{ n }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Header Styling */
.bg-gradient-to-r {
  background: linear-gradient(to right, #F59E0B, #D97706);
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
  color: #4B5563;
}
</style>
