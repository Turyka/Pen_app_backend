FROM richarvey/nginx-php-fpm:3.1.6

# Install CA certificates
RUN apk update && apk add ca-certificates && update-ca-certificates
ENV SSL_CERT_FILE=/etc/ssl/certs/ca-certificates.crt

# Install Chrome + Python for Facebook scraper
RUN apk add --no-cache \
    wget \
    gnupg \
    python3 \
    py3-pip \
    chromium \
    chromium-chromedriver \
    && pip3 install selenium webdriver-manager

# Install Chrome dependencies for Alpine
RUN apk add --no-cache \
    nss \
    freetype \
    freetype-dev \
    harfbuzz \
    ca-certificates \
    ttf-freefont \
    font-notice-ttf \
    fontconfig \
    dbus

# Setup Chrome environment
ENV CHROME_BIN=/usr/bin/chromium-browser
ENV CHROMEDRIVER_BIN=/usr/bin/chromedriver
ENV DISPLAY=:99

# Create symlinks for compatibility
RUN ln -sf /usr/bin/chromium-browser /usr/bin/google-chrome \
    && ln -sf /usr/bin/chromium-browser /usr/bin/chrome

# Test Chrome installation
RUN echo "Testing Chrome installation..." \
    && chromium-browser --version \
    && chromedriver --version

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