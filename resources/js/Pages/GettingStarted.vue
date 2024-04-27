<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import AppLayout from "@/Layouts/AppLayout.vue";

defineProps({appName: String, appVersion: String});

</script>

<template>
  <AppLayout>
    <div class="flex flex-col items-center justify-center p-8">
      <h1 class="text-4xl font-bold mb-4">Welcome to {{ appName }}</h1>
      <p class="text-lg text-gray-600 mb-6">Your installation journey begins here</p>
      <ApplicationLogo alt="Installation" class="mb-6 h-40"/>
      <RouterLink :to="{ name: 'install.server-requirements' }"
                  class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
        Get Started
      </RouterLink>
    </div>
  </AppLayout>
</template>
