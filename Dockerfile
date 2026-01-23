FROM richarvey/nginx-php-fpm:3.1.6

# Install CA certificates
RUN apk update && apk add ca-certificates && update-ca-certificates
ENV SSL_CERT_FILE=/etc/ssl/certs/ca-certificates.crt

# Install Chrome + Python + Node.js + git
RUN apk add --no-cache \
    python3 \
    py3-pip \
    chromium \
    chromium-chromedriver \
    nodejs \
    npm \
    git \
    && pip3 install selenium

# Install Playwright via npm FIRST
RUN npm install -g playwright && \
    npx playwright install chromium

# Install Playwright Python package from GitHub
RUN pip3 install git+https://github.com/microsoft/playwright-python.git

# Force create the driver link (remove if exists first)
RUN rm -rf /usr/lib/python3.11/site-packages/playwright/driver && \
    mkdir -p /usr/lib/python3.11/site-packages/playwright/driver && \
    ln -sf /usr/local/lib/node_modules/playwright /usr/lib/python3.11/site-packages/playwright/driver/node

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