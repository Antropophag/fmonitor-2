# PILOT-PREPARE-FORM-001 — picker warning redaction GREEN

- Date: `2026-09-04`
- Scope: missing bundled `/pilot/assets/picker.js`
- Production change: one warning-suppressed, fully-qualified read call

The approved prepare verifier copied the production app, removed only its
task-owned picker source and required the inherited exact redacted 503. PHP 8.5
emitted the native `file_get_contents` warning into the response before the
existing catch returned `Service unavailable.\n`.

The minimal correction uses `@\file_get_contents` at that asset read. Return
`false` still becomes `PilotHttpInfrastructureUnavailable` and the existing
route-local 503 + `Retry-After: 60`; no success, routing or authorization
behavior changes.

```text
No syntax errors detected in app/PilotHttp/PilotHttp.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form
ARCHITECTURE CHECK PASSED (7 rules)
lint: exit 0
git diff --check: exit 0
```

Independent Gate 5 rereview remains required because production changed after
the prior prepare approval.
