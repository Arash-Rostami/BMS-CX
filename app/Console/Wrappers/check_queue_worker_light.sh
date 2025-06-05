#!/bin/bash


PHP="/usr/local/bin/ea-php82"
ARTISAN="/home/communit/export/artisan"
LOG_DIR="/home/communit/export/storage/logs"
LOG_FILE="$LOG_DIR/queue_worker_cron.log"


mkdir -p "$LOG_DIR"


log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> "$LOG_FILE"
}

log "Starting queue worker run"
sleep 5


$PHP $ARTISAN queue:work \
    --stop-when-empty \
    --tries=3 \
    --timeout=45 \
    --sleep=3 \
    >> "$LOG_FILE" 2>&1

EXIT_CODE=$?

if [ $EXIT_CODE -eq 0 ]; then
    log "Queue worker finished cleanly"
else
    log "ERROR: Queue worker exited with code $EXIT_CODE"
fi
