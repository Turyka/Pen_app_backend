FROM richarvey/nginx-php-fpm:3.1.6-debian

# ------------------------
# System dependencies
# ------------------------
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    python3-setuptools \
    python3-wheel \
    wget \
    gnupg \
    chromium \
    chromium-driver \
    ca-certificates \
    fonts-liberation \
    libnss3 \
    libatk-bridge2.0-0 \
    libgtk-3-0 \
    libxss1 \
    libasound2 \
    libgbm1 \
    libdrm2 \
    libxshmfence1 \
    libu2f-udev \
    xdg-utils \
    --no-install-recommends \
 && rm -rf /var/lib/apt/lists/*

# ------------------------
# Python libraries
# ------------------------
RUN pip3 install --upgrade pip \
 && pip3 install selenium playwright

# ------------------------
# Install Playwright browser
# ------------------------
RUN playwright install chromium

# ------------------------
# Env for Selenium
# ------------------------
ENV CHROME_BIN=/usr/bin/chromium
ENV CHROME_DRIVER=/usr/bin/chromedriver

# ------------------------
# Laravel app
# ------------------------
COPY . .

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
