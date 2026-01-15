FROM richarvey/nginx-php-fpm:3.1.6

# Install CA certs
RUN apk update && apk add --no-cache ca-certificates && update-ca-certificates

# Install Python + Playwright deps
RUN apk add --no-cache \
    python3 \
    py3-pip \
    wget \
    gnupg

# Install Python packages
RUN pip3 install --no-cache-dir \
    playwright \
    requests

# Install Playwright Chromium
RUN python3 -m playwright install-deps && \
    python3 -m playwright install chromium

ENV SSL_CERT_FILE=/etc/ssl/certs/ca-certificates.crt
ENV PLAYWRIGHT_BROWSERS_PATH=0

# Copy app
COPY . /var/www/html
WORKDIR /var/www/html

# Laravel config
ENV SKIP_COMPOSER 1
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV APP_ENV production
ENV APP_DEBUG false

CMD ["/start.sh"]