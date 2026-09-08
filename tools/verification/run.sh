#!/usr/bin/env bash
set -uo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$repo_root"

export FMONITOR_TEST_DB_HOST="${FMONITOR_TEST_DB_HOST:-127.0.0.1}"
export FMONITOR_TEST_DB_PORT="${FMONITOR_TEST_DB_PORT:-23306}"
export FMONITOR_TEST_DB_NAME="${FMONITOR_TEST_DB_NAME:-fmonitor2_test}"
export FMONITOR_TEST_DB_USER="${FMONITOR_TEST_DB_USER:-fmonitor2_test}"
export FMONITOR_TEST_DB_PASSWORD="${FMONITOR_TEST_DB_PASSWORD:-fmonitor2_test_local}"
export FMONITOR_TEST_DB_ADMIN_USER="${FMONITOR_TEST_DB_ADMIN_USER:-root}"
export FMONITOR_TEST_DB_ADMIN_PASSWORD="${FMONITOR_TEST_DB_ADMIN_PASSWORD:-fmonitor2_test_root_local}"
export FMONITOR_DB_HOST="$FMONITOR_TEST_DB_HOST"
export FMONITOR_DB_PORT="$FMONITOR_TEST_DB_PORT"
export FMONITOR_DB_NAME="$FMONITOR_TEST_DB_NAME"
export FMONITOR_DB_USER="$FMONITOR_TEST_DB_USER"
export FMONITOR_DB_PASSWORD="$FMONITOR_TEST_DB_PASSWORD"
export FMONITOR_DEMO_DB_HOST="$FMONITOR_TEST_DB_HOST"
export FMONITOR_DEMO_DB_PORT="$FMONITOR_TEST_DB_PORT"
export FMONITOR_DEMO_DB_NAME="$FMONITOR_TEST_DB_NAME"
export FMONITOR_DEMO_DB_USER="$FMONITOR_TEST_DB_USER"
export FMONITOR_DEMO_DB_PASSWORD="$FMONITOR_TEST_DB_PASSWORD"
export FMONITOR_VERIFY_DB_HOST="$FMONITOR_TEST_DB_HOST"
export FMONITOR_VERIFY_DB_PORT="$FMONITOR_TEST_DB_PORT"
export FMONITOR_VERIFY_DB_NAME="$FMONITOR_TEST_DB_NAME"
export FMONITOR_VERIFY_DB_USER="$FMONITOR_TEST_DB_USER"
export FMONITOR_VERIFY_DB_PASSWORD="$FMONITOR_TEST_DB_PASSWORD"

fail() {
  local category="$1"
  shift
  printf '%s: %s\n' "$category" "$*" >&2
  exit 1
}

require_db() {
  php -r '$c=@new mysqli(getenv("FMONITOR_TEST_DB_HOST"),getenv("FMONITOR_TEST_DB_ADMIN_USER"),getenv("FMONITOR_TEST_DB_ADMIN_PASSWORD"),null,(int)getenv("FMONITOR_TEST_DB_PORT")); exit($c->connect_errno === 0 ? 0 : 1);' \
    || fail SETUP_FAILURE "test MariaDB is unavailable; run make test-env-up"
}

