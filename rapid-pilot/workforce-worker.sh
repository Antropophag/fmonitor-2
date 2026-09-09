#!/bin/sh
set -eu

# Historical one-shot adapter. Production scheduling belongs to the Jobs runtime.
invalid() {
    printf '%s\n' '{"ok":false,"error":"CONFIGURATION_INVALID"}'
    exit 64
}
[ "$#" -eq 1 ] && [ "$1" = '--once' ] || invalid
[ "${FMONITOR_PROCESS_TABLE_PREFIX+x}" = x ] || invalid
[ "${#FMONITOR_PROCESS_TABLE_PREFIX}" -le 25 ] || invalid
case "$FMONITOR_PROCESS_TABLE_PREFIX" in *[!A-Za-z0-9_]*) invalid ;; esac
repository=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
cd "$repository"
exec php bin/fmonitor2-sync-workforce.php
