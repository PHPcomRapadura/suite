FROM webdevops/php-nginx:8.4

# Used by the local pre-push hook to run Pest's coverage gate.
RUN pecl install pcov \
    && docker-php-ext-enable pcov
