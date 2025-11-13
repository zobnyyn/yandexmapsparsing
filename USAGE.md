# Яндекс Карты - Интеграция

Система для парсинга отзывов и рейтинга с Яндекс Карт.

## Быстрый старт

### 1. Запуск через Docker

```bash
docker-compose up -d
```

### 2. Создание пользователя

```bash
docker-compose exec php php artisan user:create test@test.com 123456
```

### 3. Открыть приложение

Перейдите на http://localhost

## Использование

1. **Войдите** с созданными учетными данными
2. **Добавьте ссылку** на организацию из Яндекс Карт
   - Формат: `https://yandex.ru/maps/org/название/1234567890`
   - Или с параметром: `?oid=1234567890`
3. **Нажмите "Обновить данные"** для загрузки отзывов

## Примеры ссылок

```
https://yandex.ru/maps/org/restoran_pushkin/1234567890
https://yandex.ru/maps/?oid=1234567890
https://yandex.ru/maps/2/moscow/house/tverskoy_bulvar_26/Z04YcARlTEEGQFtvfXt5dX1nbA==/?ll=37.604494%2C55.762916&oid=1234567890&z=17
```

## Функционал

✅ Авторизация (login/register)  
✅ Парсинг рейтинга компании  
✅ Парсинг количества отзывов  
✅ Вывод последних 10 отзывов  
✅ Кеширование данных  
✅ Дизайн в стиле Яндекса  

## Технологии

- **Backend:** Laravel 12, PHP 8.3
- **Frontend:** Vue 3, Vite, Tailwind CSS
- **Database:** MySQL 8.0
- **Deploy:** Docker Compose

## Архитектура

```
app/
├── Services/
│   └── YandexMapsService.php    # Бизнес-логика парсинга
├── Http/Controllers/
│   ├── AuthController.php       # Авторизация
│   └── YandexController.php     # API для Яндекс Карт
└── Models/
    ├── User.php
    └── YandexSetting.php

resources/js/
├── pages/
│   ├── Login.vue                # Страница входа
│   ├── Register.vue             # Страница регистрации
│   └── Dashboard.vue            # Главная страница
├── api.js                       # Axios конфигурация
└── router.js                    # Vue Router
```

## API Endpoints

```
POST   /api/login              - Вход
POST   /api/register           - Регистрация
POST   /api/logout             - Выход
GET    /api/me                 - Текущий пользователь

POST   /api/yandex/setting     - Сохранить URL
GET    /api/yandex/setting     - Получить настройки
POST   /api/yandex/fetch       - Загрузить данные с Яндекс
GET    /api/yandex/cached      - Получить кешированные данные
```

## Команды

```bash
# Создать пользователя
docker-compose exec php php artisan user:create email@test.com password123

# Очистить кеш
docker-compose exec php php artisan cache:clear

# Просмотр логов
docker-compose logs -f

# Перезапуск
docker-compose restart

# Остановка
docker-compose down
```

## Порты

- **80, 8080** - Nginx (приложение)
- **5173** - Vite (HMR)
- **3308** - MySQL

## Примечания

- Демо-данные используются, если парсинг не удался
- Все данные кешируются в БД
- Логи доступны через `docker-compose logs`

