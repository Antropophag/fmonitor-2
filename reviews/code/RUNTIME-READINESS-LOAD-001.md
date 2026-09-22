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

---

## Final rereview — corrected and rebased candidate

- Reviewer: `/root/final_readiness_review` (same independent reviewer; no
  specification, test, or implementation authorship)
- Committed HEAD: `5f9d2ee7e413984ae11adeab9b540ca43eac4df7`
- Candidate source: `a9ef9dae800a86950d26c1671ef103aa5d42845aaa564fc0397832b29812a291`
- Current-main base: `bf3f7ea478b7ce9fb1ae85657d2b7826c8b8acd9`
- Prepared package:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T065724Z-3c8c8b5266/package.json`
- Pull request: `#234`, mergeable and clean against the recorded base at review
- Exact-source CI: Quality Graph run `35694634455`, head SHA
  `5f9d2ee7e413984ae11adeab9b540ca43eac4df7`, fully GREEN for plan, fast,
  unit, both integration shards, e2e, governance, verify, quality-results and
  aggregate Quality Graph
- Verdict: **CHANGES_REQUESTED**

### Disposition of the prior findings

1. **Exact build identity — fixed.** The production image now computes a SHA-256
   identity from the actual installed runtime tree and pinned UI output, stores it
   in a root-owned read-only image file, and the attestation reads that file with
   shape, regular-file, link and writability checks. The operator-controlled
   identity and `development` fallback were removed from production Compose.

2. **Recovery/schema-v33 regressions — fixed.** The implementation no longer adds
   a v33 migration or recovery inventory. It reuses the v32 catalogue frontier and
   bounded existing server/database/canonical-table metadata. Backup performs the
   deep check directly; restore validates the restored schema, invalidates copied
   source startup evidence and remains unready until the public startup-check
   publishes target-applicable evidence. The recovery, jobs and certificate tests
   were aligned with those semantics and passed exact-source CI.

3. **Gate 4 regressions and UI smokes — fixed operationally.** Exact-source run
   `35694634455` executed the registered unit, both integration, e2e and governance
   categories successfully. This covers the changed recovery consumers and the
   registered Compose/login/object-card/editor/construction-control/OTIZ paths.
   HEAD contains current main exactly at merge-base
   `bf3f7ea478b7ce9fb1ae85657d2b7826c8b8acd9`; PR #234 is clean/mergeable.

4. **Exact Compose/HTTP load measurement — still open.** Contract section 5
   requires the one/sequential/four-concurrent comparison on one isolated Compose
   project, through the actual candidate HTTP readiness path, with the stated SQL
   and temporary-table counters, background control and separate startup cost.
   The retained delivery record still explicitly says the application image was
   not built and that the measurement merely reproduced bootstrap order. Those
   directional upper-bound figures do not prove the exact committed HTTP/Compose
   path or concurrent-probe behavior. The GREEN CI run validates functionality and
   regressions but does not contain this required operational measurement.

### Remaining governance inconsistency

`openspec/changes/bound-regular-runtime-readiness/tasks.md` marks measurement and
UI smoke complete, while `docs/operations/runtime-readiness-load-delivery.md`
still says live image startup/UI smokes, exact-source CI and final review are
pending. The prepared package's lifecycle projection likewise reports CI as
`UNKNOWN` and final review `PENDING`, although the package verification plan
requires Gate 3 and final review and the exact-source CI was independently
verified above. Preserve the successful CI evidence, but reconcile these records
to the actual state; UNKNOWN or contradictory evidence cannot be used as GREEN.

### Rereview conclusion

No remaining code-behavior defect was found in the corrected build binding,
bounded readiness, startup dependency direction, attestation handling, v32
backup/restore compatibility, public HTTP semantics, or current-main merge.
Approval is withheld solely because the mandatory section 5 exact-candidate
Compose/HTTP measurement is still absent and the completion records currently
claim a state contradicted by their own delivery evidence.

**Final rereview decision: CHANGES_REQUESTED.**

---

## Conclusive rereview — exact Compose measurement closure

- Reviewer: `/root/final_readiness_review` (independent throughout; no
  specification, test, production, or measurement implementation authorship)
- Delivery production source: merged PR `#234`, commit
  `5f9d2ee7e413984ae11adeab9b540ca43eac4df7`
- Evidence-only follow-up: PR `#235`, exact HEAD
  `ab7d508382dadbf89c7cf6b7d0a82db127ce03e6`
- Follow-up base: `0504d2589835f2583dc9afdbc47e4694e2573365`
  (merged PR #234 on current main)
- Exact-source follow-up CI: Quality Graph run `35700321393`, fully GREEN for
  plan, fast, unit, governance, e2e, both integration shards, verify,
  quality-results and aggregate Quality Graph
- PR state at review: clean and mergeable; changed files are limited to the
  registered production Compose test, delivery evidence and OpenSpec task state
- Verdict: **APPROVED**

### Final disposition

1. **Exact build binding remains fixed.** PR #235 contains no production change;
   the immutable image-derived identity approved in the preceding rereview is
   unchanged.

2. **Recovery and v32 compatibility remain fixed.** PR #235 contains no schema,
   recovery or startup implementation change. Exact-source CI is GREEN across both
   integration shards and the complete matrix.

3. **Gate 4, public HTTP and focused UI coverage remain GREEN.** Run
   `35700321393` is bound to the follow-up exact HEAD and passes the full active
   matrix. The earlier run `35700006495` was cancelled by the superseding commit
   and is not used as approval evidence.

4. **Exact Compose/HTTP measurement — fixed.** The registered
   `production_runtime_compose_001_test.php` now measures the exact candidate image
   through real nginx → PHP-FPM → `/health/ready` on its isolated production
   Compose project. It records the required background control and single,
   ten-sequential and four-concurrent series across SQL Questions,
   `Created_tmp_tables`, `Created_tmp_disk_tables`, elapsed time and HTTP status.
   The retained observation is:

   - control: `+2` Questions, `+1` temporary table, `+0` disk temporary tables;
   - single: `+8`, `+2`, `+0` in `5.8 ms`;
   - ten sequential: `+62`, `+11`, `+0` in `38 ms`;
   - four concurrent: `+26`, `+5`, `+0` in `13 ms`;
   - every measured HTTP request returned `200`, and the test removed its
     containers, network and volumes.

   Sampling queries and background healthchecks are explicitly retained as
   upper-bound noise rather than falsely attributed to individual probes. The
   bounded series and absence of disk temporary tables materially close contract
   section 5 without claiming site throughput or p95 performance.

### Final conclusion

All findings from both prior `CHANGES_REQUESTED` decisions are closed. The
production behavior, recovery semantics, public seams, immutable build binding,
current-main ancestry, focused user flows, exact Compose measurement and complete
exact-source CI now satisfy `RUNTIME-READINESS-LOAD-001`. No unresolved finding or
UNKNOWN remains within the reviewed delivery scope.

**Final conclusive decision: APPROVED.**
