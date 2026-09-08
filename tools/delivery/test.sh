#!/usr/bin/env bash
set -uo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."
failed=0
for file in tests/Verification/quality_graph_*_test.php; do
  printf 'VERIFY %s\n' "$file"
  # Fixture repositories have their own Git HEAD; production CI identity belongs
  # to the separate delivery-evidence node, not these isolated simulations.
  env -u GITHUB_ACTIONS -u GITHUB_SHA php "$file" || failed=1
done
python_binary="${FMONITOR_QG_PYTHON:-.venv/bin/python}"
if [[ ! -x "$python_binary" ]]; then
  printf 'SETUP_FAILURE: run uv sync --frozen for governance tests\n' >&2
  failed=1
else
  "$python_binary" tests/Verification/quality_graph_publisher_provenance_001_test.py || failed=1
fi
exit "$failed"
