<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import ApplicationLogo from "@/Components/ApplicationLogo.vue";

const router = useRouter();
const currentStep = ref(1);

const steps = [
  {label: 'Requirements', name: 'install.server-requirements'},
  {label: 'Permissions', name: 'install.folder-permissions'},
  {label: 'Environment', name: 'install.environment-variables'},
  {label: 'License', name: 'install.envato-license'},
];

const currentComponent = computed(() => router.currentRoute.value.name);
console.log(currentComponent)
</script>

<template>
  <div class="min-h-screen bg-gray-100 flex flex-col items-center justify-center">
    <ApplicationLogo class="h-16 mx-auto mb-6" v-if="currentComponent !== 'install.home'"/>

    <!-- Sidebar for steps -->
    <div class="max-w-4xl flex justify-between mb-4 gap-6" v-if="!['install.home', 'install.installation-progress'].includes(currentComponent)">
      <div v-for="(step, index) in steps" :key="index">
        <RouterLink :to="{ name: step.name }" class="flex items-center px-4 py-2 rounded-md shadow" :class="[currentComponent===step.name?'bg-blue-500':' hover:bg-blue-100']">
          <span class="inline-block w-8 h-8 text-center leading-8 rounded-full mr-2 bg-blue-200">
            <i v-if="index < currentStep - 1" class="fas fa-check"></i>
            <template v-else>{{ index + 1 }}</template>
          </span>
          <span>{{ step.label }}</span>
        </RouterLink>
      </div>
    </div>
    <div class="bg-white shadow rounded p-8 mb-8 w-full max-w-4xl min-h-96 overflow-y-auto" style="height: 44rem">
      <slot/>
    </div>
  </div>
</template>
