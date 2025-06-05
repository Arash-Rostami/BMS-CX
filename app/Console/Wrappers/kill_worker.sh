#!/bin/bash

PHP="/usr/local/bin/ea-php82"
ARTISAN="/home/communit/export/artisan"
LOG_FILE="/home/communit/export/storage/logs/stop_workers.log"

mkdir -p "$(dirname "$LOG_FILE")"

log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> "$LOG_FILE"
}

log "Starting graceful shutdown of queue workers..."

# Find all running Laravel queue:work processes
PIDS=$(pgrep -f "$PHP $ARTISAN queue:work")

if [ -z "$PIDS" ]; then
    log "No running queue workers found."
    exit 0
fi

# Gracefully stop each worker
for PID in $PIDS; do
    log "Sending SIGTERM to worker PID $PID"
    kill -15 "$PID"

    # Wait for the worker to exit (up to 60 seconds)
    for i in {1..60}; do
        if ps -p "$PID" > /dev/null 2>&1; then
            sleep 1
        else
            log "Worker PID $PID stopped gracefully."
            break
        fi
    done

    # If still alive after 60s, force kill (optional)
    if ps -p "$PID" > /dev/null 2>&1; then
        log "Worker PID $PID did not stop in time. Forcing kill."
        kill -9 "$PID"
    fi
done

log "Shutdown complete."
exit 0
