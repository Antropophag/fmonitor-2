#!/bin/sh
set -eu
export HOME=/home/fmonitor
mkdir -p "$HOME/.local/state/fmonitor2"
container_address=$(hostname -i | awk '{print $1}')
# Prepared statements exchange small packets; do not delay them on either TCP leg.
socat TCP4-LISTEN:23306,bind=127.0.0.1,fork,reuseaddr,nodelay TCP4:mariadb:3306,nodelay &
pilot_prefix=$(php -r 'echo "fm2p_",substr(hash("sha256",(string)realpath(getcwd())),0,8),"_g1_";')
FMONITOR_DB_HOST=127.0.0.1 FMONITOR_DB_PORT=23306 FMONITOR_DB_NAME="$FMONITOR_DEMO_DB_NAME" FMONITOR_DB_USER="$FMONITOR_DEMO_DB_USER" FMONITOR_DB_PASSWORD="$FMONITOR_DEMO_DB_PASSWORD" FMONITOR_PROCESS_TABLE_PREFIX="$pilot_prefix" php bin/fmonitor2-migrate.php
FMONITOR_DB_HOST=127.0.0.1 FMONITOR_DB_PORT=23306 FMONITOR_DB_NAME="$FMONITOR_DEMO_DB_NAME" FMONITOR_DB_USER="$FMONITOR_DEMO_DB_USER" FMONITOR_DB_PASSWORD="$FMONITOR_DEMO_DB_PASSWORD" FMONITOR_BOOTSTRAP_PROCESS_PREFIX="$pilot_prefix" FMONITOR_BOOTSTRAP_LEGACY_PREFIX="$pilot_prefix" php rapid-pilot/docker-bootstrap.php
socat "TCP4-LISTEN:8092,bind=${container_address},fork,reuseaddr" TCP4:127.0.0.1:8092 &
exec php rapid-pilot/start.php
