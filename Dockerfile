FROM richarvey/nginx-php-fpm:3.1.6

# Install CA certificates
RUN apk update && apk add ca-certificates && update-ca-certificates
ENV SSL_CERT_FILE=/etc/ssl/certs/ca-certificates.crt

# Install Chrome + Python + Node.js
RUN apk add --no-cache \
    python3 \
    py3-pip \
    chromium \
    chromium-chromedriver \
    nodejs \
    npm \
    && pip3 install selenium

# Install Playwright Python package with explicit driver path
RUN pip3 install playwright

# Download and install Playwright driver manually
RUN cd /tmp && \
    wget -q https://registry.npmjs.org/playwright/-/playwright-1.40.0.tgz && \
    tar -xzf playwright-1.40.0.tgz && \
    mkdir -p /usr/lib/python3.11/site-packages/playwright/driver && \
    cp -r /tmp/package/* /usr/lib/python3.11/site-packages/playwright/driver/ && \
    chmod +x /usr/lib/python3.11/site-packages/playwright/driver/cli.js

# Install Chromium browser
RUN python3 -m playwright install chromium

# Set environment variables
ENV CHROME_BIN=/usr/bin/chromium-browser
ENV CHROME_DRIVER=/usr/bin/chromedriver
ENV PLAYWRIGHT_BROWSERS_PATH=/root/.cache/ms-playwright

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