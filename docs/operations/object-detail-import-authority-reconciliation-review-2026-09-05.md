# Object-detail importer authority and contract reconciliation review

- Recorded: `2026-09-05T13:19:36Z`
- Reviewer: independently tasked read-only authority/dependency reviewer
  `/root/importer_authority_review`; reviewer authored no reviewed planning,
  specification, importer, migration or test
- Reviewed repository HEAD: `43472a41645a6cffeadda2c20d603aee785dc223`
- Scope: authority and technical-contract reconciliation only; no RED, test,
  production code, source import, real data, shared demo use or Gate approval
- Verdict: **PARTIALLY_COVERED_RECONCILIATION_REQUIRED**

## Question and determination

The owner-approved table transfer, inherited process constraints and approved
synthetic-data policy are enough authority to construct a task-owned disposable
MariaDB contour and to require the importer to perform no object-detail-family
DDL. They are not owner approval of the exact serial DML behavior in
`CHARACTERIZE-OBJECT-DETAIL-IMPORT-001`.

The distinction is explicit in the latest records:

- the owner approved moving creation of the exact two-table family into normal
  migrations, including the data-free/no-runtime-DDL ownership contract;
- the approval record says imported serial DML remains separately gated and
  excludes production import, fixture population and quarantine redesign;
- schema Gate 1 says it does not promote importer DML evidence into an approved
  behavior contract;
- the characterization task still requires durable owner approval before RED,
  and its exact executable spec still says `OWNER_APPROVAL_REQUIRED`.

Therefore no one may infer owner approval for the characterization spec's
literal payload hash, exact stdout, serial replay, rollback or rejection
assertions from the schema-transfer approval. The 2026-09-02 independent
readiness review is technical readiness, not owner authority. The interrupted
handoff review supplied no verdict and is not evidence.

## What is already authorized

The following work does not require a new product decision:

1. Use only fictional data in a private disposable database/server owned by the
   verifier. The importer target name guard requires exactly `fmonitor2_demo`,
   but does not require an existing shared demo server: the manifest accepts a
   nonempty host and port `1..65535`, and generation validation uses the private
   target sentinel plus that server's actual `@@hostname`. A separate private
   source database is selectable through the existing source environment seam.
2. Precreate the exact v12 family through the canonical migration owner, run the
   importer with a principal lacking `CREATE`, `ALTER` and `DROP`, and prove the
   schema fingerprint remains unchanged. Canonical ownership and importer
   no-DDL are already normative in approved
   `OBJECT-DETAIL-SNAPSHOT-SCHEMA-001` v0.4.
3. Require absent or incompatible family state to fail closed before source
   reads and before target DML, and remove the two importer
   `CREATE TABLE IF NOT EXISTS` statements after reviewed RED. This is the
   approved transfer's runtime boundary, not a new import semantic.
4. Preserve GRILL-004 boundaries: the verifier may create ephemeral fictional
   fixtures, but it may not seed the TEST-USER contour, import production-linked
   records, use personal data, or establish cutover/population policy.

The existing schema DDL-denial test is only migration partial-create/retry
coverage. Its review expressly leaves importer no-DDL and characterization
open, so it cannot substitute for importer RED or Gate 3.

## Required technical reconciliation before owner approval and RED

Revise the characterization's executable/OpenSpec package as one coherent exact
candidate, then obtain fresh technical Gate 1 review and explicit owner approval
of that exact candidate before RED:

1. Correct the OpenSpec proposal impact from a verifier “under `rapid-pilot/`”
   to the executable-spec seam
   `tests/Verification/characterize_object_detail_import_001_test.php`. The
   rapid-pilot importer remains the observed child process and is not the new
   verifier owner.
2. Reconcile the old two-slice sequencing. The verifier must precreate the
   family by the landed canonical v12 public migration seam, not by duplicating
   “current exact” test DDL. It must then execute the real importer with an
   independently inventoried least-privilege target principal: only the exact
   SELECT/locking reads and INSERTs required by the approved scenarios, and no
   schema-mutation grants. Assert the principal identity and grants before the
   child call.
3. Add an exact no-DDL/schema-precondition axis to the executable contract and
   tasks. It must cover: exact schema + DDL-denied principal succeeds for the
   approved serial scenarios; absent details, absent quarantine, and one
   independently specified incompatible fingerprint each fail with one fixed
   deployment/schema category before source connection/read and before target
   DML; schema, rows and decoys remain byte-identical. Define the exact public
   category/exit/output mapping before RED rather than deriving it from the
   implementation.
4. Make pre-source ordering observable without production data or a shared
   server. A private source endpoint that records or deterministically refuses
   first access can prove zero source access when target precondition fails.
   Inability to establish this fixture is `SETUP_FAILURE`, not qualifying RED.
5. Keep dry-run semantics explicit. The approved ownership invariant says no
   target DDL in dry-run or apply. Decide technically whether dry-run requires
   exact target schema before source access; record one exact outcome and test
   it. This must not silently widen the serial DML characterization.
