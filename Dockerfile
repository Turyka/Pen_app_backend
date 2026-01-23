FROM mcr.microsoft.com/playwright:v1.58.0-noble

# Install PHP + nginx (Debian/Ubuntu)
RUN apt-get update && apt-get install -y \
    nginx \
    php8.2 \
    php8.2-fpm \
    php8.2-cli \
    php8.2-mysql \
    php8.2-xml \
    php8.2-mbstring \
    php8.2-curl \
    php8.2-zip \
    supervisor \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Install Python deps (Playwright already present)
RUN pip3 install --no-cache-dir selenium

# App
WORKDIR /var/www/html
COPY . .

# Nginx config (YOU must provide this)
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Permissions
RUN chown -R www-data:www-data /var/www/html

# Render port
EXPOSE 10000
ENV PORT=10000

# Laravel env
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr
ENV COMPOSER_ALLOW_SUPERUSER=1

# Start everything
CMD ["supervisord", "-n"]
