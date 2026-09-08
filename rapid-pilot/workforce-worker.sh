#!/bin/sh
set -eu

state_root=${FMONITOR_SESSION_STATE_ROOT:-/home/fmonitor/.local/state/fmonitor2}
ready_file=${FMONITOR_WORKFORCE_READY_FILE:-/tmp/workforce-ready}

once=false
if [ "$#" -eq 1 ] && [ "$1" = "--once" ]; then
    once=true
elif [ "$#" -ne 0 ]; then
    echo '{"status":"failed","reason":"SYNC_UNAVAILABLE"}'
    exit 2
fi

manifest=
for candidate in "$state_root"/pilot-demo/*/active.json; do
    [ -f "$candidate" ] || continue
    [ -z "$manifest" ] || { echo '{"status":"failed","reason":"SYNC_UNAVAILABLE"}'; exit 1; }
    manifest=$candidate
done
[ -n "$manifest" ] || { echo '{"status":"failed","reason":"SYNC_UNAVAILABLE"}'; exit 1; }
prefix=$(php -r '$m=json_decode((string)file_get_contents($argv[1]),true,8,JSON_THROW_ON_ERROR);$p=$m["processPrefix"]??null;if(($m["state"]??null)!=="ready"||!is_string($p)||preg_match("/^[A-Za-z0-9_]{1,25}$/D",$p)!==1)exit(1);echo $p;' "$manifest" 2>/dev/null) || { echo '{"status":"failed","reason":"SYNC_UNAVAILABLE"}'; exit 1; }
export FMONITOR_PROCESS_TABLE_PREFIX=$prefix

while true; do
    if php bin/fmonitor2-sync-workforce.php; then
        touch "$ready_file"
        status=0
    else
        status=$?
    fi
    [ "$once" = false ] || exit "$status"
    delay=$(php -r '$now=time(); $next=mktime((int)date("H") + 1, 7, 0); echo max(60, $next - $now);')
    sleep "$delay" &
    wait $!
done
