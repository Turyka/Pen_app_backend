FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    ca-certificates \
    wget \
    gnupg \
    python3 \
    py3-pip \
    chromium \
    chromium-chromedriver \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    nginx \
    supervisor \
    && pip3 install selenium

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV CHROME_BIN=/usr/bin/chromium-browser
ENV CHROME_DRIVER=/usr/bin/chromedriver
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# Copy composer files first for caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application files
COPY . .
RUN composer dump-autoload --optimize --no-dev

# Nginx config
RUN rm -f /etc/nginx/http.d/default.conf
COPY conf/nginx/nginx-site.conf /etc/nginx/http.d/default.conf

# Configure PHP-FPM to use Unix socket
RUN printf '[www]\nuser = nginx\ngroup = nginx\nlisten = /var/run/php-fpm.sock\nlisten.owner = nginx\nlisten.group = nginx\nlisten.mode = 0660\npm = dynamic\npm.max_children = 5\npm.start_servers = 2\npm.min_spare_servers = 1\npm.max_spare_servers = 3\n' > /usr/local/etc/php-fpm.d/www.conf

# Supervisor config
RUN printf '[program:php-fpm]\ncommand=php-fpm\nautostart=true\nautorestart=true\nstderr_logfile=/dev/stderr\nstderr_logfile_maxbytes=0\nstdout_logfile=/dev/stdout\nstdout_logfile_maxbytes=0\n' > /etc/supervisor.d/php-fpm.conf

RUN printf '[program:nginx]\ncommand=nginx -g "daemon off;"\nautostart=true\nautorestart=true\nstderr_logfile=/dev/stderr\nstderr_logfile_maxbytes=0\nstdout_logfile=/dev/stdout\nstdout_logfile_maxbytes=0\n' > /etc/supervisor.d/nginx.conf

# Set permissions
RUN chown -R nginx:nginx /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]