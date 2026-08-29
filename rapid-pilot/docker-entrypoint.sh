#!/bin/sh
set -eu

export HOME=/home/fmonitor
mkdir -p "$HOME/.local/state/fmonitor2"

container_address=$(hostname -i | awk '{print $1}')
socat TCP4-LISTEN:23306,bind=127.0.0.1,fork,reuseaddr TCP4:mariadb:3306 &
php rapid-pilot/docker-bootstrap.php
socat "TCP4-LISTEN:8092,bind=${container_address},fork,reuseaddr" TCP4:127.0.0.1:8092 &

exec php rapid-pilot/start.php
