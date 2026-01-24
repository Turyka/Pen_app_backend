FROM richarvey/nginx-php-fpm:3.1.6

# ------------------------
# Install Python 3.11 explicitly
# ------------------------
RUN apk update && apk add --no-cache \
    python3=3.11.* \
    py3-pip \
    py3-setuptools \
    py3-wheel \
    ca-certificates \
    wget \
    gnupg \
    chromium \
    chromium-chromedriver \
    nss \
    freetype \
    harfbuzz \
    ttf-freefont \
    libstdc++ \
    bash

RUN python3 --version
RUN pip3 install --upgrade pip

# ------------------------
# Install Python libs
# ------------------------
RUN pip3 install --no-cache-dir selenium playwright==1.40.0

# ------------------------
# Install Playwright browser
# ------------------------
RUN playwright install chromium --with-deps

ENV CHROME_BIN=/usr/bin/chromium-browser
ENV CHROME_DRIVER=/usr/bin/chromedriver

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
