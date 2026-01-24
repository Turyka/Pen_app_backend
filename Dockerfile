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

# Install Playwright Python
RUN pip3 install playwright

# Install browsers ONLY (skip deps - we installed them manually)
RUN PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD=0 npm install -g playwright && \
    npx playwright install chromium

# Fix permissions on the Node playwright package
RUN chmod +x /usr/local/lib/node_modules/playwright/bin/playwright \
    && chmod +x /usr/local/lib/node_modules/playwright/bin/* \
    && find /usr/local/lib/node_modules/playwright -type f -name "*.js" -exec chmod +x {} \; 2>/dev/null || true

# Create proper driver symlink for Python Playwright
RUN mkdir -p /usr/lib/python3.11/site-packages/playwright/driver && \
    cp -rp /usr/local/lib/node_modules/playwright /usr/lib/python3.11/site-packages/playwright/driver/node && \
    chmod +x /usr/lib/python3.11/site-packages/playwright/driver/node/bin/playwright \
    && chmod +x /usr/lib/python3.11/site-packages/playwright/driver/node/bin/* \
    && find /usr/lib/python3.11/site-packages/playwright/driver/node -type f -name "*.js" -exec chmod +x {} \; 2>/dev/null || true

# Playwright environment variables
ENV CHROME_BIN=/usr/bin/chromium-browser
ENV PLAYWRIGHT_BROWSERS_PATH=0
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