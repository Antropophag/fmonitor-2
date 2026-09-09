# Privacy-safe usage aggregation

Run the aggregate over a local JSONL file or directory:

```sh
python3 tools/usage/aggregate.py --input "$HOME/.codex/sessions" --pretty
```

The command reads input once and writes JSON only to stdout. It never emits log
paths, session/turn/response IDs, prompts, messages, tool arguments, or content.
Redirect stdout only when an aggregate artifact is intended. Do not copy source
JSONL into the repository.

`model_calls_basis=response_ids` counts unique responses present in the log; it
does not independently prove provider requests. Legacy
`legacy_distinct_snapshots` is an estimate from changing cumulative counters.
Counter resets and invalid usage records mark affected aggregates incomplete.
Reasoning tokens are part of output tokens. Durations can overlap and can include
waiting. The logs provide no verified tariff or billing breakdown, so this tool
does not calculate money or quota.

## Reproducing the bounded #78 checkpoint

The checked-in baseline is a prefix of one private local session, ending at the
unique legacy cumulative snapshot with this aggregate fingerprint:

```text
input=62781751 cached_input=62058240 output=125468 reasoning_output=68047
checkpoint_utc=2026-09-09T16:11:59.156Z
```

This command structurally selects exactly one matching log, writes its prefix to
a mode-0600 temporary file, invokes the public aggregator, and removes the prefix
on exit. It prints no source path or identifier:

```sh
python3 - "$HOME/.codex/sessions" <<'PY'
import json
from pathlib import Path
import subprocess
import sys
import tempfile

source = Path(sys.argv[1])
target = (62781751, 62058240, 125468, 68047)
timestamp = "2026-09-09T16:11:59.156Z"
matches = []
for path in source.rglob("*.jsonl"):
    try:
        with path.open(encoding="utf-8", errors="replace") as stream:
          while line := stream.readline():
            try:
                row = json.loads(line)
            except json.JSONDecodeError:
                continue
            payload = row.get("payload", {}) if isinstance(row, dict) else {}
            info = payload.get("info", {}) if isinstance(payload, dict) else {}
            usage = info.get("total_token_usage", {}) if isinstance(info, dict) else {}
            actual = tuple(usage.get(key) for key in (
                "input_tokens", "cached_input_tokens",
                "output_tokens", "reasoning_output_tokens",
            )) if isinstance(usage, dict) else ()
            if row.get("timestamp") == timestamp and actual == target:
                matches.append((path, stream.tell()))
                break
    except OSError:
        continue
if len(matches) != 1:
    raise SystemExit("baseline reproduction failed: expected one private checkpoint")
path, length = matches[0]
with tempfile.TemporaryDirectory(prefix="fm2-usage-78-") as temporary:
    destination = Path(temporary) / "prefix.jsonl"
    with path.open("rb") as source_stream, destination.open("wb") as output:
        output.write(source_stream.read(length))
    destination.chmod(0o600)
    completed = subprocess.run(
        [sys.executable, "tools/usage/aggregate.py", "--input", str(destination), "--pretty"],
        check=False,
    )
    raise SystemExit(completed.returncode)
PY
```

The cumulative tuple is only the private selector. The selected prefix contains
modern records, so its 261 calls use `model_calls_basis=response_ids`. The fixture
test gives the automated proof of the aggregation rules:

```sh
python3 tests/Usage/usage_aggregation_001_test.py
```
