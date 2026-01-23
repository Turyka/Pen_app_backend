FROM richarvey/nginx-php-fpm:3.1.6

RUN apk update && apk add ca-certificates && update-ca-certificates
ENV SSL_CERT_FILE=/etc/ssl/certs/ca-certificates.crt

# Install dependencies
RUN apk add --no-cache \
    python3 \
    py3-pip \
    chromium \
    chromium-chromedriver \
    nodejs \
    git

# Install Playwright Python properly
RUN pip3 install playwright
RUN playwright install chromium

ENV PLAYWRIGHT_BROWSERS_PATH=/root/.cache/ms-playwright

COPY . .

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
