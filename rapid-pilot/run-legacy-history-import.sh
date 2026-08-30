#!/usr/bin/env bash

set -euo pipefail

root_dir=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
env_file=${FMONITOR_SOURCE_ENV_FILE:-"${root_dir}/.env"}
pilot_container=${FMONITOR_PILOT_CONTAINER:-fmonitor2-pilot-pilot-1}
source_container=${FMONITOR_SOURCE_TUNNEL_CONTAINER:-fmonitor-db-tunnel}
source_network=${FMONITOR_SOURCE_TUNNEL_NETWORK:-fmonitor-db-access}

if [[ ! -r "$env_file" ]]; then
    echo "Legacy source environment is unavailable." >&2
    exit 66
fi

set -a
# shellcheck disable=SC1090
source "$env_file"
set +a

for name in FMONITOR_SOURCE_NAME FMONITOR_SOURCE_USER FMONITOR_SOURCE_PASSWORD; do
    if [[ -z "${!name:-}" ]]; then
        echo "Missing ${name}." >&2
        exit 64
    fi
done

docker inspect "$pilot_container" >/dev/null
docker inspect "$source_container" >/dev/null

if ! docker inspect "$pilot_container" \
    --format '{{range $name, $_ := .NetworkSettings.Networks}}{{println $name}}{{end}}' \
    | grep -Fxq "$source_network"; then
    docker network connect "$source_network" "$pilot_container"
fi

docker exec \
    -e FMONITOR_SOURCE_HOST="$source_container" \
    -e FMONITOR_SOURCE_PORT="${FMONITOR_SOURCE_TUNNEL_PORT:-13306}" \
    -e FMONITOR_SOURCE_NAME="$FMONITOR_SOURCE_NAME" \
    -e FMONITOR_SOURCE_USER="$FMONITOR_SOURCE_USER" \
    -e FMONITOR_SOURCE_PASSWORD="$FMONITOR_SOURCE_PASSWORD" \
    -e FMONITOR_DB_HOST="${FMONITOR_PILOT_DB_HOST:-mariadb}" \
    -e FMONITOR_DB_PORT="${FMONITOR_PILOT_DB_PORT:-3306}" \
    -e FMONITOR_DB_NAME="${FMONITOR_PILOT_DB_NAME:-fmonitor2_demo}" \
    -e FMONITOR_DB_USER="${FMONITOR_PILOT_DB_USER:-fmonitor2_demo}" \
    -e FMONITOR_DB_PASSWORD="${FMONITOR_PILOT_DB_PASSWORD:-fmonitor2_demo_local}" \
    "$pilot_container" sh -lc '
        set -eu
        manifest_root=/home/fmonitor/.local/state/fmonitor2/pilot-demo
        manifest=$(find "$manifest_root" -mindepth 2 -maxdepth 2 -type f -name active.json -print)
        count=$(printf "%s\n" "$manifest" | sed "/^$/d" | wc -l)
        if [ "$count" -ne 1 ]; then
            echo "Expected exactly one active pilot manifest; found $count." >&2
            exit 69
        fi
        export FMONITOR_PILOT_ACTIVE_MANIFEST=$manifest
        exec php rapid-pilot/import-legacy-history.php "$@"
    ' runner "$@"
