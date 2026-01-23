FROM php:8.2-fpm-bookworm

# Install system deps
RUN apt-get update && apt-get install -y \
    nginx \
    ca-certificates \
    python3 \
    python3-pip \
    chromium \
    chromium-driver \
    fonts-liberation \
    libnss3 \
    libatk-bridge2.0-0 \
    libgtk-3-0 \
    libxss1 \
    libasound2 \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Python deps
RUN pip3 install --no-cache-dir selenium playwright

# Install Playwright browser
RUN playwright install chromium

# Nginx config
COPY docker/nginx.conf /etc/nginx/nginx.conf

# App
WORKDIR /var/www/html
COPY . .

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 10000

CMD ["supervisord", "-n"]
