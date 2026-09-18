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

## Integrated CI environment addendum — 2026-09-09

GitHub Actions run `34299969447` had one implementation-owned failure in the
complete inventory recorded outside the repository at
`/tmp/pr63-53ef6828-failure-inventory.log`: integration shard 2 expected the
missing-configuration outcome `64`, but an inherited `FMONITOR_DB_PASSWORD`
changed it to `70`. The failure was independently reproduced with
`FMONITOR_DB_PASSWORD=SYNTHETIC_PARENT_POISON` as recorded in
`/tmp/pr63-parent-env-repro.log`.

Reviewed test SHA-256:

```text
9816548239b6e6a64d27e31e66410f782f90942cb4f3ffa9116717fd2c512a1c  tests/Runtime/initial_owner_provisioning_001_test.php
```

The test now removes inherited CLI-owned `FMONITOR_DB_*`, process-prefix and
bootstrap-password variables before applying each explicit child environment.
Both ordinary and concurrent child paths use that boundary. A poisoning
regression proves that an intentionally omitted DB password still returns exact
`CONFIGURATION_INVALID`/64, and `finally` restores the parent environment exactly.
The independent focused test is GREEN. Production source is unchanged.

**Verdict: APPROVED.** This is a test-isolation correction for the integrated CI
environment and does not alter the previously reviewed provisioning behavior.

---

## Independent Gate 5 review — explicit local continuation (#185) — 2026-09-18

- Reviewer: independently tasked agent `/root/issue185_gate5`; author of none of
  the reviewed specification, tests, production implementation, or Gate 3 review.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T232001Z-428549e1e4/package.json`.
- Base: `e419c2b5d1e4d6c9e46edda1adf7abac6883447a`.
- Candidate source: `24e498a1d99cce3fb4b05453a1405041025e02b59523ef20a6d2f618c20e106b`;
  executable source: `5a157b1487fe093ac10fbb232f84284343c794d0d5ddb01818a90da04e7b0815`.
- Contract: `specs/INITIAL-OWNER-PROVISIONING-001.md`
  (`6f59acf9dadd30d2ddac31fb38aaa21917fb0a1af0db915ea9f667463c20876d`).
- Snapshot patch: `b712e3c70e1b55fdd619a1ad08f2f15e94b4a17a86393dd737ecdd9003b914e8`.
- Planner lane: `CRITICAL`; Gate 3 and final review required.
- Verdict: **APPROVED**.

### Findings

No blocking or non-blocking code findings.

### Assessment

The implementation conforms to A1–A5. `make up` alone opts into the explicit
local-resume CLI after prepare/migrate, while the unflagged direct CLI and checked
production/web/migration entry points retain strict clean-create/exact-replay
semantics. The application operation remains in IdentityAccess and introduces no
second persistence owner, migration, import, external send, or excluded #185
scope.

Local continuation validates normalized expected identity, global uniqueness of
the bootstrap owner candidate, active user and mandatory role definitions, exact
bootstrap/null-actor provenance for both required grants, exactly one credential,
and the four required permissions. Foreign, absent, ambiguous, blocked,
credential-less, provenance-corrupt, inactive-role, missing-grant, and
insufficient-permission states fail closed. It neither compares the supplied
password nor seeds, repairs, promotes, restores, or otherwise changes identity
facts.

The operation uses the existing database/prefix advisory-lock key and a MariaDB
`READ ONLY` transaction. All success and rejection paths commit or roll back and
the outer `finally` releases the lock. The focused runtime verifier exercises a
DML-only principal, held-lock behavior, complete concurrent process triples,
strict production isolation, all complementary provenance predicates, developed
owner tolerance, and full pre/post equality across the nine identity tables. The
Make verifier is execution-sensitive through `make --dry-run up`, checks ordering
and exact opt-in, and checks the named production routes negatively. The small
owner methods and shared required-permission constant are maintainable and do not
duplicate policy outside the owning module.

All four supplied GREEN records are bound to the candidate and executable source
above with exit 0 and retained transcripts: route ownership
`1789686919867737000-7308c4821da14394bd96e0b81426450f`, runtime lifecycle
`1789686924237194000-e26c07101f884ca6ae1d2dc87ea7b210`, governance
`1789687144215276000-148afc90565e421e8e0ea2bd64f8a5c6`, and architecture
`1789687170722786000-d27708d9a1c544b3aea2de15c9bb538c`. Their command blobs
match the reviewed test files where applicable. The complete Gate 3 history was
reviewed: two requested-correction rounds were closed by the approved narrow
rereview, and the later two-line root fixture/oracle correction received an
independent supplemental approval. Authorship separation and scope exclusions are
recorded consistently.

Exact-source CI, PR/head, deployment, and enforcement remain `UNKNOWN`/pending and
are not represented as GREEN. This approval authorizes continuation to the
required exact-source CI and PR-ready work only; it does not authorize merge or
deploy.

---

## Independent Gate 5 correction review — first local startup in PR #189 — 2026-09-18

- Reviewer: independently tasked agent `/root/issue185_blocker_gate5`; author of
  none of the corrected specification, test, production implementation, or Gate 3
  correction review.
- Package:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T001430Z-6a69522698/package.json`
  (`d205d428feafd2431f0cc025e17f3e69e52aedbd0c6d4432cb164044d4c57828`).