6. Preserve every `UNKNOWN` exclusion: concurrency/absent-row races,
   present-to-missing or missing-to-present transitions, detail/quarantine
   coexistence precedence, refresh, retention/reconciliation, authorization,
   audit, semantic/range validation, consumer hash validation, premium meaning,
   production cutover and `lift_type`. Do not add assertions about these axes.
7. Reconcile task accounting across both changes. Characterization Gates 1–5
   establish the serial regression oracle; schema tasks 2.2/3.2 consume the same
   reviewed no-DDL/precondition cases to remove importer DDL. No task is complete
   merely because the schema engine or its migration DDL-denial coverage is
   GREEN. Architecture baseline reduction follows only the actual two-statement
   removal.

The contract may keep the existing fixed fictional six-field fixture and its
precomputed hashes as the candidate. Those exact values are technically ready,
but remain unapproved until the owner approves the reconciled exact spec hash.

## Owner decision boundary

There is **no genuinely new product-policy question** to send to the owner.
Private fictional fixtures, no personal/production data, canonical table
ownership, no runtime DDL and fail-closed schema access are already decided.
The listed `UNKNOWN` semantics remain outside scope and require no decision when
they remain unasserted.

There is still one mandatory owner action under the delivery process: approve
the reconciled exact `CHARACTERIZE-OBJECT-DETAIL-IMPORT-001` Gate 1 candidate.
That is new exact-scope authorization for the serial regression oracle, not a
request to decide table transfer again. A generic continuation message, the old
readiness verdict, or the existing schema approval cannot supply it. Any
normative edit after that approval changes the candidate hash and requires a
fresh exact-hash approval.

## Exact reviewed SHA-256

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
375f7b21d0a8035bb6e5d2284b914386d5352dc4d674f0e56eb6ba0bae8b4f99  docs/operations/autonomous-restart-handoff-2026-09-05-1309Z.md
0de0578ade9509923a322464c52e5958c5038a3a09ec3063b8be4a6de255918e  rapid-pilot/AGENTS.md
069f8d75334380b1b0348ab0ed60b508c6152e0cf4f8daa118827c5f79696950  rapid-pilot/import-production-object-details.php
7f4d2ff47c3e0b69f0a4e44583901a07bff4fde5b851c601b310d6d438753a96  rapid-pilot/legacy-migration/WorkforceCatalogReconciliationCandidate.php
6a13161ccd644e2423511cc3eb2dae918425dedda6b254ff12336590fac51e22  specs/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001.md
be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40  specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md
cb479d963026fdf4673fad62fcd8b90b1b021e8685476be211dc6c8689825bdd  openspec/changes/characterize-object-detail-import/proposal.md
246c4600f64dbf0e44b76ed80e852db1359372b2b78914582c871fdc634fea22  openspec/changes/characterize-object-detail-import/design.md
08ddc907ede8b09b164412feb32e7d7f96c904d2d6c173e8ee3f93c400f99790  openspec/changes/characterize-object-detail-import/tasks.md
d09ec281200c2c4522ead5714a1c879a592f3af42c8482cd4ba606900acc9d62  openspec/changes/characterize-object-detail-import/specs/verification/object-detail-import-characterization/spec.md
5bbb0d7fa9a36e5acfb6c94db5f60366ed3b99a9130f1b587c011f1211c8d87c  docs/operations/object-detail-schema-owner-approval-2026-09-05.md
df33d0207ef03b4992fc89bb09db19db230f2011347143d15eba995323fa8f0f  docs/operations/object-detail-snapshot-schema-gate1-review-v04-2026-09-05.md
7110845088542546fd8f557dc45b54149bb343f91e505dd0db97c8570223f9ee  docs/operations/object-detail-import-gate1-readiness-review-2026-09-02.md
49faf5ae3da3586e09c974caca5b71c30d41534a8226e31be6f856e865f4e87d  docs/operations/object-detail-schema-import-characterization-dependency-review-2026-09-05.md
ac29a675157c1f4bfbc2aa16e8ed3c579b6c4c66cf8ae1128d27548612a08a51  docs/operations/object-detail-import-behavior-evidence.md
cc8d66a09d156c7d5cead80ee5d40b36bcf52f2a5f0ed8cda78b1895989a97e2  docs/operations/object-detail-schema-evidence.md
a3ea053acc9c7ae50a9a96ac0e3e8125f91e2d1944c336027ce1fa40a9f84992  docs/operations/test-user-data-reset-decision.md
d2231cbe706dd9c992e5030955ba98edc07cbd634bdad609ce22f71b0695739f  reviews/tests/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001-ddl-denial-v1.md
```

## Final verdict

**PARTIALLY_COVERED_RECONCILIATION_REQUIRED.** Proceed with a coherent
characterization/no-DDL Gate 1 amendment using only a disposable private
synthetic contour. Seek owner approval only for that reconciled exact serial
regression contract. Do not ask again whether tables move to canonical
migrations, do not infer exact-hash approval, and do not promote any excluded
`UNKNOWN` behavior.
