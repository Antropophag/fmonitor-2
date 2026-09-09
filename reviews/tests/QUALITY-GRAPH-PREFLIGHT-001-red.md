# QUALITY-GRAPH-PREFLIGHT-001 — RED evidence

- Status: **RED — NO APPROVAL RECORDED**
- Date: 2026-09-09
- Specification: `specs/QUALITY-GRAPH-CURRENT-CI-001.md`
- Specification SHA-256: `e140f33bfd844891d099b59f84ec70c58dd0b68bd90a835d8420464951d56b92`
- Test: `tests/Verification/quality_graph_preflight_001_test.py`
- Test SHA-256: `2f5439b6ac95d63932f792c828ea7631768b00e034d784085efd474100bd026b`

## Executable boundary

The test invokes only the public command
`python3 tools/delivery/quality-graph-preflight.py`. Every case supplies a real
`GITHUB_EVENT_PATH` and a disposable localhost HTTP server implementing the bounded
GitHub run/artifacts GET surface. The fixture records request method, path and bearer
header and rejects writes. It does not mock an internal preflight function.

The matrix covers a complete current seven-node set for successful, failed and cancelled
completed runs, retained older plus complete current artifacts, missing current node,
stale-only and future attempts, duplicate
current and duplicate historical node/attempt, unknown node, invalid QG artifact name,
expired current artifact,
two-page enumeration, HTTP failure, malformed JSON, non-terminating pagination, stale
event attempt, non-completed event, and API run/head/repository mismatches. Success cases require the exact
terminal `QUALITY_GRAPH_PREFLIGHT_OK nodes=7`; rejected cases require nonzero plus
`QUALITY_GRAPH_PREFLIGHT_FAILURE`. Thus absence of the CLI cannot make negative cases
pass accidentally.

## Intended RED

Command:

```text
python3 tests/Verification/quality_graph_preflight_001_test.py
```

Observed exit: `1`.

Observed failing assertion:

```text
RED_ASSERTION: current full seven should pass current admission; got 2:
python3: can't open file '.../tools/delivery/quality-graph-preflight.py':
[Errno 2] No such file or directory
```

The disposable server started and stopped normally. The first valid case fails because
the specified public CLI does not exist, which is the intended missing-behavior RED.
`python3 -m py_compile tests/Verification/quality_graph_preflight_001_test.py` and
`git diff --check` both exit zero.

## Review boundary

This record preserves RED evidence only. It is not Gate 3 approval, implementation
authorization, publisher parity, GitHub permission evidence, or completion of #25.
