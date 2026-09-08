# PILOT-OBJECT-READ-RBAC-FIXTURES-001 — independent integration Gate 3 v6

- Date: `2026-09-04T17:34:04+03:00`
- Reviewer: separately tasked agent `/root/object_list_integration_gate3`
- Test author: separately tasked agent `/root/object_list_integration_red`; reviewer authored none of the specification, test, fixture, production, RED evidence, or prior review
- Reviewed exact HEAD: `22ad2309a74d2f18de19d87d4a6b79b84653ff66`
- Correction baseline: `14ffbb7f06e740a183111e8a6b88d3f9b05059d1`
- Public seam: raw HTTP `GET|HEAD /pilot/objects`
- Verdict: **CHANGES_REQUESTED**

No production or test file was edited by this review. The v5 approval is not
reused for the changed test hash.

## Fresh reproduction

```text
$ date --iso-8601=seconds
2026-09-04T17:34:04+03:00

$ php -l tests/InstallationProcess/pilot_object_list_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_list_001_test.php

$ git diff --check \
    14ffbb7f06e740a183111e8a6b88d3f9b05059d1..22ad2309a74d2f18de19d87d4a6b79b84653ff66
# exit 0; no output

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_list_001_test.php
PHP Fatal error: Uncaught TestFailure: query is byte-identical and ignored
/pilot/objects?origin=migration
Expected: status 200, Content-Length 3361, canonical three-item body
Actual:   status 200, Content-Length 2439, empty-state body
... tests/InstallationProcess/pilot_object_list_001_test.php(277): assertSameValue()
exit 255
```

Both positive header controls and both inverse mismatch controls pass before
fixture setup. The public run then reaches the same intended origin-selection
RED after canonical RBAC, configured UI-shell, semantic list/facts/order,
classification/pagination checks, snapshots, non-origin query, and demo-origin
query. Cleanup completed. V5 behavior coverage remains intact.

## Correct mappings already present

- Invalid route, method and missing/malformed identity expect no
  `Retry-After`.
- Local actor/role/grant denials and committed revoke expect no header.
- The separate local-RBAC schema/read unavailable contract still expects no
  header, exact correlation header/manifest and one safe matching log event.
- Configured CSS failure, dangling/invalid imported data and 501 ceiling now
  expect exact `Retry-After: 60` as required by `PILOT-OBJECT-LIST-001` and
  inherited `PILOT-HTTP-AUTH-001`.
- `polError()` takes an explicit expected value. Positive absent/`60` controls
  and inverse unexpected/missing controls prove that neither direction is
  permissive and that mismatches fail specifically at the Retry assertion.

## Blocking finding: user-read and list-read 503 branches remain uncovered

The approved object-list contract distinguishes infrastructure failures after
CSS validation:

```text
active-user lookup failure -> 503, Retry-After: 60
list DB/query/schema failure -> 503, Retry-After: 60
```

The revised test contains no executable fault at either seam. A wrong database
password fails earlier in canonical local-RBAC lookup and is intentionally
asserted by `polUnavailable()` to have **no** Retry header under the separately
approved RBAC contract. It therefore cannot prove the later legacy active-user
lookup mapping. Dangling and invalid-value cases exercise successful list reads
followed by integrity rejection; they cannot prove a query/schema/read failure.

This leaves a regression-sensitive gap exactly where the two controlling
contracts differ. Production could return no header for legacy-user or list
infrastructure failure, while CSS/integrity/ceiling and all synthetic helper
controls remain GREEN.

Add deterministic public-seam cases that allow canonical local RBAC to succeed
and then fail only:

1. the inherited active legacy-user/role lookup; and
2. the installation-case/list read.

Each must require exact redacted `503`, `Retry-After: 60`, GET/HEAD parity as
applicable, zero mutation, and restoration/cleanup. Keep the local-RBAC
unavailable cases on their no-retry correlation contract and all denials on no
header. The fault mechanism must not make an earlier authority lookup fail and
must demonstrate which stage was reached.

## Gate decision

Gate 3 is **CHANGES_REQUESTED**. OpenSpec task `2.2` remains unchecked and Gate
4 is unauthorized. Request another fresh independent review after test-only
correction and exact-hash RED evidence.

## Exact reviewed hashes

```text
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
781ef51e6c6626d3dc94fbcbce938c74c2118b71e0ccfbd022736493c544b546  tests/InstallationProcess/pilot_object_list_001_test.php
6c7929a4a599919d25eba2330c097e03450925435b0b50a49026d08cbc1e20f9  tests/Support/PilotObjectReadRbacFixture.php
5d0cd9ac01fc22957f8ceb6fb733019aa41bf8da1acb4830774e14624f7e6d96  docs/operations/pilot-object-list-integration-red-correction-v6-2026-09-04.md
d5233f4a7f43e71cfd5eff0ce16fd3b49eff1d27471034f892021e8efcb023ac  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v5.md
71ab6211e1beb90e4af42ddcb6776b5008257d5202aec3e2a8e2bf2d4d0e921d  openspec/changes/pilot-object-read-rbac-fixtures/tasks.md

METADATA  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v6.md
```
