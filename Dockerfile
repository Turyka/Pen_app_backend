# ===========================================
# Full Laravel + Python Scraper Dockerfile
# ===========================================

FROM php:8.2-fpm-bullseye

# -------------------------------
# Install system dependencies
# -------------------------------
RUN apt-get update && apt-get install -y \
    # Basic tools
    wget curl gnupg bash unzip git \
    # Chromium for scraping
    chromium chromium-driver \
    libnss3 libx11-6 libxcomposite1 libxrandr2 libglib2.0-0 \
    # Python
    python3 python3-pip \
    # Other dependencies
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# -------------------------------
# Set Chrome environment variables
# -------------------------------
ENV CHROME_BIN=/usr/bin/chromium
ENV CHROME_DRIVER=/usr/bin/chromedriver

# -------------------------------
# Install Python packages
# -------------------------------
RUN pip3 install --no-cache-dir selenium playwright \
    && playwright install chromium

# -------------------------------
# Set Laravel environment variables
# -------------------------------
ENV SKIP_COMPOSER=1
ENV WEBROOT=/var/www/html/public
ENV PHP_ERRORS_STDERR=1
ENV RUN_SCRIPTS=1
ENV REAL_IP_HEADER=1
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr
ENV COMPOSER_ALLOW_SUPERUSER=1

# -------------------------------
# Copy your app code
# -------------------------------
COPY . /var/www/html
WORKDIR /var/www/html

# Make start.sh executable
RUN chmod +x /var/www/html/start.sh

# -------------------------------
# Default command
# -------------------------------
CMD ["/var/www/html/start.sh"]
