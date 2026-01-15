FROM richarvey/nginx-php-fpm:3.1.6

# Install CA certificates
RUN apk update && apk add ca-certificates && update-ca-certificates
ENV SSL_CERT_FILE=/etc/ssl/certs/ca-certificates.crt

# Install Chrome + Python deps (Selenium + Playwright)
RUN apk add --no-cache \
    wget \
    gnupg \
    python3 \
    py3-pip \
    py3-setuptools \
    py3-wheel \
    gcc \
    g++ \
    python3-dev \
    musl-dev \
    chromium \
    chromium-chromedriver \
    && pip3 install --no-cache-dir --upgrade pip \
    && pip3 install --no-cache-dir selenium \
    && pip3 install --no-cache-dir playwright \
    && python3 -m playwright install chromium \
    && apk del gcc g++ python3-dev musl-dev

# Set Chrome options
ENV CHROME_BIN=/usr/bin/chromium-browser
ENV CHROME_DRIVER=/usr/bin/chromedriver
ENV PLAYWRIGHT_BROWSERS_PATH=0

COPY . .

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

# Allow composer to run as root
ENV COMPOSER_ALLOW_SUPERUSER 1

CMD ["/start.sh"]