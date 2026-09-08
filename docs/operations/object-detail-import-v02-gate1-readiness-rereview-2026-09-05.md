# CHARACTERIZE-OBJECT-DETAIL-IMPORT-001 v0.2 — independent Gate 1 readiness rereview

Date: 2026-09-05. Reviewer: `/root/selection_contract_reconciliation`.
Reviewed repository HEAD: `4db6c004d444b129c56b33879e7f9cc896dc3762` plus the current
uncommitted root-authored v0.2 candidate identified by exact hashes below. The
reviewer authored none of the reviewed executable or OpenSpec artifacts.

Verdict: **READY_FOR_OWNER_APPROVAL**.

This verdict means the corrected exact candidate is technically coherent enough
to present for the mandatory product-owner Gate 1 approval of the PILOT_ONLY
serial regression oracle. It is not product-owner approval, Gate 1 approval,
RED, Gate 3, implementation permission, GREEN, Gate 5 or Done. It does not
reopen or infer the already recorded table-transfer approval.

## Prior findings resolved

1. The public oracle now declares two exact argv forms. Apply cases include
   `--apply`; clean dry-run and dry-run schema-refusal cases omit it. The spec
   assigns every scenario class to one form and fixes capture-time use, so the
   earlier universal-`--apply` contradiction is closed.
2. The owned artifact inventory is exact: `object-detail-<token>/manifest.json`
   is the only child file, mode 0600 under a 0700 directory. Child process
   results, schema/row/grant snapshots, image/container identities and assertion
   evidence remain bounded in verifier memory and are checked before cleanup.
   No transcript, snapshot or credential files are allowed. The ambient decoy
   has exact name/bytes, is owned by the meta-test outside the verifier child,
   survives both runs and is deleted only by its creator after identity/byte
   verification. Thus “evidence is saved” no longer conflicts with “no owned
   run artifact survives.”
3. Numeric monotonic bounds now cover Docker operations, health readiness,
   importer children, listener control, output size, the whole per-token run,
   SQL connection setup, graceful/forced child termination, reaping and exact
   container removal/absence proof. Startup/readiness/control failures map to
   setup failure; importer timeout/overflow after a healthy fixture maps to
   regression failure. Cleanup has its own bounded budget, can never yield
   success, and preserves an already established regression exit while adding
   a safe cleanup/setup category. No indefinite wait or sleep-based oracle
   remains permitted.

## Consistency confirmation

- The exact apply/dry-run refusal matrix remains after successful generation
  validation and before source connection or target DML. Its exit 2, fixed JSON
  plus LF and empty stderr mapping is observable and does not absorb existing
  argument/generation failures.
- The listener protocol remains constructible within the new two-second bound:
  an accepted positive control proves the listener, then a nonblocking accept
  after child reap distinguishes an empty queue from any behavioral source TCP
  connection. Failure to establish the control is setup failure.
- The private disposable MariaDB contour, immutable image-ID use, exact
  container label/name/endpoint validation, literal `fmonitor2_demo`, private
  source database and generation-sentinel/hostname checks remain coherent with
  the actual importer guards.
- Least-privilege grants remain sufficient. Source needs SELECT on its four
  exact tables. Target SELECT covers cases, sentinel, family and metadata/
  `SELECT ... FOR UPDATE` reads; INSERT covers the two family writes. USAGE is
  the only global baseline; no CREATE/ALTER/DROP/UPDATE/DELETE or role/schema
  grant is admitted. The privileged setup connection is not passed to the
  importer and verifies principal/grants separately.
- The verifier-meta RED and real-importer no-DDL/pre-source RED remain distinct.
  Current apply is expected to fail on its runtime CREATE under the DDL-denied
  principal; current malformed-schema dry-run is expected to reach the source
  listener. Neither setup failure nor the absence of future verifier code can
  substitute for the production-boundary RED.
- Clean/replay/conflict/source-rejection expectations, fixed output key order,
  full row/schema/decoy observations, first-capture preservation and independently
  fixed hashes remain unchanged and constructible.
- UNKNOWN exclusions remain exhaustive for the named transitions, concurrency,
  coexistence, reconciliation/retention, target product authorization/audit,
  semantic validation, consumer verification, TEST-USER population, production
  cutover, premium meaning and additional fields. The fixture's grant proof is
  isolation evidence and does not promote target authorization behavior.
- The executable spec, proposal, design, task order and delta requirements agree
  that characterization owns the serial oracle while the separately gated
  canonical-schema change owns production no-DDL/precondition implementation.
  Neither change is marked complete by existing schema-engine GREEN alone.

## Verification

The previously independently recomputed expected hashes remain:

```text
5fbb37587f0bd1dff238fd1e97972b4e74d9ac4583c875961d9639e6022e0d15  object 451301 canonical material
5f3d14bbdc7708430233092240bc82fe3ab3e28867ef1e4b7bc14fbf542e2b89  object 451302 missing-source material
```

`openspec validate characterize-object-detail-import --strict` passes and
`git diff --check` passes at rereview time.

## Next gate

Present the exact v0.2 candidate hash set below to the product owner for explicit
Gate 1 approval of this bounded serial characterization. Do not ask the owner to
approve the canonical table transfer again and do not describe this technical
verdict as owner approval. Any normative edit after approval changes the
candidate and requires exact-hash reconciliation before RED.

## Exact reviewed SHA-256

```text
a2e9f65a20bd6e33c740c774094508b32a33faa6e0bdf4ace498e31f024e24c9  specs/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001.md
bc6c8ec30aa9a17d0074ebe3e5273febf3961534da1467d65fd7324ba0da3691  openspec/changes/characterize-object-detail-import/proposal.md
cf72aa312f86f1e2923b531fdad9c741dd5b78a45a50faeba6740e3749dc9c11  openspec/changes/characterize-object-detail-import/design.md
2e3e23e62b38a0b2093cf17785d6cb916eb566690817bb7a674c9e7825947716  openspec/changes/characterize-object-detail-import/tasks.md
f69b02e090df4a888a69b2e9c53cce262162b16314be2d4d8e37327ea7cfac0c  openspec/changes/characterize-object-detail-import/specs/verification/object-detail-import-characterization/spec.md
e9861ca0bc9f3e859f49961383e1931d7c92098a79534d4bec52f8ab2ef4812b  docs/operations/object-detail-import-authority-reconciliation-review-2026-09-05.md
338cc053460079932dabc7e7c0cfda84e0c0e1784e71969a31993b04245955f5  docs/operations/object-detail-import-v02-gate1-readiness-review-2026-09-05.md
be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40  specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md
069f8d75334380b1b0348ab0ed60b508c6152e0cf4f8daa118827c5f79696950  rapid-pilot/import-production-object-details.php
7f4d2ff47c3e0b69f0a4e44583901a07bff4fde5b851c601b310d6d438753a96  rapid-pilot/legacy-migration/WorkforceCatalogReconciliationCandidate.php
```
