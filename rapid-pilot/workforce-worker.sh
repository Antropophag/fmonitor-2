#!/bin/sh
set -eu

export HOME=/home/fmonitor

while true; do
    if php rapid-pilot/hourly-bitrix-workforce.php; then
        touch /tmp/workforce-ready
    fi
    delay=$(php -r '$now=time(); $next=mktime((int)date("H") + 1, 7, 0); echo max(60, $next - $now);')
    sleep "$delay" &
    wait $!
done
