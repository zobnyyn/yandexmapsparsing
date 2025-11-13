<template>
  <div class="min-h-screen bg-gray-50">
    <nav class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex items-center space-x-3">
            <h1 class="text-xl font-semibold text-gray-900">Яндекс Карты - Отзывы</h1>
          </div>
          <div class="flex items-center gap-4">
            <button
              @click="router.push('/settings')"
              class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition"
            >
              Настройки
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

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8 mb-6">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-2xl font-bold text-gray-900">Управление</h2>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
          <button
            type="button"
            @click="fetchReviews"
            :disabled="loading || !hasSettings"
            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ loading ? 'Загрузка...' : 'Обновить данные' }}
          </button>

          <button
            type="button"
            @click="router.push('/settings')"
            class="px-6 py-3 bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-medium rounded-lg transition"
          >
            Изменить настройки
          </button>
        </div>

        <div v-if="message" class="mt-4 p-4 rounded-lg" :class="messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'">
          {{ message }}
        </div>
      </div>

      <div v-if="data" class="space-y-6">
        <div v-if="data.company_name || data.company_photo" class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="relative h-48 bg-gradient-to-br from-gray-100 to-gray-200">
            <img
              v-if="data.company_photo"
              :src="data.company_photo"
              :alt="data.company_name"
              class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-6xl">
              🏢
            </div>
          </div>
          <div class="p-6 sm:p-8">
            <h2 class="text-3xl font-bold text-gray-900 mb-2">{{ data.company_name || 'Неизвестная компания' }}</h2>
            <div class="flex items-center gap-6 text-sm text-gray-500">
              <div class="flex items-center gap-2">
                <span class="text-yellow-500 text-xl">⭐</span>
                <span class="font-semibold text-gray-900">{{ data.rating.toFixed(1) }}</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-blue-500 text-xl">💬</span>
                <span>{{ data.review_count }} {{ pluralizeReviews(data.review_count) }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
          <h2 class="text-2xl font-bold text-gray-900 mb-6">Статистика</h2>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl p-6 border border-yellow-200">
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-sm font-medium text-yellow-900 mb-1">Рейтинг</div>
                  <div class="text-4xl font-bold text-yellow-600">{{ data.rating.toFixed(1) }}</div>
                </div>
                <div class="text-5xl">⭐</div>
              </div>
            </div>

            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200">
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-sm font-medium text-blue-900 mb-1">Всего отзывов</div>
                  <div class="text-4xl font-bold text-blue-600">{{ data.review_count }}</div>
                </div>
                <div class="text-5xl">💬</div>
              </div>
            </div>
          </div>

          <div v-if="data.fetched_at" class="mt-6 pt-6 border-t border-gray-200">
            <div class="flex items-center text-sm text-gray-500">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              Последнее обновление: {{ formatDate(data.fetched_at) }}
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
          <h2 class="text-2xl font-bold text-gray-900 mb-6">
            Отзывы <span class="text-gray-500 text-lg">({{ data.reviews?.length || 0 }})</span>
          </h2>

          <div v-if="data.reviews?.length" class="space-y-6">
            <div
              v-for="(review, index) in data.reviews"
              :key="index"
              class="border border-gray-200 rounded-xl p-6 hover:shadow-md transition-shadow"
            >
              <div class="flex justify-between items-start mb-3">
                <div>
                  <div class="font-semibold text-gray-900">{{ review.author }}</div>
                  <div class="text-sm text-gray-500 mt-1">{{ formatDate(review.date) }}</div>
                </div>
                <div class="flex items-center gap-0.5">
                  <span v-for="star in 5" :key="star" class="text-xl">
                    {{ star <= review.rating ? '⭐' : '☆' }}
                  </span>
                </div>
              </div>

              <p class="text-gray-700 leading-relaxed">{{ review.text }}</p>
            </div>
          </div>

          <div v-else class="text-center py-12">
            <div class="text-6xl mb-4">📝</div>
            <p class="text-gray-500 text-lg">Отзывы не найдены</p>
          </div>
        </div>
      </div>

      <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center">
        <div class="text-6xl mb-4">🗺️</div>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">Добро пожаловать!</h3>
        <p class="text-gray-500 max-w-md mx-auto mb-6">
          Настройте интеграцию с Яндекс Картами и нажмите "Обновить данные", чтобы увидеть статистику и отзывы
        </p>
        <button
          @click="router.push('/settings')"
          class="px-6 py-3 bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-medium rounded-lg transition"
        >
          Перейти к настройкам
        </button>
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
const hasSettings = ref(false);
const data = ref(null);
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

const fetchReviews = async () => {
  loading.value = true;
  message.value = '';

  try {
    const { data: responseData } = await api.post('/yandex/fetch');
    data.value = responseData;
    message.value = 'Данные успешно обновлены';
    messageType.value = 'success';
  } catch (e) {
    const errorMsg = e.response?.data?.error || e.response?.data?.message || 'Ошибка загрузки данных';
    message.value = errorMsg;
    messageType.value = 'error';
    console.error('Fetch error:', e.response?.data);
  } finally {
    loading.value = false;
  }
};

const loadSetting = async () => {
  try {
    const { data: setting } = await api.get('/yandex/setting');
    if (setting && setting.yandex_url) {
      hasSettings.value = true;
    }
  } catch (e) {
    hasSettings.value = false;
  }
};

const loadCachedData = async () => {
  try {
    const { data: cachedData } = await api.get('/yandex/cached');
    if (cachedData) {
      data.value = cachedData;
    }
  } catch (e) {
  }
};

const pluralizeReviews = (count) => {
  const cases = [2, 0, 1, 1, 1, 2];
  const titles = ['отзыв', 'отзыва', 'отзывов'];
  return titles[(count % 100 > 4 && count % 100 < 20) ? 2 : cases[Math.min(count % 10, 5)]];
};

const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleString('ru-RU', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

onMounted(() => {
  loadSetting();
  loadCachedData();
});
</script>

