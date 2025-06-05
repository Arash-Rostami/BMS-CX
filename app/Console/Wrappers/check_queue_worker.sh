#!/bin/bash

# Configuration
ARTISAN_PATH="/home/communit/export/artisan"
PHP_PATH="/usr/local/bin/ea-php82"
LOG_FILE="/home/communit/export/storage/logs/queue_monitor.log"
PID_FILE="/home/communit/export/storage/logs/queue_worker.pid"

# Improved logging function
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> "$LOG_FILE"
}

# Start worker with better error handling
start_worker() {
    log "Starting queue worker..."
    nohup "$PHP_PATH" "$ARTISAN_PATH" queue:work --sleep=3 --tries=3 >> "$LOG_FILE" 2>&1 &

    if [ $? -ne 0 ]; then
        log "ERROR: Failed to start worker process"
        exit 1
    fi

    echo $! > "$PID_FILE"
    log "Queue worker started with PID $(cat "$PID_FILE")"
}

# Main execution
log "Checking queue worker status..."

if [ -f "$PID_FILE" ]; then
    PID=$(cat "$PID_FILE")
    if ps -p $PID > /dev/null 2>&1; then
        log "Queue worker is running with PID $PID"
    else
        log "Stale PID file found. Removing and restarting worker."
        rm -f "$PID_FILE"
        start_worker
    fi
else
    log "No PID file found. Starting worker."
    start_worker
fi
