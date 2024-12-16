#!/bin/bash

ARTISAN_PATH="/home/communit/export/artisan"
LOG_FILE="/home/communit/export/storage/logs/queue_monitor.log"

echo "$(date) - Cron job triggered" >> "$LOG_FILE"

if ! pgrep -f "queue:work --daemon"; then
    echo "$(date) - Queue worker not found, starting..." >> "$LOG_FILE"
    /usr/local/bin/ea-php82 "$ARTISAN_PATH" queue:work --daemon --sleep=3 --tries=3 >> "$LOG_FILE" 2>&1 &
else
    echo "$(date) - Queue worker is already running." >> "$LOG_FILE"
fi
