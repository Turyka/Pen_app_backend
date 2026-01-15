FROM richarvey/nginx-php-fpm:3.1.6

# Install CA certificates + TikTok scraper deps
RUN apk update && apk add --no-cache \
    ca-certificates \
    && update-ca-certificates \
    && apk add --no-cache \
    python3 \
    py3-pip \
    nodejs \
    npm \
    && pip3 install --no-cache-dir \
    playwright \
    requests \
    && python3 -m playwright install --with-deps chromium \
    && apk add --no-cache wget gnupg

ENV SSL_CERT_FILE=/etc/ssl/certs/ca-certificates.crt
ENV PLAYWRIGHT_BROWSERS_PATH=/ms-playwright
ENV CHROME_BIN=/ms-playwright/chromium-*/chrome-linux/chrome

# Copy app + scraper
COPY . /var/www/html
WORKDIR /var/www/html

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
ENV COMPOSER_ALLOW_SUPERUSER 1

CMD ["/start.sh"]