# RUNTIME-READINESS-LOAD-001 — Gate 5 final review

- Reviewer: `/root/final_readiness_review` (independent; no specification, test,
  or implementation authorship)
- Committed HEAD: `2a1c12542c6f9833a8b544994a64309c125a61ef`
- Candidate source: `bdcfa1c06714b188ebe97cf48e76036013987ce012bd72cb17af25619b550d73`
- Base: `3c242f34e8f30986f1b8354c4ef947a4c63936dc`
- Prepared package:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T005457Z-c31739796c/package.json`
- Contract: `specs/RUNTIME-READINESS-LOAD-001.md`
- Prior Gate 3: `APPROVED` for the corrected pre-implementation tests, as
  preserved in `reviews/tests/RUNTIME-READINESS-LOAD-001.md`
- Verdict: **CHANGES_REQUESTED**

## Findings

1. **HIGH — the attestation is not bound to an exact application build.**
   Contract section 2 requires a deterministic identity of the exact application
   build and requires stale success to fail for another build. The Dockerfile only
   accepts the caller-supplied `FMONITOR_RUNTIME_BUILD_ID` build argument, Compose
   injects that same caller-supplied string into the runtime environment, and
   `RuntimeConfiguration` defaults it to `development`
   (`deploy/runtime/Dockerfile:20-21`, `deploy/runtime/compose.yaml:19,36-37`,
   `app/Runtime/RuntimeConfiguration.php:34`). Two different source/image builds
   configured with the same value therefore produce and accept the same build
   identity. The focused test proves only that changing the supplied string causes
   a mismatch; it does not prove derivation from immutable image or source
   material. Derive the identity from the exact built artifact/source and fail
   closed when that identity is unavailable.

2. **HIGH — changed recovery consumers are currently inconsistent with the new
   readiness contract, so schema-v33 backup/restore compatibility is not GREEN.**
   `tests/Runtime/runtime_recovery_001_test.php:75` still expects the canonical
   migration result to be schema version 32, although the candidate catalogue now
   returns 33. The same test prepares storage and calls only
   `MariaDbRuntimeReadiness::assertDeepReady()` at line 79; it never publishes an
   attestation, while production backup now calls steady
   `RuntimeReadiness::assertReady()` at `app/RuntimeRestore/RuntimeRecovery.php:25`.
   Its valid backup must consequently fail `STARTUP_NOT_READY`. Likewise,
   `tests/Runtime/runtime_jobs_recovery_001_test.php:42` calls steady readiness
   immediately after prepare without creating startup evidence. Finally,
   `tests/Runtime/deadline_transfer_certificate_recovery_001_test.php:42` still
   expects steady readiness to fingerprint an unrelated renamed table and return
   `SCHEMA_NOT_READY`, directly contradicting contract section 3's prohibition on
   schema fingerprints during steady probes. Correct these post-Gate-3 deltas and
   obtain source-bound GREEN evidence for all changed recovery consumers.

3. **HIGH — the prepared Gate 5 package does not establish Gate 4 completion.**
   It lists 14 focused local obligations but contains exact-source GREEN evidence
   for only `runtime_readiness_load_001_test.php` and
   `production_runtime_contract_001_test.php`. The delivery record says recovery
   runs were interrupted, and OpenSpec task 3.2 remains unchecked because the
   required login, object card/editor, construction-control and OTIZ smokes were
   not run. `docs/development-process.md` requires planner-selected tests and
   relevant regressions before the final decision; missing, interrupted and
   UNKNOWN checks are not GREEN. Exact-source CI is correctly still UNKNOWN and
   remains a subsequent required gate, but it cannot retroactively make this
   incomplete Gate 5 package approvable.

4. **MEDIUM — the recorded load measurement does not execute the contract's
   declared HTTP/Compose contour.** Contract section 5 requires comparison on one
   isolated Compose project, including sequential and four-concurrent probes. The
   delivery record states that the application image was not built and that the
   measurement only reproduced the request bootstrap order. The reported global
   counter deltas are explicitly upper bounds containing background and sampling
   traffic. This is useful directional evidence, but it does not prove the exact
   committed HTTP probe path, startup dependency chain, or that concurrent HTTP
   probes never deep-check. Keep this result as exploratory evidence and repeat
   the required measurement on the exact candidate Compose application.

## Standards axis

Gate 4 evidence and the required focused user-flow checks are incomplete. A
non-blocking maintainability issue also remains: schema version `33` is duplicated
in the migration, regular readiness and recovery code. Deriving the comparison
from the canonical frontier would avoid primitive duplication and synchronized
future edits. Generated runtime/template parity is intentional and is not treated
as duplicated-code scope.

## Spec axis

Startup dependency direction, current-DB connection plus `SELECT 1`, bounded
marker lookup, attestation file mode/owner/link checks, public 200/503 structure,
DB-independent liveness and read-only concurrent class-level probes otherwise
align structurally with contract sections 1–4. Those strengths do not close the
exact-build violation, stale recovery regressions, missing focused evidence, or
non-representative operational measurement above.

**Final decision: CHANGES_REQUESTED.**
