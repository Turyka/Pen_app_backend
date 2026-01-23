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

# Install Playwright via npm FIRST (this creates the driver)
RUN npm install -g playwright

# Install Chromium browser
RUN npx playwright install chromium

# NOW install Playwright Python package
RUN pip3 install git+https://github.com/microsoft/playwright-python.git

# Create the driver symlink that Playwright Python expects
RUN mkdir -p /usr/lib/python3.11/site-packages/playwright/driver && \
    ln -sf /usr/local/lib/node_modules/playwright /usr/lib/python3.11/site-packages/playwright/driver/node

# Set environment variables
ENV CHROME_BIN=/usr/bin/chromium-browser
ENV CHROME_DRIVER=/usr/bin/chromedriver
ENV PLAYWRIGHT_BROWSERS_PATH=/root/.cache/ms-playwright

# Verify installation works
RUN python3 -c "
from playwright.sync_api import sync_playwright
with sync_playwright() as p:
    browser = p.chromium.launch(headless=True, args=['--no-sandbox'])
    print('SUCCESS: Playwright is working!')
    browser.close()
"

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