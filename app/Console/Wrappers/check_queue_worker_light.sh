#!/bin/bash

PHP="/usr/local/bin/ea-php82"
ARTISAN="/home/communit/export/artisan"
LOG_DIR="/home/communit/export/storage/logs"
LOG_FILE="$LOG_DIR/queue_worker_cron.log"
PID_FILE="/tmp/queue_worker_cron.pid"

# 1. Prepare logs
mkdir -p "$LOG_DIR"
log() { echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> "$LOG_FILE"; }

# 2. PID lock (flock)
exec 200>"$PID_FILE"
flock -n 200 || { log "Queue worker already running"; exit 0; }
echo $$ 1>&200
trap 'rm -f "$PID_FILE"' EXIT

# 3. Launch worker
# log "Starting queue worker (PID $$)"
ionice -c2 -n7 nice -n 10 \
  $PHP $ARTISAN queue:work \
    --stop-when-empty \
    --tries=3 \
    --timeout=45 \
    --sleep=3 \
    --max-jobs=100 \
    --memory=128 \
  >>"$LOG_FILE" 2>&1

# 4. Exit‑code logic
EXIT_CODE=$?
case $EXIT_CODE in
  0) :;; #  log "Finished cleanly";;
  137) log "WARNING: Killed (OOM or SIGKILL)";;
  *)   log "ERROR: Exited with code $EXIT_CODE";;
esac
