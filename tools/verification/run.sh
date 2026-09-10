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

# Explicit inventory: validate everything before printing a list or executing tests.
load_inventory() {
  local directory line group runtime file extra key
  local seen=$'\n' registered=$'\n'
  local catalog=tools/verification/suites.tsv
  for directory in tests/InstallationProcess tests/AssignmentOrderComposition tests/Verification tests/Otiz tests/Runtime tests/Jobs; do
    test -d "$directory" || fail SETUP_FAILURE "missing verification directory: $directory"
  done
  test -f "$catalog" || fail SETUP_FAILURE "missing verification catalog: $catalog"
  while IFS= read -r line || [[ -n "$line" ]]; do
    [[ -z "$line" || "$line" == \#* ]] && continue
    IFS=$'\t' read -r group runtime file extra <<< "$line"
    [[ "$line" == "$group"$'\t'"$runtime"$'\t'"$file" ]] \
      || fail SETUP_FAILURE "invalid catalog row: $line"
    case "$group" in unit|db|characterization|e2e) ;; *) fail SETUP_FAILURE "unknown catalog suite: $group" ;; esac
    case "$runtime" in php|node|python3) ;; *) fail SETUP_FAILURE "unknown catalog runtime: $runtime" ;; esac
    [[ "$file" =~ ^(tests|rapid-pilot)/[a-zA-Z0-9_./-]+$ && "$file" != *..* ]] \
      || fail SETUP_FAILURE "invalid catalog path: $file"
    test -f "$file" || fail SETUP_FAILURE "missing catalog file: $file"
    key="$group"$'\t'"$file"
    [[ "$seen" != *$'\n'"$key"$'\n'* ]] || fail SETUP_FAILURE "duplicate catalog member: $key"
    seen+="$key"$'\n'
    registered+="$file"$'\n'
    if [[ "$group" == "$suite" ]]; then
      selected_runtimes+=("$runtime")
      selected_files+=("$file")
    fi
  done < "$catalog"
  for file in tests/InstallationProcess/*test.php tests/AssignmentOrderComposition/*test.php tests/Verification/*_test.mjs tests/Otiz/*test.php tests/Runtime/*test.php tests/Jobs/*test.php; do
    test -f "$file" || continue
    [[ "$registered" == *$'\n'"$file"$'\n'* ]] \
      || fail SETUP_FAILURE "unregistered verifier: $file; add it to $catalog"
  done
}

run_selected() {
  local index runtime file started status failures=0 summary harness_python outcome
  harness_python="${FMONITOR_HARNESS_PYTHON:-python3}"
  for ((index=0; index<${#selected_files[@]}; index++)); do
    runtime="${selected_runtimes[$index]}"
    file="${selected_files[$index]}"
    printf 'VERIFY %s\n' "$file"
    started=$SECONDS
    summary="$("$harness_python" tools/delivery/harness.py run -- "$runtime" "$file")"
    status=$?
    outcome="$($harness_python -c 'import json,sys;print(json.loads(sys.argv[1])["outcome"])' "$summary")"
    [[ "$outcome" == GREEN || "$status" -ne 0 ]] || status=1
    "$harness_python" -c 'import json,os,sys
d=json.loads(sys.argv[1]); r=json.load(open(d["record_path"]));
assert r["id"]==d["id"] and r["outcome"]==d["outcome"]
if os.environ.get("GITHUB_ACTIONS")=="true":
    sys.stdout.write(open(r["stdout_path"],errors="replace").read())
    sys.stderr.write(open(r["stderr_path"],errors="replace").read())
else:
    print(sys.argv[1])
    if r["outcome"]!="GREEN": print(d.get("excerpt", ""), end="" if d.get("excerpt", "").endswith("\n") else "\n")' "$summary"
    printf 'VERIFY_TIMING suite=%s runtime=%s file=%s seconds=%s exit=%s\n' \
      "$suite" "$runtime" "$file" "$((SECONDS - started))" "$status"
    if ((status != 0)); then
      printf 'REGRESSION_FAILURE: %s\n' "$file" >&2
      failures=$((failures + 1))
      # Retain the existing characterization Python prerequisite fail-fast.
      if [[ "$suite" == characterization && "$runtime" == python3 ]]; then
        fail REGRESSION_FAILURE "$file"
      fi
    fi
  done
  ((failures == 0)) || fail REGRESSION_FAILURE "$failures verifier(s) failed"
  return 0
}

if [[ "${1:-}" == category ]]; then
  [[ "$#" -eq 2 || "$#" -eq 4 ]] || fail SETUP_FAILURE "usage: run.sh category CATEGORY [--shard 1/2|2/2]"
  shift
  exec python3 tools/verification/ci.py run "$@"
fi

suite="${1:-}"
if [[ "$suite" == list ]]; then
  test "$#" -eq 2 || fail SETUP_FAILURE "usage: tools/verification/run.sh list unit|db|characterization|e2e"
  suite="$2"
  case "$suite" in unit|db|characterization|e2e) ;; *) fail SETUP_FAILURE "unknown list suite: $suite" ;; esac
fi
selected_runtimes=()
selected_files=()
load_inventory

if [[ "${1:-}" == list ]]; then
  for ((index=0; index<${#selected_files[@]}; index++)); do
    printf '%s\t%s\n' "${selected_runtimes[$index]}" "${selected_files[$index]}"
  done
  exit 0
fi

case "$suite" in
  unit|characterization)
    run_selected
    ;;
  db|e2e)
    require_db
    run_selected
    ;;
  lint)
    while IFS= read -r file; do php -l "$file" >/dev/null || fail REGRESSION_FAILURE "$file syntax"; done \
      < <(find app bin public rapid-pilot tests tools -type f -name '*.php' -print | sort)
    ;;
  red)
    test -n "${2:-}" || fail SETUP_FAILURE "usage: tools/verification/run.sh red <test-file> [expected-marker]"
    marker="${3:-RED_ASSERTION}"
    harness_python="${FMONITOR_HARNESS_PYTHON:-python3}"
    summary="$("$harness_python" tools/delivery/harness.py run --intended-red "$marker" -- php "$2")"
    status=$?
    outcome="$($harness_python -c 'import json,sys;print(json.loads(sys.argv[1])["outcome"])' "$summary")"
    if [[ "$outcome" != INTENDED_RED ]]; then
      [[ "$status" -ne 0 ]] || fail RED_ASSERTION "expected failure but $2 passed"
      fail REGRESSION_FAILURE "$2 did not produce intended RED marker $marker (outcome $outcome)"
    fi
    if [[ "$status" -eq 0 ]]; then
      fail RED_ASSERTION "expected failure but $2 passed"
    fi
    printf 'RED_ASSERTION: expected failing behavior observed in %s\n' "$2"
    ;;
  *)
    fail SETUP_FAILURE "unknown suite '${1:-}'; expected unit|db|characterization|e2e|lint|red|list"
    ;;
esac
