#!/bin/bash

# Option 1: Simple message to a file
echo "Cron test successful! $(date)" >>  /home/communit/export/storage/logs/cron.log

# Option 2: More detailed message with date and hostname
echo "Cron test on $(hostname) at $(date): Test successful!" >>  /home/communit/export/storage/logs/cron.log

# Option 3: Message with a variable
message="This is a test message with a variable."
echo "$message $(date)" >> /tmp/cron_test.log

# Option 4: Message including the script name
echo "Cron test from $0 at $(date)" >>  /home/communit/export/storage/logs/cron.log  # $0 expands to the script's filename
