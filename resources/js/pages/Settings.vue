<template>
  <div class="min-h-screen bg-gray-50">
    <nav class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex items-center space-x-3">
            <h1 class="text-xl font-semibold text-gray-900">Яндекс Карты - Настройки</h1>
          </div>
          <div class="flex items-center gap-4">
            <button
              @click="router.push('/dashboard')"
              class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition"
            >
              Главная
            </button>
            <button
              @click="handleLogout"
              class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition"
            >
              Выйти
            </button>
          </div>
        </div>
      </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Настройки интеграции с Яндекс Картами</h2>

        <form @submit.prevent="saveSetting" class="space-y-6">
          <div>
            <label class="block text-sm font-medium text-gray-900 mb-2">
              Ссылка на организацию в Яндекс Картах
            </label>
            <input
              v-model="yandexUrl"
              type="url"
              required
              placeholder="https://yandex.ru/maps/-/CLCHq4L0"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent transition"
            >
            <p class="mt-2 text-xs text-gray-500">Скопируйте короткую ссылку из Яндекс Карт (например: https://yandex.ru/maps/-/CLCHq4L0)</p>
          </div>

          <div class="flex flex-col sm:flex-row gap-3">
            <button
              type="submit"
              :disabled="loading"
              class="px-6 py-3 bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-medium rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ loading ? 'Сохранение...' : 'Сохранить настройки' }}
            </button>

            <button
              type="button"
              @click="router.push('/dashboard')"
              class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-900 font-medium rounded-lg transition"
            >
              Отмена
            </button>
          </div>

          <div v-if="message" class="mt-4 p-4 rounded-lg" :class="messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'">
            {{ message }}
          </div>
        </form>
      </div>

      <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-blue-900 mb-3">Как получить ссылку?</h3>
        <ol class="space-y-2 text-sm text-blue-800">
          <li class="flex items-start">
            <span class="font-bold mr-2">1.</span>
            <span>Откройте Яндекс Карты и найдите вашу организацию</span>
          </li>
          <li class="flex items-start">
            <span class="font-bold mr-2">2.</span>
            <span>Нажмите на кнопку "Поделиться" или скопируйте ссылку из адресной строки</span>
          </li>
          <li class="flex items-start">
            <span class="font-bold mr-2">3.</span>
            <span>Вставьте ссылку в поле выше и сохраните</span>
          </li>
        </ol>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api';

const router = useRouter();
const loading = ref(false);
const yandexUrl = ref('');
const message = ref('');
const messageType = ref('success');

const handleLogout = async () => {
  try {
    await api.post('/logout');
  } catch (e) {
  } finally {
    localStorage.removeItem('token');
    router.push('/login');
  }
};

const saveSetting = async () => {
  loading.value = true;
  message.value = '';

  try {
    await api.post('/yandex/setting', { yandex_url: yandexUrl.value });
    message.value = 'Настройки успешно сохранены';
    messageType.value = 'success';

    setTimeout(() => {
      router.push('/dashboard');
    }, 1500);
  } catch (e) {
    message.value = e.response?.data?.message || 'Ошибка сохранения';
    messageType.value = 'error';
  } finally {
    loading.value = false;
  }
};

const loadSetting = async () => {
  try {
    const { data: setting } = await api.get('/yandex/setting');
    if (setting) {
      yandexUrl.value = setting.yandex_url || '';
    }
  } catch (e) {
  }
};

onMounted(() => {
  loadSetting();
});
</script>

