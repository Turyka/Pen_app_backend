FROM richarvey/nginx-php-fpm:3.1.6

# Install CA certificates
RUN apk update && apk add ca-certificates && update-ca-certificates
ENV SSL_CERT_FILE=/etc/ssl/certs/ca-certificates.crt

# Install Chrome + Python for Facebook scraper
RUN apk add --no-cache \
    wget \
    gnupg \
    python3 \
    py3-pip \
    chromium \
    chromium-chromedriver \
    && pip3 install selenium

# Set Chrome options
ENV CHROME_BIN=/usr/bin/chromium-browser
ENV CHROME_DRIVER=/usr/bin/chromedriver

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install composer dependencies
ENV COMPOSER_ALLOW_SUPERUSER 1
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy the rest of the application
COPY . .

# Generate autoloader and run post-install scripts
RUN composer dump-autoload --optimize --no-dev \
    && composer run-script post-autoload-dump

# Image config
ENV SKIP_COMPOSER 1
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1

# Laravel config
ENV APP_ENV production
ENV APP_DEBUG false
ENV LOG_CHANNEL stderr

CMD ["/start.sh"]