# Independent test review — LOCAL-RBAC-AUTH-CONTRACT-001

Date: 2026-09-02  
Reviewer: fresh agent `local_rbac_test_gate3_approval`  
Verdict: **APPROVED**

Reviewer did not author or edit spec/tests/production. After multiple correction
cycles the final review confirmed:

- exact grant, multi-role union, inactive user/activation/role, near-match and
  no legacy/client-selected fallback;
- positive real `GET /pilot/objects`, exact `objects.read` mapping, handler/read
  admission ordering and committed revoke on the next invocation;
- deterministic mid-check mutation barrier rejecting mixed RBAC snapshots;
- all three typed unavailable classifications, duplicate identity, generic
  external `503`, same opaque 12-hex correlation and exactly one safe internal
  category;
- closed v6-derived redaction oracle covering four table names, every column,
  indexes, FK constraints, information-schema catalog objects and relevant DB
  error fragments;
- healthy fixture/setup, passing route-mapping characterization and qualifying
  assertion RED at missing application seam/current legacy route admission.

Reviewed hashes match durable evidence and owner-approved spec hash. Gate 4 may
begin without changing approved tests or expectations.

## 2026-09-09 correlation-aware oracle correction pending independent review

Author: `/root/review_startup`. This addendum records test-only work and does not
self-approve it; `/root` performs the independent review.

The contract explicitly permits one opaque 12-hex correlation ID in the single
safe unavailable event. The existing whole-log numeric oracle also forbade literal
RBAC fixture IDs `7301`, `701` and `702`. A legitimate random correlation such as
`abc701defabc` therefore produced a deterministic false positive even though no
identity value leaked.

Qualifying minimal RED, before changing the oracle:

```text
$ php tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
validated opaque correlation cannot impersonate a leaked role identifier
Expected: 0
Actual: 1
exit 255
```

The correction constructs a scan surface only after validating the closed safe
category, exact 12-hex correlation and exact full event line. It replaces the
correlation field on exactly that one line with a fixed opaque marker. Every other
log byte continues through the original numeric leak regex; all existing literal,
schema identifier, email and SQL checks still scan the original whole log.

The deterministic adversarial fixture proves both directions: correlation
`abc701defabc` is accepted in the exact safe field, while separate leaked values
`actor_role_id=701`, `actor_role_id=702` and `actor_user_id=7301` remain visible and
are each required to match the forbidden-ID oracle. The same full correlation text
on an additional untrusted log line also remains visible, proving there is no
global value exemption. A second safe event, a suffix on the event line, an
unapproved category or an invalid correlation cannot be masked by the helper.

Exact candidate and focused GREEN:

```text
30b9536b6adfe3e107f3210bda6764ec7973f6f8270c108aa8dcb1f790baf381  tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php

$ php tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission

$ git diff --check
exit 0, no output
```

No production, browser, suite inventory, security contract or logging behavior was
changed.

Independent review by `/root`: **APPROVED** for the exact test hash above.
The helper requires the approved category,12-hex correlation and exactly one
complete matching event, and only the numeric-ID scan uses its result. All original
whole-log disclosure checks remain. The added adversarial assertions preserve
visibility of all three fixture IDs and the correlation string outside that
accepted field. I independently reran the real HTTP/DB route test: PASS.
The broader local `pilot_e2e_flow_001_test.php` stopped in preflight because this
worktree lacks checkout-local TCPDF `vendor/autoload.php`; it is not reported as
a local GREEN. CI supplies the pinned dependency and must exercise that parent
flow on the corrected candidate. No production code or browser assertion changed.
