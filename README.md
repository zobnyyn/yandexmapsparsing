<img width="2560" height="1440" alt="Снимок экрана от 2025-11-13 03-00-17" src="https://github.com/user-attachments/assets/bb7aff59-4b39-46da-a2d8-ab4f02ac4e52" />
<img width="2560" height="1440" alt="Снимок экрана от 2025-11-13 03-00-30" src="https://github.com/user-attachments/assets/59b5235e-a434-4f8e-9229-c7eb6fddc154" />
<img width="2560" height="1440" alt="Снимок экрана от 2025-11-13 03-00-41" src="https://github.com/user-attachments/assets/076ba17f-4884-4e93-a66d-87e1904b432f" />
<img width="2560" height="1440" alt="Снимок экрана от 2025-11-13 03-00-44" src="https://github.com/user-attachments/assets/05875d9e-e612-4f5b-b05c-76a369542250" />

# Интеграция с Яндекс Картами

Веб-приложение для получения и отображения отзывов с Яндекс Карт.

## Требования

- PHP 8.2+
- Composer
- Node.js 18+ и npm
- SQLite (или другая БД)

## Быстрый старт

### 1. Установка зависимостей

```bash
composer install
npm install --legacy-peer-deps
```

### 2. Настройка окружения

```bash
cp .env.example .env
php artisan key:generate
```

### 3. База данных

```bash
touch database/database.sqlite
php artisan migrate
```

### 4. Запуск приложения

**Вариант А: Автоматический запуск (рекомендуется)**

```bash
composer dev
```

Эта команда запустит все необходимые сервисы одновременно:
- Laravel сервер (http://localhost:8000)
- Vite dev server для фронтенда
- Queue worker для фоновых задач
- Logs viewer

**Вариант Б: Ручной запуск**

Откройте 3 терминала и запустите команды:

```bash
# Терминал 1 - Laravel сервер
php artisan serve

# Терминал 2 - Vite (фронтенд)
npm run dev

# Терминал 3 - Queue worker
php artisan queue:work
```

### 5. Первый запуск

1. Откройте браузер: http://localhost:8000
2. Зарегистрируйтесь или войдите (login/password)
3. Перейдите в раздел "Настройки"
4. Вставьте ссылку на организацию с Яндекс Карт
5. Вернитесь на главную и нажмите "Обновить данные"

## Структура проекта

```
app/
├── Http/Controllers/     # Контроллеры
├── Services/            # Бизнес-логика
└── Models/              # Модели данных

resources/js/
├── components/          # Vue компоненты
├── pages/              # Страницы приложения
└── router/             # Роутинг

routes/
└── api.php             # API маршруты
```

## Возможности

- ✅ Авторизация (login/password)
- ✅ Настройка интеграции с Яндекс Картами
- ✅ Парсинг отзывов с карточки организации
- ✅ Отображение рейтинга и количества отзывов
- ✅ Карточка компании с фото и названием
- ✅ Список последних 10 отзывов
- ✅ Адаптивный дизайн

## Production сборка

```bash
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Настройте веб-сервер (Nginx/Apache) для работы с `public` директорией.

## Проблемы при установке

**Ошибка npm install**
```bash
npm install --legacy-peer-deps
```

**Ошибка SQLite**
```bash
touch database/database.sqlite
chmod 664 database/database.sqlite
```

**Ошибка прав на storage**
```bash
chmod -R 775 storage bootstrap/cache
```

## Стэк

- **Backend**: Laravel 12, Sanctum
- **Frontend**: Vue 3, Vue Router, Tailwind CSS
- **Парсинг**: Puppeteer (через Node.js)
- **БД**: SQLite (можно заменить на MySQL/PostgreSQL)