- Base: `e419c2b5d1e4d6c9e46edda1adf7abac6883447a`.
- Candidate source:
  `1ba97c1e33fb663b96ca0a9950bc8ecaa7f13b55329f583a007a222704febfca`;
  executable source:
  `36fc0f3490a0ccb0fe88c5f747dd86ab1ea73b487b861b031e3ee77c089592ae`.
- Contract: `specs/INITIAL-OWNER-PROVISIONING-001.md`
  (`cb28aa540e8452d5b3c2edff192915676fc45626624f5aed7a997a626355e9f3`).
- Snapshot base: `b2563a7aab0844386adafa80a815a622356239e4`;
  snapshot patch:
  `81e3c2dca04589120af096c0ffeeaba4b59b8081af5df24d59ff70bf838025d2`.
- Verdict: **APPROVED**.

### Findings

No blocking or non-blocking findings in the bounded PR #189 correction.

### Standards

The correction keeps state ownership in the existing
`MariaDbInitialOwnerProvisioning` application seam. The CLI only selects the
explicit mode and passes validated inputs; `Makefile up` only opts into that mode.
Neither contains identity-classification SQL, failure suppression, `|| true`, or
a second bootstrap path. The production delta is limited to the existing owner
and CLI seams and introduces no duplicated persistence authority or unrelated
scope. PHP syntax checks for both changed production files pass.

### Spec

The local-mode method validates the same email/password/schema preconditions,
acquires the existing per-database/prefix advisory lock, and only then tests
whether every canonical identity table is empty. A completely empty identity is
delegated to the existing `provisionLocked` strict-create transaction while the
lock remains held. Any nonempty identity is delegated only to the existing
`START TRANSACTION READ ONLY` resume path. Thus partial, conflicting, foreign,
blocked, provenance-invalid, credential-less, grant-deficient, and
permission-deficient states cannot create a replacement and retain the existing
`LOCAL_OWNER_NOT_RESUMABLE` outcome.

The unflagged CLI still calls `provision` directly, so strict production create,
exact replay, and developed-state rejection are unchanged. Existing valid local
owners remain read-only: the local password is required for a possible clean
create but is neither compared nor written on the nonempty resume branch. The
same lock serializes clean local calls and developed resumes; the test matrix
retains exact busy behavior, clean create-versus-busy/replay outcomes, full
nine-table preservation, all provenance/authority rejection predicates, rollback,
lock release, schema/config failures, and redaction.

The corrected A4/delta and independent Gate 3 correction approval were reviewed.
All four supplied records are GREEN, exit 0, source-bound to the candidate and
executable source above, and retain their transcripts: route ownership
`1789690214558582000-b9a383d20865412882861e608ac1b66b`, runtime lifecycle
`1789690218493979000-f70726b588b94599a7e4db3672ba3d5d`, governance
`1789690417639579000-317157a052f64ea9863e2e89fd63b7c5`, and architecture
`1789690441799056000-dbd5d425e6f4466981c5b6f07cce895e`. Applicable command
blobs equal the reviewed test SHA-256 values.

**Gate 5 correction verdict: APPROVED.** This verdict covers only the bounded
first-local-startup blocker and regression check. A new exact-source PR #189 CI,
PR-ready admission, merge, deployment, and enforcement remain pending/`UNKNOWN`;
this review does not authorize merge or deploy.
