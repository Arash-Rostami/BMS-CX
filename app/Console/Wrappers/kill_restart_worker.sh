#!/bin/bash

ARTISAN_PATH="/home/communit/export/artisan"
PHP_BIN="/usr/local/bin/ea-php82"
LOG_FILE="/home/communit/export/storage/logs/queue_monitor.log"
PID_FILE="/home/communit/export/storage/logs/queue_worker.pid"

log() { echo "$(date '+%Y-%m-%d %H:%M:%S') – $1" >> "$LOG_FILE"; }

log "Issuing artisan queue:restart"
$PHP_BIN "$ARTISAN_PATH" queue:restart >> "$LOG_FILE" 2>&1
sleep 5

PIDS=$(pgrep -f "$PHP_BIN $ARTISAN_PATH queue:work")
if [ -n "$PIDS" ]; then
  log "SIGTERM to lingering PIDs: $PIDS"
  kill $PIDS
  sleep 5
  rm -f "$PID_FILE"
fi

REMAIN=$(pgrep -f "$PHP_BIN $ARTISAN_PATH queue:work")
if [ -n "$REMAIN" ]; then
  log "SIGKILL to stubborn PIDs: $REMAIN"
  kill -9 $REMAIN
fi

log "Starting fresh queue worker"
nohup "$PHP_BIN" "$ARTISAN_PATH" queue:work --sleep=3 --tries=3 >> "$LOG_FILE" 2>&1 &
if [ $? -ne 0 ]; then
  log "ERROR: failed to launch queue worker"
  exit 1
fi

NEW_PID=$!
echo $NEW_PID > "$PID_FILE"
log "New worker PID: $NEW_PID"
