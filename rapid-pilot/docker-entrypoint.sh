#!/bin/sh
set -eu

export HOME=/home/fmonitor
mkdir -p "$HOME/.local/state/fmonitor2"

container_address=$(hostname -i | awk '{print $1}')
socat TCP4-LISTEN:23306,bind=127.0.0.1,fork,reuseaddr TCP4:mariadb:3306 &
process_prefix=$(php -r 'echo "fm2d_", substr(hash("sha256", (string) realpath(getcwd())), 0, 8), "_g1_";')
FMONITOR_DB_HOST=127.0.0.1 \
FMONITOR_DB_PORT=23306 \
FMONITOR_DB_NAME="$FMONITOR_DEMO_DB_NAME" \
FMONITOR_DB_USER="$FMONITOR_DEMO_DB_USER" \
FMONITOR_DB_PASSWORD="$FMONITOR_DEMO_DB_PASSWORD" \
FMONITOR_PROCESS_TABLE_PREFIX="$process_prefix" \
php bin/fmonitor2-migrate.php
php rapid-pilot/docker-bootstrap.php
socat "TCP4-LISTEN:8092,bind=${container_address},fork,reuseaddr" TCP4:127.0.0.1:8092 &

exec php rapid-pilot/start.php
