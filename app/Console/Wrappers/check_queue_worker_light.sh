#!/bin/bash

PHP_PATH="/usr/local/bin/ea-php82"
ARTISAN_PATH="/home/communit/export/artisan"
LOG_FILE="/home/communit/export/storage/logs/queue_worker_cron.log"
LOCK_FILE="/tmp/laravel_export_worker.lock"

exec 200>"$LOCK_FILE"
flock -n 200 || exit 0

ionice -c2 -n7 nice -n 10 \
    $PHP_PATH $ARTISAN_PATH queue:work \
    --stop-when-empty \
    --max-jobs=50 \
    --max-time=300 \
    --tries=3 \
    --timeout=43 \
    --memory=128 \
    >/dev/null 2>>"$LOG_FILE"
