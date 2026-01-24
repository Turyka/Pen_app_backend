FROM richarvey/nginx-php-fpm:3.1.6

# Install CA certificates
RUN apk update && apk add ca-certificates && update-ca-certificates

# Set SSL cert environment
ENV SSL_CERT_FILE=/etc/ssl/certs/ca-certificates.crt

# Install Chrome + Python + Node.js + git + Playwright deps
RUN apk add --no-cache \
    python3 \
    py3-pip \
    chromium \
    chromium-chromedriver \
    nodejs \
    npm \
    git \
    nss \
    freetype \
    freetype-dev \
    harfbuzz \
    ca-certificates \
    ttf-freefont \
    && pip3 install selenium

# Install Playwright Python from GitHub (Alpine compatible)
RUN pip3 install git+https://github.com/microsoft/playwright-python.git

# Install Playwright browser via npm (no --with-deps)
RUN npm install -g playwright && \
    npx playwright install chromium

# CRITICAL: Copy entire node_modules structure (not symlink) + fix ALL permissions
RUN mkdir -p /usr/lib/python3.11/site-packages/playwright/driver && \
    cp -a /usr/local/lib/node_modules/playwright /usr/lib/python3.11/site-packages/playwright/driver/node && \
    chmod +x /usr/lib/python3.11/site-packages/playwright/driver/node/bin/playwright && \
    chmod +x /usr/lib/python3.11/site-packages/playwright/driver/node/bin/* 2>/dev/null || true && \
    find /usr/lib/python3.11/site-packages/playwright/driver/node -name "*.js" -type f -exec chmod +x {} + 2>/dev/null || true && \
    chmod +x /usr/lib/python3.11/site-packages/playwright/driver/node/package/bin/* 2>/dev/null || true

# Playwright environment variables
ENV CHROME_BIN=/usr/bin/chromium-browser
ENV PLAYWRIGHT_BROWSERS_PATH=/root/.cache/ms-playwright
ENV PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD=1

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