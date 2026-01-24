FROM richarvey/nginx-php-fpm:3.1.6

# ------------------------
# System dependencies
# ------------------------
RUN apk update && apk add --no-cache \
    ca-certificates \
    wget \
    gnupg \
    python3 \
    py3-pip \
    chromium \
    chromium-chromedriver \
    nss \
    freetype \
    harfbuzz \
    ttf-freefont \
    libstdc++ \
    bash

ENV SSL_CERT_FILE=/etc/ssl/certs/ca-certificates.crt

# ------------------------
# Python dependencies
# ------------------------
RUN pip3 install --no-cache-dir \
    selenium \
    playwright

# ------------------------
# Install Playwright browsers
# IMPORTANT: must run AFTER playwright install
# ------------------------
RUN playwright install chromium --with-deps

# ------------------------
# Chrome paths (for Selenium)
# ------------------------
ENV CHROME_BIN=/usr/bin/chromium-browser
ENV CHROME_DRIVER=/usr/bin/chromedriver

# ------------------------
# Laravel app
# ------------------------
COPY . .

ENV SKIP_COMPOSER 1
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr

ENV COMPOSER_ALLOW_SUPERUSER=1

CMD ["/start.sh"]
