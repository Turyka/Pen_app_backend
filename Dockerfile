FROM richarvey/nginx-php-fpm:3.1.6

# Install CA certificates and required libs
RUN apk update && apk add --no-cache \
    ca-certificates \
    chromium \
    chromium-chromedriver \
    python3 \
    py3-pip \
    bash \
    curl \
    gnupg \
    libstdc++ \
    nss \
    libx11 \
    libxcomposite \
    libxrandr \
    libc6-compat \
    && update-ca-certificates


    
# Upgrade pip and install Python packages
RUN python3 -m ensurepip \
    && pip3 install --upgrade pip \
    && pip3 install selenium playwright \
    && playwright install chromium

# Set Chromium paths
ENV CHROME_BIN=/usr/bin/chromium
ENV CHROME_DRIVER=/usr/bin/chromedriver

# Copy application
COPY . .

# Laravel environment
ENV SKIP_COMPOSER=1
ENV WEBROOT=/var/www/html/public
ENV PHP_ERRORS_STDERR=1
ENV RUN_SCRIPTS=1
ENV REAL_IP_HEADER=1
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr
ENV COMPOSER_ALLOW_SUPERUSER=1

# Ensure start.sh is executable
RUN chmod +x /start.sh

CMD ["/start.sh"]
