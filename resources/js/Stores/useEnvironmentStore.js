// src/Stores/useEnvironmentStore.js
import { defineStore } from 'pinia';

export const useEnvironmentStore = defineStore('environment', {
    state: () => ({
        envVariables: ''
    }),
    actions: {
        submitEnvironmentVariables(newEnvVariables) {
            // Simulate processing data
            console.log('Processing environment variables:', newEnvVariables);

            // Simulate successful operation
            this.envVariables = newEnvVariables;
            this.resetEnvironmentVariables();
        },
        resetEnvironmentVariables() {
            // Reset state
            this.envVariables = '';
        }
    }
});
