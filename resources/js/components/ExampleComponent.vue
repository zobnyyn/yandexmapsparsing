<template>
  <div class="bg-gray-50 rounded-lg p-4 mt-4">
    <h3 class="text-lg font-semibold mb-2">Пример компонента</h3>
    <input
      v-model="message"
      type="text"
      placeholder="Введите текст..."
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
    >
    <p class="mt-2 text-gray-600">Вы ввели: {{ message }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const message = ref('');
</script>

