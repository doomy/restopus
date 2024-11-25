FROM php:8.4-rc-fpm

# Copy application files
COPY . /srv

# Set working directory
WORKDIR /srv