#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
token="$(php -r 'echo bin2hex(random_bytes(8));')"
container="fm2-ia-red-${token}"
password="fm2_ia_${token}"
port=""

cleanup() {
  docker rm -f "$container" >/dev/null 2>&1 || true
}
trap cleanup EXIT INT TERM

docker run -d --name "$container" --tmpfs /var/lib/mysql \
  -e MARIADB_ROOT_PASSWORD="$password" \
  -e MARIADB_DATABASE=fmonitor2_identity_red \
  -p 127.0.0.1::3306 \
  mariadb:11.4.7-noble \
  --general-log=1 --log-output=TABLE >/dev/null

port="$(docker inspect --format '{{(index (index .NetworkSettings.Ports "3306/tcp") 0).HostPort}}' "$container")"
for _ in $(seq 1 60); do
  if docker exec "$container" healthcheck.sh --connect --innodb_initialized >/dev/null 2>&1; then break; fi
  sleep 1
done
docker exec "$container" healthcheck.sh --connect --innodb_initialized >/dev/null

export FMONITOR_TEST_DB_HOST=127.0.0.1
export FMONITOR_TEST_DB_PORT="$port"
export FMONITOR_TEST_DB_ADMIN_USER=root
export FMONITOR_TEST_DB_ADMIN_PASSWORD="$password"

php "$repo_root/tests/InstallationProcess/identity_access_runtime_ddl_001_test.php"
