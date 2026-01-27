#!/usr/bin/env bash

echo "Installing composer dependencies..."
composer install --no-dev --working-dir=/var/www/html

echo "Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "Caching config and routes..."
php artisan config:cache
php artisan route:cache

echo "Listing routes (for debugging)..."
php artisan route:list

echo "Running migrations..."
php artisan migrate --force
