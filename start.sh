#!/bin/bash

echo "🚀 Запуск Laravel + Vue + Docker..."
echo ""

# Запуск контейнеров
echo "📦 Запускаю Docker контейнеры..."
docker-compose up -d

# Ожидание запуска MySQL
echo "⏳ Ожидаю запуск MySQL..."
sleep 5

# Установка зависимостей (только при первом запуске)
if [ ! -d "vendor" ]; then
    echo "📥 Устанавливаю PHP зависимости..."
    docker-compose exec php composer install
fi

# Генерация ключа приложения (только если не существует)
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Генерирую ключ приложения..."
    docker-compose exec php php artisan key:generate
fi

# Миграции
echo "🗄️  Запускаю миграции..."
docker-compose exec php php artisan migrate --force

# Права доступа
echo "🔒 Устанавливаю права доступа..."
docker-compose exec php chown -R www:www /var/www/storage /var/www/bootstrap/cache

echo ""
echo "✅ Готово!"
echo ""
echo "🌐 Приложение доступно по адресу:"
echo "   → http://localhost"
echo "   → http://localhost:8080"
echo ""
echo "🔥 Vite HMR:"
echo "   → http://localhost:5173"
echo ""
echo "💾 MySQL:"
echo "   → localhost:3308"
echo "   → Database: yandex"
echo "   → User: yandex"
echo "   → Password: secret"
echo ""
echo "📋 Полезные команды:"
echo "   docker-compose logs -f         - просмотр логов"
echo "   docker-compose down            - остановка контейнеров"
echo "   docker-compose exec php bash   - войти в PHP контейнер"
echo ""

