FROM webdevops/php-nginx:8.4

# Used by the local pre-push hook to run Pest's coverage gate.
RUN if ! php -m | grep -qi '^pcov$'; then pecl install pcov; fi \
    && docker-php-ext-enable pcov
