<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import AppLayout from "@/Layouts/AppLayout.vue";

const router = useRouter();
const requirements = ref({});


const fetchRequirements = async () => {
  try {
    const response = await axios.get('/api/install/server-requirements');
    requirements.value = response.data.data;
  } catch (error) {
    console.error("Failed to fetch server requirements:", error);
  }
};

onMounted(fetchRequirements);

const checkAgain = () => {
  fetchRequirements();
};

const nextStep = () => {
  router.push({ name: 'install.folder-permissions' })
}
</script>


<template>
  <AppLayout>
    <h2 class="text-2xl font-bold text-center">Server Requirements Check</h2>
    <p class="text-lg text-gray-600 text-center my-4">
      Ensuring your server meets all the necessary requirements for
      Laravel.
    </p>
    <div class="w-96 text-center m-auto mt-4">

      <div v-for="(status, requirement) in requirements" :key="requirement"
           class="flex items-center p-1 bg-gray-50 rounded-md">
          <span :class="status ? 'bg-green-200' : 'bg-red-200'"
                class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full">
            <svg v-if="status" class="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                 stroke-width="1.5"
                 viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path d="M4.5 12.75l6 6 9-13.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <svg v-else class="w-6 h-6 text-red-600" fill="none" stroke="currentColor"
                 stroke-width="1.5"
                 viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>
        <span class="ml-3 text-gray-700">{{ requirement }}</span>
      </div>


      <div class="text-center mt-4 flex justify-between">
        <PrimaryButton @click="checkAgain">
          Check Again
        </PrimaryButton>
        <PrimaryButton @click="nextStep"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
          Next Step
        </PrimaryButton>
      </div>
    </div>
  </AppLayout>
</template>
