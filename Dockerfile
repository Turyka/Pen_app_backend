FROM richarvey/nginx-php-fpm:3.1.6

# Install CA certificates
RUN apk update && apk add ca-certificates && update-ca-certificates
ENV SSL_CERT_FILE=/etc/ssl/certs/ca-certificates.crt

# Install Chrome + Python + Selenium/Playwright deps
RUN apk add --no-cache \
    python3 py3-pip chromium chromium-chromedriver nodejs npm git \
    nss freetype freetype-dev harfbuzz ca-certificates ttf-freefont

# Install Python packages
RUN pip3 install selenium git+https://github.com/microsoft/playwright-python.git

# Install npm playwright
RUN npm install -g playwright && npx playwright install chromium

# Fix Playwright driver permissions (CRITICAL)
RUN mkdir -p /usr/lib/python3.11/site-packages/playwright/driver/node && \
    cp -a /usr/local/lib/node_modules/playwright/* /usr/lib/python3.11/site-packages/playwright/driver/node/ && \
    find /usr/lib/python3.11/site-packages/playwright/driver/node -type f \( -name "*.js" -o -name "playwright" \) -exec chmod +x {} + 

ENV CHROME_BIN=/usr/bin/chromium-browser PLAYWRIGHT_BROWSERS_PATH=/root/.cache/ms-playwright

# Copy Laravel + scrapers
COPY . /var/www/html

# Laravel config
ENV SKIP_COMPOSER=1 WEBROOT=/var/www/html/public PHP_ERRORS_STDERR=1 RUN_SCRIPTS=1
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr COMPOSER_ALLOW_SUPERUSER=1

CMD ["/start.sh"]