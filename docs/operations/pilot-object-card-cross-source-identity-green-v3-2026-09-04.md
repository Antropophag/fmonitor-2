# PILOT-OBJECT-CARD-001 cross-source identity GREEN v3 — 2026-09-04

## Fresh Gate 4 composition

- Reviewed Gate 2 head: `91534b2f72c71c221a987962ff7d726dab9d8ca9`.
- Independent Gate 3/head: `72f55e355a2b1ad577aff95a9c5a63e83482ee92`.
- Review: `reviews/tests/PILOT-OBJECT-CARD-001-cross-source-identity-v7.md`.
- Verdict: `APPROVED`.
- Production candidate: `f887a636b9799df7967f48dfd6d9876be17c0c83`.
- Tests, specifications and reviews changed during Gate 4: none.

This append-only record supersedes earlier object-card GREEN records only for
the changed cross-source integration composition.

The HTTP identity directory now delegates exact legacy identity resolution to
the existing MariaDB process-user directory. Its narrow parameterized read
requires one exact active legacy user and one active legacy role. Card success
does not read or depend on a local actor row, local status, local role,
permission or process capability. The identity-only SQL principal can decide
the denied legacy case without unrelated local columns, permission tables or
object reads.

After successful identity admission, the existing card composition separately
queries only `assignment_order.prepare` to decide whether the sole upload-first
link is present. It does not use that capability to authorize card reading.
Configured/shared and compatibility shell selection remains configuration-only.
All approved five-group content, newest-three event limit, no-artifact-read
boundary, state/PTO action negatives, CSP and ordered scripts remain unchanged.

## GREEN evidence

At `2026-09-04T12:45:31+03:00`, on exact production SHA
`f887a636b9799df7967f48dfd6d9876be17c0c83`:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_object_card_001_test.php
PASS: PILOT-OBJECT-CARD-001 public HTTP card

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/local_rbac_auth_contract_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 public seam contract

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_route_csp_001_test.php
pilot_route_csp_001_test: PASS

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

$ make lint
exit 0

$ openspec validate remove-pilot-work-navigation-item --strict
Change 'remove-pilot-work-navigation-item' is valid

$ git diff --check
PASS (no output)
```

Exact bytes:

```text
3d0d01da364cb9575793bf43e15389371ac73ffb633b34b79963c1191e2d065d  tests/InstallationProcess/pilot_object_card_001_test.php
3d9a8388de31ba9d52f092d1f4d78914f0839e1a8a1bf491a6ccc3a3713c673a  reviews/tests/PILOT-OBJECT-CARD-001-cross-source-identity-v7.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
be0e8dede13a68086bbcbc42bf8944c8b5cca721b40039641c3b16572d774768  app/InstallationProcess/MariaDbProcessUserDirectory.php
19e5703061b69d5379a62c0086ab17b0b264fc72902c5c22dc15fd4e84fc228c  app/PilotHttp/PilotHttp.php
```

No repository-wide `VERIFY_OK`, navigation GREEN, integration completion or
Gate 5 approval is claimed.
