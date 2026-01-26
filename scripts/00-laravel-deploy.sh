#!/usr/bin/env bash
echo "Running composer"
composer install --no-dev --working-dir=/var/www/html

echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

php artisan route:clear
php artisan cache:clear
php artisan config:clear

echo "Running migrations..."
php artisan migrate --force
