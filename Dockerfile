FROM richarvey/nginx-php-fpm:3.1.6

# Certificates
RUN apk update && apk add ca-certificates && update-ca-certificates
ENV SSL_CERT_FILE=/etc/ssl/certs/ca-certificates.crt

# System dependencies
RUN apk add --no-cache \
    python3 \
    py3-pip \
    chromium \
    nodejs \
    git \
    nss \
    freetype \
    harfbuzz \
    ttf-freefont

# Install Playwright Python (OFFICIAL WAY)
RUN pip install playwright==1.57.0

# Install browsers (Playwright-managed)
RUN playwright install chromium

ENV PLAYWRIGHT_BROWSERS_PATH=/root/.cache/ms-playwright
ENV CHROME_BIN=/usr/bin/chromium-browser

COPY . .

# Laravel config
ENV SKIP_COMPOSER=1
ENV WEBROOT=/var/www/html/public
ENV PHP_ERRORS_STDERR=1
ENV RUN_SCRIPTS=1
ENV REAL_IP_HEADER=1
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr
ENV COMPOSER_ALLOW_SUPERUSER=1

CMD ["/start.sh"]
