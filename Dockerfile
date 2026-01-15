FROM richarvey/nginx-php-fpm:3.1.6

# System deps + BUILD ESSENTIALS (critical for Alpine)
RUN apk update && apk add --no-cache \
    ca-certificates \
    python3 \
    py3-pip \
    py3-setuptools \
    py3-wheel \
    gcc \
    g++ \
    python3-dev \
    musl-dev \
    linux-headers \
    wget \
    gnupg \
    && update-ca-certificates

# Create virtualenv (fixes root warnings + isolation)
RUN python3 -m venv /opt/venv
ENV PATH="/opt/venv/bin:$PATH"

# Upgrade pip + install (NO VERSION PIN - let it pick Alpine-compatible)
RUN pip install --no-cache-dir --upgrade pip setuptools wheel && \
    pip install --no-cache-dir requests && \
    pip install --no-cache-dir playwright

# Playwright browsers
RUN playwright install-deps && \
    playwright install chromium

# Cleanup build deps
RUN apk del gcc g++ python3-dev musl-dev linux-headers

ENV SSL_CERT_FILE=/etc/ssl/certs/ca-certificates.crt
ENV PLAYWRIGHT_BROWSERS_PATH=0

COPY . /var/www/html
WORKDIR /var/www/html

ENV SKIP_COMPOSER 1
ENV WEBROOT /var/www/html/public
ENV APP_ENV production

CMD ["/start.sh"]