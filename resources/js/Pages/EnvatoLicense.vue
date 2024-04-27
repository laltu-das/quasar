<script setup>
import { ref } from 'vue';
import axios from 'axios';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import AppLayout from "@/Layouts/AppLayout.vue";

const envatoItemId = ref('');
const licenseKey = ref('');
const errors = ref({});
const processing = ref(false);
const artisanResponse = ref("");

const verifyLicense = async () => {
  processing.value = true;
  errors.value = {};  // Clear previous errors before new submission

  try {
    const response = await axios.post(`https://support.scriptspheres.com/api/license-verify`, {
      envatoItemId: envatoItemId.value,
      licenseKey: licenseKey.value,
    });

    // Check if there's a token and run the Artisan command
    if (response.data && response.data.token) {
      await downloadFile(response.data.token);
    } else {
      console.error('No token provided for running the Artisan command');
      artisanResponse.value = 'Failed to obtain token for installation.';
    }
  } catch (error) {
    if (error.response && error.response.data.errors && error.response.status === 422) {
      errors.value = error.response.data.errors;  // Capturing Laravel validation errors
    } else {
      // General error handling
      console.error('Unexpected error:', error);
      errors.value = {general: ['An unexpected error occurred.']};
    }
  } finally {
    processing.value = false;
  }
}

const downloadFile = async (token) => {
  processing.value = true;
  try {
    const response = await axios.post(`/api/install/download-project`, {
      envatoItemId: envatoItemId.value,
      token: token,
    });
    artisanResponse.value = response.data;
  } catch (error) {
    console.error("Failed to execute Artisan command:", error);
    artisanResponse.value = "Error: " + error.message;
  } finally {
    processing.value = false;
  }
};

</script>


<template>
  <AppLayout>
    <h2 class="text-2xl font-semibold text-center">Envato License Verification</h2>
    <form @submit.prevent="verifyLicense">
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700" for="envatoItemId">Envato Item ID</label>
        <input id="envatoItemId" v-model="envatoItemId"
               class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm" type="text">
        <div v-if="errors.envatoItemId">{{ errors.envatoItemId }}</div>
      </div>
      <div class="">
        <label class="block text-sm font-medium text-gray-700" for="licenseKey">Purchase Code</label>
        <input id="licenseKey" v-model="licenseKey"
               class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm" type="text">
        <div v-if="errors.licenseKey">{{ errors.licenseKey }}</div>
      </div>
      <PrimaryButton :disabled="processing" class="mt-4 w-full" type="submit">
        Verify License
      </PrimaryButton>
    </form>
    <p v-if="artisanResponse" class="mt-4 text-green-600">{{ artisanResponse }}</p>

  </AppLayout>
</template>
