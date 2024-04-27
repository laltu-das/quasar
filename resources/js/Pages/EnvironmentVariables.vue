<script setup>
import {onMounted, ref} from 'vue';
import {useRouter} from 'vue-router';
import {useEnvironmentStore} from '@/Stores/useEnvironmentStore';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import axios from "axios";

const envVariables = ref('');
const router = useRouter();
const environmentStore = useEnvironmentStore();

const fetchEnvironmentVariables = async () => {
  try {
    const response = await axios.get('/api/install/environment-variables');
    envVariables.value = response.data.data;
  } catch (error) {
    console.error("Failed to fetch server requirements:", error);
  }
};

onMounted(fetchEnvironmentVariables);

const submit = () => {
  environmentStore.submitEnvironmentVariables(envVariables.value);
  envVariables.value = '';
  router.push({name: 'install.envato-license'});
}

</script>

<template>
  <AppLayout>
    <h2 class="text-3xl font-semibold mb-6">Environment Variables</h2>
    <div class="w-full">
      <form @submit.prevent="submit">
        <div class="grid grid-cols-1 gap-6">
        <textarea v-model="envVariables" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Laravel"
                  rows="20"/>
        </div>
        <PrimaryButton class="mt-4 w-full">
          Next Step
        </PrimaryButton>
      </form>
    </div>
  </AppLayout>
</template>
