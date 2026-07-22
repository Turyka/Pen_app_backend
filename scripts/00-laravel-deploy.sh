#!/usr/bin/env bash

echo "Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "Caching config and routes..."
php artisan config:cache
php artisan route:cache

echo "Generating app key if not set..."
php artisan key:generate --force || true

echo "Listing routes (for debugging)..."
php artisan route:list

echo "Running migrations..."
php artisan migrate --force || echo "Migration failed - will retry on next start"
