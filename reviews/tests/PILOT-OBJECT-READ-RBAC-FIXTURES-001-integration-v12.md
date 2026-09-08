# PILOT-OBJECT-READ-RBAC-FIXTURES-001 — independent integration Gate 3 v12

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/object_list_css_gate3`
- Test author: a different separately tasked agent; this reviewer authored none of the reviewed tests, fixtures, specification, production, or RED evidence
- Reviewed exact test-only correction: `d76c16d866f442e804119484d0b0e87f6dc78258`
- Correction baseline: `75a642476224abe9ec99905777164b4279e743a7`
- Previously reviewed integration test head: `513d00debbf547e74bb35be9656627405fc913e0`
- Prior Gate 3 v11 approval commit: `8f531a86726d4ddfb60382c5e829069104c21a90`
- Public seam: raw HTTP `GET|HEAD /pilot/objects`
- Verdict: **APPROVED**

This approval is limited to the exact hashes below. It approves the corrected
predecessor fixture and retains the unchanged v11 integration RED for minimal
Gate 4. It is not production approval, Gate 5, a fresh runtime execution,
repository-wide GREEN, CI readiness, or release evidence. The reviewer changed
no test or production byte.

## Correction review

The correction closes the stale positive-fixture defect without weakening the
authorization oracle:

- `local_rbac_objects_route_admission_001_test.php` now reads the public
  `../shlz-ui/packages/styles/shlz.css` export and copies its complete bytes to
  a unique task-owned regular file whose exact basename is `shlz.css`.
- The fixture rejects unreadable/copy-short setup and independently checks the
  canonical file identity and SHA-256 equality. It does not substitute the
  former wrong-basename `rapid-pilot/pilot.css` adapter.
- Cleanup is registered immediately after task-owned root creation, before the
  public CSS read or any database setup. `TaskOwnedArtifactRoot` confines
  recursive cleanup to the caller/token-owned canonical directory under the
  repository `.test-artifacts` boundary and rejects symlink/ownership/mode
  violations.
- Only the positive/read-capable `$base` composition receives the valid CSS.
  `$authOnly` retains SELECT solely on the four authorization tables and now
  receives the deliberately absent `unavailable/shlz.css`. Therefore inactive
  role/user, near-match grant, missing actor, wrong actor, hostile client input,
  and legacy `REMOTE_USER` cases can return their required 401/403 only before
  both downstream CSS and business-object reads.
- The exact local actor ID, active local-role union, case-sensitive exact
  `objects.read`, and per-request committed revoke checks are unchanged. The
  successful real-handler assertion still defeats an always-deny implementation
  and continues to require the canonical object projection.

The diff from the correction baseline contains only this test correction and
its append-only Gate 2 evidence. No production byte changed.

## Retained integration RED and sensitivity

The normative object-list test, fixture, specifications, and v11 RED evidence
are byte-identical to the previously approved/reproduced inputs. In particular:

- healthy local authorization plus missing configured CSS still expects exact
  redacted `503`, `Retry-After: 60`, GET/HEAD parity, no partial body, and no
  object-state mutation; the captured v11 result fails specifically with
  `Expected: 503`, `Actual: 200` at line 298;
- all query variants, including `?origin=migration`, must be byte-equivalent to
  the queryless representation, so a later query-sensitive implementation is
  observable after the CSS predecessor is fixed;
- exactly 500 canonical objects must return the complete unpaginated list,
  while 501 must fail closed with redacted `503` and `Retry-After: 60`;
- helper controls independently reject absent/unexpected/wrong retry headers,
  and the existing representation, classification/pagination exclusion,
  local-display-identity, revoke/repeat, read-fault, and cleanup assertions are
  unchanged.

The change therefore repairs setup validity; it does not derive expected values
from production, relax authorization ordering, or make the intended production
gap pass.

## Verification available on this executor

```text
$ test -f ../shlz-ui/packages/styles/shlz.css && stat ...
../shlz-ui/packages/styles/shlz.css Regular File 1538

$ git diff --check d76c16d^..d76c16d
# exit 0; empty output

$ git diff --name-status d76c16d^..d76c16d
A docs/operations/pilot-object-list-predecessor-css-gate2-restart-2026-09-04.md
M tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php

$ command -v php
# unavailable

$ command -v docker
# unavailable
```

No PHP/MariaDB runtime exists on this executor, so this review does not claim a
fresh execution. The qualifying integration RED remains the exact, unchanged
test and captured execution reviewed in v11. Static inspection of the only
changed predecessor fixture establishes that its former `pilot.css` basename
defect is removed and that denied probes retain inaccessible downstreams.

## Gate decision

**APPROVED.** Minimal Gate 4 may restore configured CSS validation strictly
after successful local authorization, then implement the already reviewed query
ignorance and complete-list ceiling behavior. Any further test, fixture,
specification, or integration-composition byte change requires another fresh
independent Gate 3 review.

## Exact reviewed-input hashes

```text
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
f13c27c2ee0d706954f5eee081bb717612abeac5e0386f0881a875c229bc1392  specs/LOCAL-RBAC-AUTH-CONTRACT-001.md
6c7929a4a599919d25eba2330c097e03450925435b0b50a49026d08cbc1e20f9  tests/Support/PilotObjectReadRbacFixture.php
d6f6731715e4007126541caab75ed6e4099f0192d12783090b0921a3bc0c68da  tests/Support/TaskOwnedArtifactRoot.php
9151e5b82c6d89122103d381e648c807632bc92dbfb5da52f30acaf8f5676562  tests/InstallationProcess/pilot_object_list_001_test.php
5e29a959970faa7b7e8220dc4e114560f3f665e3faca0ab308dec6eac83b4914  tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
0874d3cbfef054289c6cfcf7b825ce89c93668ef8ecb6d61ae586ef2904fd83b  docs/operations/pilot-object-list-predecessor-css-gate2-restart-2026-09-04.md
54508b7e5d578acbd10de080edfe0a6dffa11b77c7e7ce515f6989e5dd1fdb23  docs/operations/pilot-object-list-integration-red-correction-v11-2026-09-04.md
19e7575c1015ed987c4c8d4b197a02e3c1bcefe3668826914a2dc7409dcbb401  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v11.md
6378680b75d7d42afe64f637542a3602c57d9ac9e6f7f2acb84cb3c9ceac8b91  ../shlz-ui/packages/styles/shlz.css

METADATA  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v12.md
```
