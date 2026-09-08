# Independent focused review — original role authorization manual pilot

- Verdict: **APPROVED**
- Reviewer: `/root/workforce_schedule_audit`; reviewer did not author the reviewed implementation or tests.
- Base: `b97eda0` (`Record deployed order picker browser verification`).
- Date: 2026-09-07.
- Scope: native original upload/correction authorizer, focused HTTP fixtures/tests,
  and the narrow GET/HEAD execution-page CSP classification.
- Limitation: focused manual-pilot evidence only. This is not a full Gate 3/Gate 5,
  complete regression, deployment, or production-readiness approval.

## Findings

No blocking findings.

The native original command authorizer now derives authority from the active local
pilot identity model used by the UI: active user, active assigned role, and the exact
`assignment_order.original.upload` or `assignment_order.original.correct` permission.
The query is parameterized and bounded, the accepted capability names are allowlisted,
and database failures return `UNAVAILABLE`. There is no fallback to
`fm2_process_user_capabilities`, role names, superadministrator status, or a broader
permission. Read/form admission remains separately enforced by the HTTP read seam.

The focused permission cases prove upload and correction independently, including
success after deleting every legacy explicit original capability. Inactive role,
inactive user, administrative-only user, wrong object/order, revoked session and
missing correction reason remain rejected. Rejections preserve domain rows apart
from the command owner's allowed append-only request/audit evidence. The full focused
flow still proves direct upload, replay, correction, immutable historical binding,
and absence of implicit composition application or opening.

The CSP addition applies only to successful HTML GET/HEAD execution pages and enables
their same-origin script. POST and non-HTML/error responses retain the restrictive
base policy. The fixture callback now receives the already task-owned loopback port,
which permits a true trusted-loopback golden check without granting a non-loopback
identity or changing production behavior.

## Source evidence

```text
c73e05424ea537e87cfe9a462b5530d7a0875f24da49b475c5c11b034ef6732b  app/AssignmentOrderOriginal/MariaDbAssignmentOrderOriginalEvidence.php
93fe3784a49f73c43cdf6ca95b0ebff6ce32de25259d2ce6777c15e8888af930  app/PilotHttp/PilotRouteCsp.php
834b64668faa571ab0081a70aae7a45cce79fa8920240fe9bf9c297579cabf48  tests/AssignmentOrderComposition/original_upload_http_permissions_001_test.php
9a6c90bc03e67aa6018a060f71deba7240d519b330d9513607ebf6166f9f8e50  tests/AssignmentOrderComposition/original_upload_http_flow_001_test.php
a18e1ee31d527b9e305addf9cc927428daa5ae32225fc719c07df7a1bc6bef8f  tests/AssignmentOrderComposition/original_upload_http_prefill_001_test.php
d85e3510e034b713141662449add0180f1bad55cd444a62e61accb2189f7626d  tests/Support/OriginalHttpFixture.php
c42ffbe526d5058008273a0ed0c730811b30ce6bdfc86f3e15f4c275c6263c33  tests/Support/SelectionHttpFixture.php
34e74768f500c34bbb1b903ab6d681f446c5406938cb1cd40d6425ac3d4b87dc  tests/Support/SelectionNativeFixture.php
eb7c1bc1edf982e16523e561bb8c86da9e359b6e8b443e169fb59547c0d7624f  tests/InstallationProcess/pilot_route_csp_execution_manual_test.php
```

## Verification

- `original_upload_http_permissions_001_test.php` — 9 focused cases PASS.
- `original_upload_http_flow_001_test.php` — PASS.
- `original_upload_http_prefill_001_test.php` — PASS.
- `pilot_route_csp_execution_manual_test.php` — PASS.
- PHP lint for both changed production files — PASS.
- `git diff --check` — PASS.

Root-owned role-only browser verification is separate operational evidence. This
review does not infer its outcome before the root reports the completed PDF action.
