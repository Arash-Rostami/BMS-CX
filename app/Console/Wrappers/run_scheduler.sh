#!/bin/bash

ARTISAN_PATH="/home/communit/export/artisan"
LOG_FILE="/home/communit/export/storage/logs/scheduler.log"

/usr/local/bin/ea-php82 "$ARTISAN_PATH" schedule:run >> "$LOG_FILE" 2>&1
