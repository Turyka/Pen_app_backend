FROM richarvey/nginx-php-frm:3.1.6

# All deps in ONE layer (Render loves this)
RUN apk update && apk add --no-cache \
    ca-certificates chromium chromium-chromedriver python3 py3-pip nodejs npm git \
    nss freetype harfbuzz ttf-freefont && \
    update-ca-certificates

ENV SSL_CERT_FILE=/etc/ssl/certs/ca-certificates.crt \
    CHROME_BIN=/usr/bin/chromium-browser

# Python FIRST
RUN pip3 install --no-cache-dir selenium "git+https://github.com/microsoft/playwright-python.git"

# Node + Playwright browsers
RUN npm install -g playwright && npx playwright install --with-deps chromium

# Fix Playwright (idempotent - NO mkdir errors)
RUN mkdir -p /usr/lib/python3.*/site-packages/playwright/driver/node 2>/dev/null || true && \
    cp -a /usr/local/lib/node_modules/playwright/* /usr/lib/python3.*/site-packages/playwright/driver/node/ 2>/dev/null || true && \
    find /usr/lib/python3.*/site-packages/playwright -type f \( -name "*.js" -o -name "playwright" \) -exec chmod +x {} + 2>/dev/null || true

# Copy Laravel
COPY . /var/www/html
RUN chmod +x /var/www/html/public/*.py

ENV PLAYWRIGHT_BROWSERS_PATH=/root/.cache/ms-playwright \
    SKIP_COMPOSER=1 \
    WEBROOT=/var/www/html/public \
    PHP_ERRORS_STDERR=1 \
    APP_ENV=production

CMD ["/start.sh"]