run_files() {
  local file
  local failures=()
  for file in "$@"; do
    printf 'VERIFY %s\n' "$file"
    if ! php "$file"; then
      printf 'REGRESSION_FAILURE: %s\n' "$file" >&2
      failures+=("$file")
    fi
  done
  if ((${#failures[@]} > 0)); then
    fail REGRESSION_FAILURE "${#failures[@]} verifier(s) failed"
  fi
}

for required_directory in tests/InstallationProcess tests/AssignmentOrderComposition tests/Verification; do
  test -d "$required_directory" || fail SETUP_FAILURE "missing verification directory: $required_directory"
done
unit_files=()
db_files=()
node_files=()
while IFS= read -r file; do
  if rg -q 'FMONITOR_TEST_DB|new mysqli' "$file"; then
    db_files+=("$file")
  else
    unit_files+=("$file")
  fi
done < <(find tests/InstallationProcess -maxdepth 1 -type f -name '*test.php' -print | sort)
while IFS= read -r file; do
  case "$file" in
    tests/AssignmentOrderComposition/selection_command_outcomes_001_test.php|tests/AssignmentOrderComposition/selection_command_recovery_001_test.php|tests/AssignmentOrderComposition/selection_command_tracer_001_test.php)
      unit_files+=("$file") ;;
    *) db_files+=("$file") ;;
  esac
done < <(find tests/AssignmentOrderComposition -maxdepth 1 -type f -name '*test.php' -print | LC_ALL=C sort)
while IFS= read -r file; do node_files+=("$file"); done \
  < <(find tests/Verification -maxdepth 1 -type f -name '*_test.mjs' -print | LC_ALL=C sort)
# Bash 3.2 with nounset requires guarded expansion for declared empty arrays.
sorted_files=()
while IFS= read -r file; do test -z "$file" || sorted_files+=("$file"); done < <(printf '%s\n' ${unit_files[@]+"${unit_files[@]}"} | LC_ALL=C sort)
unit_files=(${sorted_files[@]+"${sorted_files[@]}"}); sorted_files=()
while IFS= read -r file; do test -z "$file" || sorted_files+=("$file"); done < <(printf '%s\n' ${db_files[@]+"${db_files[@]}"} | LC_ALL=C sort)
db_files=(${sorted_files[@]+"${sorted_files[@]}"})

if [[ "${1:-}" == list ]]; then
  test "$#" -eq 2 || fail SETUP_FAILURE "usage: tools/verification/run.sh list unit|db"
  case "$2" in
    unit)
      for file in ${unit_files[@]+"${unit_files[@]}"}; do printf 'php\t%s\n' "$file"; done
      for file in ${node_files[@]+"${node_files[@]}"}; do printf 'node\t%s\n' "$file"; done ;;
    db) for file in ${db_files[@]+"${db_files[@]}"}; do printf 'php\t%s\n' "$file"; done ;;
    *) fail SETUP_FAILURE "unknown list suite '$2'; expected unit|db" ;;
  esac
  exit 0
fi


case "${1:-}" in
  unit)
    unit_failed=0
    (run_files ${unit_files[@]+"${unit_files[@]}"}) || unit_failed=1
    for file in ${node_files[@]+"${node_files[@]}"}; do
      printf 'VERIFY %s\n' "$file"
      if ! node "$file"; then
        printf 'REGRESSION_FAILURE: %s\n' "$file" >&2
        unit_failed=1
      fi
    done
    bash tools/delivery/test.sh || unit_failed=1
    if ((unit_failed)); then fail REGRESSION_FAILURE "unit suite failures"; fi
    ;;
  db)
    require_db
    run_files ${db_files[@]+"${db_files[@]}"}
    ;;
  characterization)
    python3 tests/Verification/pilot_healthcheck_cookie_failure_001_test.py || fail REGRESSION_FAILURE "healthcheck cookie persistence"
    python3 tests/Verification/pilot_healthcheck_session_001_test.py || fail REGRESSION_FAILURE "healthcheck session lifecycle"
    run_files \
      rapid-pilot/verify-auth-hot-path.php \
      rapid-pilot/verify-calendar-projections.php \
      rapid-pilot/verify-checklist-current-crew.php \
      tests/Verification/characterize_inspection_schedule_duplicate_001_test.php \
      tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php \
      tests/Verification/characterize_inspection_photo_revoke_001_test.php \
      tests/Verification/characterize_inspection_photo_rejections_001_test.php \
      tests/Verification/characterize_inspection_photo_upload_001_test.php \
      rapid-pilot/verify-completion-flow.php \
      rapid-pilot/verify-deployment-contract.php \
      rapid-pilot/verify-focus-contract.php \
      rapid-pilot/verify-object-queue-filters.php \
      tests/Verification/characterize_object_detail_import_001_test.php \
      tests/Verification/harness_otiz_canonical_compat_001_test.php \
      rapid-pilot/verify-premium-calculation.php \
      rapid-pilot/verify-visual-contract.php
    ;;
  e2e)
    require_db
    run_files tests/InstallationProcess/pilot_e2e_flow_001_test.php
    ;;
  lint)
    while IFS= read -r file; do php -l "$file" >/dev/null || fail REGRESSION_FAILURE "$file syntax"; done \
      < <(find app bin public rapid-pilot tests tools -type f -name '*.php' -print | sort)
    ;;
  red)
    test -n "${2:-}" || fail SETUP_FAILURE "usage: tools/verification/run.sh red <test-file>"
    if php "$2"; then
      fail RED_ASSERTION "expected failure but $2 passed"
    fi
    printf 'RED_ASSERTION: expected failing behavior observed in %s\n' "$2"
    ;;
  *)
    fail SETUP_FAILURE "unknown suite '${1:-}'; expected unit|db|characterization|e2e|lint|red|list"
    ;;
esac
