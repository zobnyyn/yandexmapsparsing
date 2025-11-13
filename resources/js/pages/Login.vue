<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center px-4">
    <div class="max-w-md w-full">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Яндекс Карты</h1>
        <p class="text-gray-600 mt-2">Войдите, чтобы продолжить</p>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
        <form @submit.prevent="handleLogin" class="space-y-6">
          <div>
            <label class="block text-sm font-medium text-gray-900 mb-2">Логин</label>
            <input
              v-model="form.email"
              type="email"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent transition"
              placeholder="example@yandex.ru"
            >
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-900 mb-2">Пароль</label>
            <input
              v-model="form.password"
              type="password"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent transition"
              placeholder="Введите пароль"
            >
          </div>

          <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            {{ error }}
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="w-full bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-medium py-3 rounded-lg transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ loading ? 'Вход...' : 'Войти' }}
          </button>
        </form>

        <div class="mt-6 pt-6 border-t border-gray-200 text-center">
          <router-link
            to="/register"
            class="text-blue-600 hover:text-blue-700 text-sm font-medium"
          >
            Создать аккаунт
          </router-link>
        </div>
      </div>

      <div class="mt-6 text-center text-xs text-gray-500">
        <p>© 2025 Интеграция с Яндекс Картами</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api';

const router = useRouter();
const loading = ref(false);
const error = ref('');

const form = ref({
  email: '',
  password: ''
});

const handleLogin = async () => {
  loading.value = true;
  error.value = '';

  try {
    const { data } = await api.post('/login', form.value);
    localStorage.setItem('token', data.access_token);
    router.push('/dashboard');
  } catch (e) {
    error.value = e.response?.data?.message || 'Ошибка авторизации';
  } finally {
    loading.value = false;
  }
};
</script>

