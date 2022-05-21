FROM php-fpm

ADD ./docker/jobs/crontab /crontab

# Install crontab
RUN set -eux; \
    apt-get update \
    && apt-get -y install cron

RUN /usr/bin/crontab /crontab

CMD ["cron","-f"]
