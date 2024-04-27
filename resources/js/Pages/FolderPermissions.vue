<script setup>
import {onMounted, ref} from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import AppLayout from "@/Layouts/AppLayout.vue";

const permissions = ref([]);
const errors = ref({});
const router = useRouter();

const fetchPermissions = async () => {
  try {
    const response = await axios.get('/api/install/folder-permissions');
    permissions.value = response.data.data.permissions;
  } catch (error) {
    console.error('Failed to fetch permissions:', error);
    errors.value = { fetchError: 'Failed to load permissions. Please try again later.' };
  }
};

// Fetch permissions when the component is mounted
onMounted(fetchPermissions);

const nextStep = () => {
  router.push({name: 'install.environment-variables'});
}
</script>



<template>
  <AppLayout>
    <h2 class="text-2xl font-bold text-center">Folder Permissions</h2>
    <div class="w-96 text-center m-auto my-4">
      <div v-if="Object.keys(errors).length > 0" class="text-red-500">
        {{ errors.fetchError }}
      </div>
      <div v-else-if="permissions.length === 0" class="text-gray-600">
        Loading permissions...
      </div>
      <div v-else>
        <div v-for="permission in permissions" :key="permission.folder"
             class="flex items-center p-1 bg-gray-50 rounded-md">
          <span :class="permission.isWritable ? 'bg-green-200' : 'bg-red-200'"
                class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full">
            <svg v-if="permission.isWritable" class="w-6 h-6 text-green-600" fill="none"
                 stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path d="M4.5 12.75l6 6 9-13.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <svg v-else class="w-6 h-6 text-red-600" fill="none" stroke="currentColor"
                 stroke-width="1.5" viewBox="0 0 24 24">
              <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>
          <span class="ml-3 text-gray-700">
              {{ permission.folder }} ({{ permission.permission }})
          </span>
        </div>

        <PrimaryButton class="w-full mt-4" @click="nextStep">
          Next Step
        </PrimaryButton>
      </div>
    </div>
  </AppLayout>
</template>
