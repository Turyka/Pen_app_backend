# Use the official Playwright Python image (v1.50.0-noble)
FROM mcr.microsoft.com/playwright/python:v1.50.0-noble

# Install PHP, Nginx, and other dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    php-fpm \
    git \
    ca-certificates \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Set environment variables
ENV PLAYWRIGHT_BROWSERS_PATH=/root/.cache/ms-playwright
ENV SSL_CERT_FILE=/etc/ssl/certs/ca-certificates.crt
ENV WEBROOT=/var/www/html/public
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr

# Copy your app into the container
COPY . /var/www/html/public

# Expose HTTP port
EXPOSE 80

# Ensure permissions
RUN chown -R www-data:www-data /var/www/html/public

# Start PHP-FPM and Nginx when container starts
CMD ["bash", "-c", "service php7.4-fpm start && nginx -g 'daemon off;'"]
