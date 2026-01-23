FROM richarvey/nginx-php-fpm:debian

# Install system deps
RUN apt-get update && apt-get install -y \
    ca-certificates \
    python3 \
    python3-pip \
    wget \
    gnupg \
    chromium \
    chromium-driver \
    fonts-liberation \
    libnss3 \
    libatk-bridge2.0-0 \
    libgtk-3-0 \
    libxss1 \
    libasound2 \
    && rm -rf /var/lib/apt/lists/*

# Python deps
RUN pip3 install --no-cache-dir selenium playwright

# Install Playwright browser
RUN playwright install chromium

# Env vars
ENV SSL_CERT_FILE=/etc/ssl/certs/ca-certificates.crt
ENV CHROME_BIN=/usr/bin/chromium
ENV CHROME_DRIVER=/usr/bin/chromedriver
ENV PLAYWRIGHT_BROWSERS_PATH=/root/.cache/ms-playwright

# Laravel / nginx config
ENV SKIP_COMPOSER=1
ENV WEBROOT=/var/www/html/public
ENV PHP_ERRORS_STDERR=1
ENV RUN_SCRIPTS=1
ENV REAL_IP_HEADER=1
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY . .

CMD ["/start.sh"]
