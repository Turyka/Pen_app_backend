FROM richarvey/nginx-php-fpm:3.1.6

# Install system dependencies
RUN apk update && apk add --no-cache \
    ca-certificates \
    && update-ca-certificates

ENV SSL_CERT_FILE=/etc/ssl/certs/ca-certificates.crt

# Install Chrome + Python
RUN apk add --no-cache \
    wget \
    gnupg \
    python3 \
    py3-pip \
    py3-packaging \
    chromium \
    chromium-chromedriver \
    && pip3 install --no-cache-dir selenium==4.15.2

# Fix Chrome paths
ENV CHROME_BIN=/usr/bin/chromium-browser
ENV CHROME_DRIVER=/usr/bin/chromedriver

# Copy everything
COPY . .

# Laravel config
ENV SKIP_COMPOSER 1
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1
ENV APP_ENV production
ENV APP_DEBUG false
ENV LOG_CHANNEL stderr
ENV COMPOSER_ALLOW_SUPERUSER 1

CMD ["/start.sh"]