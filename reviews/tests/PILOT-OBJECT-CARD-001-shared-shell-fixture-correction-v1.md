# Test review: PILOT-OBJECT-CARD-001 shared-shell fixture correction v1

- Gate: 3 — fresh independent review after fixture correction
- Reviewer: separately tasked agent `/root/object_card_fixture_gate3`
- Independence: reviewer authored neither the corrected fixture/test nor its RED evidence
- Base: `c48cbe8e8ece258afed3a82612c09c161170ad63`
- Reviewed commit: `30ab2fbe3e48db20b55d61ab1044dd280be96342`
- Reviewed evidence: `docs/operations/pilot-object-card-shared-shell-fixture-red-correction-2026-09-04.md`
- Reviewed at: `2026-09-04T04:09:27+03:00`
- Verdict: **APPROVED**

## Independent assessment

The correction is a bounded test-fixture repair. It does not change production
code or any pre-existing object-card content, authorization, transport,
adversarial, read-only, determinism, or cleanup expectation. The only new
assertion fixes the fixture's complete process-capability rows before HTTP
starts, independently proving that broad reader `19` has no process capability
while separate actor `18` alone has exact `assignment_order.prepare`.

The fixture now uses the canonical migration entrypoint against its uniquely
owned database with exact non-empty process prefix `poc_`. Local users, roles,
assignments and permissions are installed through `LocalRbacFixture` at that
same prefix; its new prefix parameter validates identifier bytes and preserves
the previous empty-prefix default for existing callers. All process fixtures,
read grants, write-denial probe and HTTP configuration consistently use the
same prefix.

Configured shared-shell setup now supplies distinct task-owned `shlz.css` and
`pilot.css` files and protects both in the filesystem guard. The SELECT-only
HTTP principal receives only the explicitly enumerated current columns/tables
required by local identity, migration provenance, capability readiness and the
card projection. It receives no write grant, and no legacy authorization
fallback was introduced.

The intended failure progression is reproduced independently. At the base,
the public card request stops at setup with `Expected: 200 / Actual: 503`. At
the reviewed commit, the same command reaches the existing presentation oracle
and fails only because the current production CSP additionally contains
`script-src 'self'`. This correction neither changes nor reclassifies that CSP
expectation. It therefore restores a qualifying RED without claiming the
navigation slice or object-card regression GREEN.

Cleanup remains attempt-safe in the existing `finally`: server processes are
stopped, the unique database and both SQL users are dropped, and the task-owned
artifact root is removed. Independent post-run inspection found no artifact
entries or matching test/server processes.

No blocking traceability, seam, sensitivity, expected-value independence,
authorization separation, determinism, isolation, cleanup, or scope finding
remains for this fixture correction.

Non-blocking maintainability observations: `LocalRbacFixture::tables()` trusts
its test-owned prefix while `install()` validates the same parameter; the exact
reviewed literal is safe, but a later cleanup could centralize that validation.
The test also retains an unused `ProductionProcessSchemaMigration` import, and
the bounded migration helper drains stdout before stderr (the current canonical
migration output is too small for the theoretical pipe-buffer deadlock). None
changes the reviewed seam, result, or isolation.

## Verification evidence

```text
$ git diff --check c48cbe8...30ab2fbe3e48db20b55d61ab1044dd280be96342
PASS (no output)

$ php -l tests/InstallationProcess/pilot_object_card_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_card_001_test.php

$ php -l tests/Support/LocalRbacFixture.php
No syntax errors detected in tests/Support/LocalRbacFixture.php

$ openspec validate remove-pilot-work-navigation-item --strict
Change 'remove-pilot-work-navigation-item' is valid

$ # at c48cbe8
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_object_card_001_test.php
Expected: 200
Actual: 503
exit 255

$ # at 30ab2fbe3e48db20b55d61ab1044dd280be96342
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_object_card_001_test.php
Example A broad reader without capability content-security-policy
Expected: default-src 'none'; style-src 'self'; img-src 'self'; font-src 'self'; ...
Actual: default-src 'none'; style-src 'self'; script-src 'self'; img-src 'self'; font-src 'self'; ...
exit 255

$ find .test-artifacts -mindepth 1 -maxdepth 2 -print
PASS (no output)

$ pgrep -af 'pilot_object_card_001|php -S 127.0.0.1'
PASS (no matching test/server process; inspection command itself excluded)
```

## Reviewed hashes

```text
64a08f284ff5133b36cc55bb47365b78460a4340fee55bd980020405165051a3  tests/InstallationProcess/pilot_object_card_001_test.php
ca82fca35d722c9c626eb6eb69e1aa11e7958730bc99e67541aebb610dbf5952  tests/Support/LocalRbacFixture.php
c7c38d67f4275413f0ce9cb4c2b031f0cb634095a7ca7fc85d1f064c67010509  docs/operations/pilot-object-card-shared-shell-fixture-red-correction-2026-09-04.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
b54412b14ca3d3e8ad63fc629d3dda7e5902209c52a1b2acd92dade5ba053531  specs/PILOT-UI-SHELL-001.md
f13c27c2ee0d706954f5eee081bb717612abeac5e0386f0881a875c229bc1392  specs/LOCAL-RBAC-AUTH-CONTRACT-001.md
888bfabec7f079c9a5bc21ebf1093cded10c08dde131e6169fd9f37b24225504  openspec/changes/remove-pilot-work-navigation-item/specs/ui/pilot-work-navigation-item-removal/spec.md
```

This approval covers only the exact fixture correction above. The existing CSP
presentation mismatch remains RED, and the broader navigation Gate 3 remains
open until its full approved route-wiring evidence is satisfied.
