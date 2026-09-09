# Independent Gate 5 review — INITIAL-OWNER-PROVISIONING-001

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/runtime_review`
- Implementation author: separately tasked agent `/root/runtime_plan`
- Verdict: **APPROVED**

## Exact reviewed identities

```text
52f78825d52a19fa30e9ef528be453632f83f5b577d4f19733459929f5bf736a  specs/INITIAL-OWNER-PROVISIONING-001.md
e084133a84c7a02521a1aac41fd5d4ca2835ba4f7d1634977c0ecd7a0a866ae2  tests/Runtime/initial_owner_provisioning_001_test.php
92d83d580c215f2edf48744ed810e7d9bee70efa921659456d8dc5a78bed7ca1  app/IdentityAccess/InitialOwnerProvisioningResult.php
2fd20978b92a908e5d1099f8a651541df99e23608e204acd8ec194fc87f90d89  app/IdentityAccess/LocalRoleCatalog.php
ffde4cfc63fd34f5e444095e87926e12d3149a0f94942c93a16bf8b215198886  app/IdentityAccess/MariaDbInitialOwnerProvisioning.php
3cb7d786f3ab07d962641c91f0e5c9667f1edb61fe2b84aea1e87762c08d3a92  app/RapidPilot/LocalRoleCatalog.php
5addddd6a75312defeaa1b347ad9c400643c5a3fd7611c4c9b938bf137b78551  bin/fmonitor2-provision-initial-admin.php
```

## Review

IdentityAccess owns one narrow clean-only operation. It validates schema before
locking, serializes by database/prefix, and starts one transaction. Clean means all
nine identity tables are empty; partial catalog state therefore returns the stable
identity-not-empty outcome without attempted repair. Role catalog, user, Argon2id
credential and exactly two bootstrap grants commit atomically.

Replay requires the normalized user/full-name, empty phone, session version one,
active state, matching Argon2id credential, exact grants/catalog and empty auxiliary
invitation/role/auth/status history. All other existing states return conflict with
no write. Trigger injection proves rollback after late writes and lock release permits
a clean retry. Concurrent real CLIs produce exactly one user and either busy or exact
replay for the second call.

The CLI uses direct explicit DB configuration and stable secret-free JSON exits. It
contains no migration, demo, HTTP or external-send path and succeeds under a
SELECT/INSERT/UPDATE/DELETE-only principal. The prior RapidPilot catalog is now a
thin compatibility forwarder to the IdentityAccess policy, avoiding duplicate role
definitions.

## Verification

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/Runtime/initial_owner_provisioning_001_test.php
PASS: INITIAL-OWNER-PROVISIONING-001 explicit clean owner CLI

# Author evidence
identity_access_runtime_ddl_001_test.php PASS
verify-auth-hot-path.php PASS
pilot_http_auth_001_global_calls_test.php PASS
architecture-check PASS (7 rules)
openspec validate provision-initial-owner-admin --strict PASS
git diff --check PASS
```

## Verdict

**APPROVED.** The implementation provides explicit, atomic, DML-only initial owner
provisioning without promotion, repair, startup bootstrap or secret disclosure.
Full CI and integration into the post-#33 operations branch remain subsequent gates.

## Operational rereview

The canonical root README now directs production operators to one runbook and marks
the old `make up`/8092/demo/Bitrix flow as historical. The reviewed runbook gives an
executable clean-checkout/private-environment sequence through image identity, DB
account, prepare, migrate, readiness, initial owner, start, secret removal, backup,
restart, update and rollback.

The DML account probe was corrected to construct MariaDB's exact `'user'@'host'`
grantee identity. It requires global privileges to be exactly `USAGE`, target-schema
privileges to be exactly `SELECT/INSERT/UPDATE/DELETE`, and table/column grants to be
empty. A real MariaDB sensitivity test accepts the exact account and rejects schema
CREATE, TRIGGER, CREATE VIEW, global SELECT, and a column SELECT on another database.

```text
611641d37210695034d2d8d6e4bf35286c28c457326505fa07517e289648e61b  docs/operations/production-runtime-runbook.md
9bee5fb5aed432bd17e1b3704b0501bb6df5464e6f0641d731cbc2554d4109d5  tests/Runtime/runtime_dml_privilege_probe_001_test.php

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/Runtime/runtime_dml_privilege_probe_001_test.php
PASS: production runbook exact DML privilege probe sensitivity
```

The isolated exercised contour proved owner login 303, health and restart using
private mode-0600 evidence; its containers and volumes were removed. The evidence
source was the pre-merge #33 commit plus the recorded #27 diff, so authoritative CI
must run after rebasing #27 onto merged #33 before integration.

**Operational verdict: APPROVED.** No documentation or least-privilege blocker
remains.

## Integrated rereview on merged #33

The dirty #27 work was preserved while its worktree advanced to merged #33 base
`eceebdbb`. The new provisioning and DML-probe tests are registered alongside the
complete #33 Runtime inventory. The first integrated review caught their omission
from the protected historical DB-membership subtraction list; the correction adds
only those two explicit paths:

```text
1c8d4f998492f9f2f893955d01dd0cff588dc50ffe8456ae0be910273b97085a  tests/Verification/verification_inventory_001_test.py
```

Integrated verification:

```text
verification_inventory_001_test.py: 15/15 PASS
verification_ci_001_test.py: 15/15 PASS
quality_graph_ci_setup_001_test.php: PASS
initial_owner_provisioning_001_test.php: PASS
runtime_dml_privilege_probe_001_test.php: PASS
git diff --check: PASS
```

**Integrated verdict: APPROVED.** #27 preserves the merged #33 verification and
governance topology. Authoritative branch CI remains required after commit.
