<script setup>

import {ref} from 'vue'
import AppLayout from "@/Layouts/AppLayout.vue";

defineProps({
  output: String
})
const installationSteps = ref([
  {message: 'Configuring database...', done: false},
  {message: 'Applying migrations...', done: false},
  {message: 'Seeding database...', done: false},
  // Add more steps as necessary
])

const installationComplete = ref(false)

// Example function to simulate installation steps progress
setTimeout(() => {
  installationSteps.value.forEach((step, index) => {
    setTimeout(() => {
      step.done = true
      if (index === installationSteps.value.length - 1) {
        installationComplete.value = true
      }
    }, (index + 1) * 1000) // Each step is marked as done one second apart
  })
}, 1000)

function redirectToDashboard() {
  router.visit(route('welcome'))
}

</script>

<template>
  <AppLayout>
    <h2 class="text-2xl font-bold text-center">Installation Progress</h2>
    <div>
      <h1>Artisan Command Output</h1>
      <pre>{{ output }}</pre>
    </div>
    <div v-if="!installationComplete">
      <p v-for="step in installationSteps" :key="step.message" class="flex items-center justify-between">
        {{ step.message }}
        <span :class="{'text-green-500': step.done, 'text-gray-400': !step.done}">
                        <svg v-if="step.done" class="h-6 w-6" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                          <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <svg v-else class="h-6 w-6 animate-spin" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                          <path
                              d="M12 3v3m0 12v3m9-9h-3M6 12H3m2.293-7.293l-1.414 1.414M17.707 6.293l1.414 1.414M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                              stroke-linecap="round"
                              stroke-linejoin="round"/>
                        </svg>
                      </span>
      </p>
    </div>

    <div v-if="installationComplete" class="text-center">
      <p class="text-green-500">Installation Completed Successfully!</p>
      <button class="mt-4 bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded"
              @click="redirectToDashboard">
        Go to Dashboard
      </button>
    </div>
  </AppLayout>
</template>