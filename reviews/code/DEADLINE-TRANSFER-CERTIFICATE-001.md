# DEADLINE-TRANSFER-CERTIFICATE-001 — Gate 5 code review

- Date: 2026-09-14
- Reviewer: `review_certificate` (independent agent; authored none of the specification, tests, fixtures, implementation, or retained verification evidence)
- Frozen package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T192613Z-9f7c30d127/package.json`
- Exact reviewed source: reconstructible snapshot over base `79b3a4cecae558d74d781f353e967f03c036b921`, candidate source `f38aa3426068aa9c53568d7c63a8ae46a6249c9ab1d0847409169253b7bb414f`, executable source `fea23c5e52f0f8fee4bcc7f145c1dda9e33524ba95bc790d727270bec27616a5`
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T192613Z-9f7c30d127/snapshot`, patch SHA-256 `c5d1797101ff1c03d797932f25aac819f29cd2e553f6859a15669a73d1a7c8df`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T192613Z-9f7c30d127/verification-plan.json`, SHA-256 `0d450dc7c415cf416cae0c18e73c751697fb634b5aba1349edb84cc4aebdf162`
- Scope: certificate application, v25 migration/readiness/recovery, exact role grants, Yii routes/controller/view/card, architecture ownership and the associated certificate tests. Concurrent native-input, calculator and publication work in the snapshot is outside this decision.

## Findings

1. **CRITICAL — the migration/readiness fingerprint accepts structurally incompatible certificate tables.** `DeadlineTransferCertificateSchemaMigration::compatible()` at `app/InstallationProcess/DeadlineTransferCertificateSchemaMigration.php:13` compares only `Field`, `Type`, `Null`, and `Extra`. It does not inspect defaults, string collations, primary/unique indexes, foreign keys, or the InnoDB engine required by `specs/DEADLINE-TRANSFER-CERTIFICATE-001.md:25,31`. Thus an existing table with the right columns but no `(installation_case_id,revision_number)` uniqueness, no revision/chunk FKs, a wrong primary key, MyISAM, or an incompatible collation is accepted; `MariaDbRuntimeReadiness` then also reports it ready because it delegates to the same method. The incompatible-table test creates only a one-column table, so all such defects remain GREEN. Correction: make the production fingerprint validate the complete declared structure and add representative preexisting same-column/wrong-key, wrong-FK, wrong-engine/collation/default cases proving refusal before any DDL.

2. **HIGH — `currentEvidence()` does not return the required revision metadata.** The contract at `specs/DEADLINE-TRANSFER-CERTIFICATE-001.md:19` requires revision metadata plus the source envelope. `app/DeadlineTransferCertificate/MariaDbDeadlineTransferCertificates.php:36` returns only `id`, `certificateDate`, `newDeadline`, and `source`; it omits `installationCaseId`, `revisionNumber`, `previousRevisionId`, `correctionReason`, `actorId`, `recordedAt`, `sourceLabel`, `sourceLocator`, `pdfSha256`, and `byteSize`. The application test checks only three selected fields and the digest, so the incomplete public result passes. Correction: return the complete mapped revision with the source envelope and assert the exact result shape/value.

3. **HIGH — corruption in an earlier revision's lineage or dates is not rejected.** In `MariaDbDeadlineTransferCertificates::chain()` at line 38, the `foreach` body contains only the stored-byte check; the revision-number, previous-link and date condition executes once after the loop against the final `$r/$i`. A corrupt certificate date, deadline, revision number, or previous link in any non-current revision therefore passes `read()`, `download()`, and `currentEvidence()`, violating the fail-closed immutable linear-history rule at contract lines 19 and 35. Current tests corrupt only the latest revision. Correction: validate all metadata inside the loop and add corruption witnesses against revision 1 while a later current revision exists.

4. **HIGH — Yii resolves object existence before certificate authorization and leaks that existence to forbidden users.** Each controller action calls `object($id)` before constructing the service and invoking its authorized read/write/download (`app/YiiRuntime/Controllers/DeadlineCertificateController.php:6-8`). A user without certificate access receives 403 for an existing object but 404 for a missing object, and the page query reads process/legacy rows before the application authorization. This conflicts with the authorization-before-disclosure invariant in contract lines 13 and 17. The HTTP tests cover forbidden existing objects and missing objects separately, but never request a missing object as a forbidden actor. Correction: authorize the session actor before object/case resolution, or expose one application-owned authorized resolver, and add forbidden-existing/missing parity without data reads or PDF acquisition.

5. **MEDIUM — idempotency fingerprints depend on PHP array insertion order and undeclared extra fields.** `submit()` hashes `json_encode([$c,$bytes])` directly at `MariaDbDeadlineTransferCertificates.php:14`. Two commands with identical declared metadata and PDF but a different associative-key order produce different hashes and `OPERATION_CONFLICT`; adding an irrelevant extra key does the same because command validation permits extras. That violates semantic same-request replay in contract line 27 and makes transport construction details part of domain identity. Correction: construct the fingerprint from the declared fields in one fixed order (and the PDF hash), and test replay with reordered input plus explicit handling of extra keys.

6. **MEDIUM — `download()` expands the declared result with an internal revision object to support route ownership.** Contract line 17 declares `{mediaType,filename,bytes,byteSize,pdfSha256}`. The implementation returns an additional `revision` key at line 35, and the controller depends on it for cross-object authorization. The tests compare selected keys and therefore do not detect the public seam drift. Correction: preserve the declared exact download result and perform object/revision ownership through an authorized application method, or explicitly amend and reapprove the contract/test shape before changing implementation.

## Verification and frontier assessment

The v25 catalogue, current recovery inventory, backup/restore validator, role catalogue, capability allowlist, routes and architecture SQL-owner rule are wired. The runtime recovery test provides strong real-image dump/restore coverage, and the focused application/schema/recovery/HTTP tests exercise the intended major boundaries. Syntax and `git diff --check` are GREEN for the frozen source.

The only GREEN record on the exact reviewed package source is HTTP `1789413976265040000-b4c9932edc354554aa3d72d1a7fd6a60` (`f38aa342...`, executable `fea23c5e...`, no drift). Application `1789413716630487000-09f2f8397e174f4f92387c274ca4d556`, schema `1789413716630524000-68f136ad190e4f23a3f0167cfe112b4b`, and recovery `1789413716636362000-47cdaf5f4ec046769e9d4db80adb9148` are GREEN at earlier source `c244df2fd842da92905db6b2cef95e6d2d42ad25c74acba022041fdfb536a47f`, executable `92d598476e669bed1d3066e40baea0f8b3f9b3720cb4f8482455b70d87fb0afe`, each without drift. The provided artifacts do not establish byte equivalence of their relevant files to this package, so they are retained historical evidence and not claimed as exact-package GREEN. The root Gate 5 prepare rejection caused by concurrent unrelated source changes likewise is not approval. Full exact-source CI and deployment remain `UNKNOWN`.

## Verdict

`CHANGES_REQUESTED`

Gate 5 is blocked. Correct the six certificate findings, add sensitive tests where identified, run the four focused certificate checks on one new frozen exact source, and submit the complete package for independent correction review. Because findings 1-3 and 5-6 require test expectation changes or additions, they restart the affected Gate 2/3 review before final Gate 5 approval. No full local suite is requested; the canonical full matrix remains one later exact-source CI run.

---

## Gate 5 correction review — 2026-09-14

- Reviewer: `review_certificate` (independent; authored none of the reviewed artifacts or evidence)
- Frozen package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T194550Z-0c3d0d1716/package.json`
- Exact source: candidate `719b1c81888252e0d199c94e58b0e3bfe0a3f563420cf246882a2c786032a1dc`, executable `612923bd364f53de61c46374016d3c7e7be7a3768c3fda673265f42565f8970e`, base `79b3a4cecae558d74d781f353e967f03c036b921`
- Snapshot patch SHA-256: `e2185a187f7473cd14a29806c3d2d7f20fff44ed759b53dc3f3360ef1eb7293c`; verification-plan SHA-256: `08c5bc13e6304947468e069815ca2e7e875c6d1bf16d03e3ab84e70e32af35c7`
- Scope: the six certificate corrections and necessary v25 migration/readiness/recovery. Concurrent native-input/publication work, numeric-frontier updates, later UI provenance work and rapid-pilot compatibility are outside this verdict.

### Finding resolution

No findings remain.

1. Migration compatibility now checks exact columns, null/default/collation properties, primary and unique keys, foreign-key targets, InnoDB and table collation; migration/readiness reject each approved same-column conflict before DDL.
2. `currentEvidence()` returns the complete mapped revision plus source envelope.
3. Bytes, sequence, predecessor and dates are validated inside the loop for every historical revision.
4. Yii authorizes through the application owner before object lookup. The valid missing-case write probe reaches `CASE_NOT_FOUND` before byte acquisition for an authorized actor; forbidden actors fail first. PDF routing checks revision membership through authorized `read()`.
5. Idempotency hashes a fixed ordered set of declared semantic fields plus PDF SHA-256, ignoring input order and undeclared transport fields.
6. `download()` exposes exactly the five declared keys; route ownership no longer depends on an internal revision payload.

The recovery witness uses the real retained v24 public tool outside the checkout and proves refusal of v25 backup/restore without database or private-state mutation. Current recovery still proves full v25 dump/restore, binary bytes, counters, readiness and public read/download behavior.

### Exact-source verification

All four records start/end on candidate `719b1c81...`, executable `612923bd...`, and exit 0:

- HTTP `1789415080607994000-b0c1e74d024741feab306b0ea77d5899`;
- application `1789415080595204000-5ac978bc9d1d44e7a63a14d6e1f9b331`;
- schema `1789415080591353000-6e90adf1a42e47aa82061677031fb782`;
- recovery `1789415080604781000-5c28799772704ebdb967bd9114a099b4`.

Each prints its `PASS DEADLINE-TRANSFER-CERTIFICATE-001` result with empty stderr. PHP syntax is GREEN for the corrected owner, migration, controller and recovery test. Per owner policy, the canonical local full suite was not run. Exact-source CI and deployment remain later gates.

### Verdict

`APPROVED`

Gate 5 passes for the bounded certificate implementation at exact source `719b1c81888252e0d199c94e58b0e3bfe0a3f563420cf246882a2c786032a1dc`. The six prior findings are resolved and all four focused certificate consumers are GREEN on one frozen source.

---

## Gate 5 schema-v26 integration review — 2026-09-14

- Frozen root package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T200621Z-c02e97116a/package.json`
- Exact source: candidate `04c2a9529014ecf6a082112abdca2f3ad0e0adb9f94dbdd7aaad8bf2239207e4`, executable `4c19a6d379546d68b28d843c186d929f3660aac9b979c194f84a9771636f6a38`, base `d6daceea01cd5e4a6dd20b56921f767224cd1fb5`
- Scope: production certificate renumbering from migration 25 to 26, preservation of upstream feedback v25, current recovery inventory 77/42, and associated root frontier deltas. Certificate domain/HTTP logic was previously approved and is unchanged. Publication, native inputs, later derived-source UI work and rapid-pilot compatibility are excluded.

### Findings

1. **LOW — two normative paragraphs still assign the certificate migration to v25.** `specs/DEADLINE-TRANSFER-CERTIFICATE-001.md:23` says existing role grants are added “during v25 migration,” and line 25 says “Canonical v25 extends v24 with certificate” tables. This contradicts the correctly updated v26 acceptance section, migration catalogue and recovery profile. Correct these phrases to “during v26 migration” and “Canonical v26 extends v25.” Root has identified the same exact correction for the next frozen source.

### Production and frontier assessment

The production renumber is otherwise coherent. Catalogue version 25 remains `FeedbackSchemaMigration`; version 26 owns `DeadlineTransferCertificateSchemaMigration`, whose result reports 26. `RuntimeRecoverySchemaV25.php` is byte-identical to the upstream baseline (SHA-256 `29a475f2d94b300a689d7c2e611280db49bcfa74224a3768d85c195b7cddb821`) and remains 73 tables/41 auto-increment owners. `RuntimeRecoverySchemaV26` composes it with the four certificate tables and only the revision counter, yielding exactly 77/42. Runtime backup/restore validation consistently uses the v26 profile.

The audited current-frontier expectations use version 26 and complete migration ledgers. Literal Jobs/recovery inventories include all certificate tables and their sole counter. Independent restored-snapshot runs of certificate schema, real-container certificate recovery, Jobs schema, production frontier, settlement schema and clean-stand provisioning all pass. The package itself has no retained evidence; the concurrent checkout recovery harness result remains `UNKNOWN` and is not reported as GREEN. The exact version-25 checks in the old demo paths are retained outside this bounded integration scope.

### Verdict

`CHANGES_REQUESTED`

The production renumber and root test delta are approved, with no behavioral defect found. This exact frozen source cannot close the integration review because its normative certificate text still assigns the feature to v25 in two places. Apply the two stated prose corrections and include them in the next frozen exact-source package; no new implementation or behavioral test work is requested.

---

## Gate 5 schema-v26 final correction disposition — 2026-09-14

- Final package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T201555Z-63a287a5ba/package.json`
- Exact source: candidate `76539fec0ff2f9314a33e437cc5827abcefaa8fc65ea331665524daad8e24ecd`, executable `011b284d4a8eb03ee4a5ada7663c104869c848fd5ac4d11e043c024a83f9a541`
- Snapshot patch SHA-256: `0b5302c3f7ea61bfc3ac1121a8bbed832a778187dac1284c6dda2d7884c952f1`; plan SHA-256: `800749077465d8a14410906f33bd5bfc7ea08624b17dd49c8c940a8d4cb5d8f4`

No findings remain. The final package changes both contradictory phrases to “during v26 migration” and “Canonical v26 extends v25.” The previously reviewed production composition remains intact: feedback owns v25, certificate owns v26, v25 is byte-identical to upstream, v26 contains 77 tables and 42 auto-increment owners, and runtime recovery validates the v26 profile. Root frontier expectations, literal inventories, forward-update coverage, clean-stand facts and verification registrations are coherent.

All eight newly registered focused tests are GREEN at the exact candidate/executable source. The four certificate records are:

- application `1789416961399559000-aab5b69a7a3e40de8385a7bc1014b494`;
- schema `1789416962568777000-0ac88614cacb4873a1e539c0017f9a7a`;
- HTTP `1789416963726836000-b5d69b5b6c94431da2e6fcccadd06f69`;
- recovery `1789416964884520000-b0737c44e6c043a8a209340536542b28`.

`make architecture-check` record `1789416985558315000-f5c5e2648e364e408b8816151743d829` is also exact-source GREEN. No rapid-pilot compatibility work is required or approved.

### Final verdict

`APPROVED`

Gate 5 passes for the bounded certificate schema-v26 integration on exact source `76539fec0ff2f9314a33e437cc5827abcefaa8fc65ea331665524daad8e24ecd`.

---

## Gate 5 maximal-prefix correction — 2026-09-14

- Final package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T205757Z-420e6f2965/package.json`
- Package source: candidate `236ac45d7cb769f8946796b85e244198e0397170af41525df61a7c6fa3b22a80`, executable `8d1d8443b7305efc965307d86e138cceb0f5e20f71a218c9f0b9f3a85172fca8`, base `b1d6b5178126090e6e2ee3d6eb090100547d3c47`
- Snapshot patch SHA-256: `3c923ce76030e806e0f94ec826474752fcb609cd3c5cf37ee731344517c02b91`; plan SHA-256: `7be08ba6169790e90f5829929bb840ccb3a02d403fd8d1223d553b9b4e77ed44`
- Scope: only the certificate migration's maximal-prefix foreign-key identifier correction and its approved physical-inventory/dual-prefix witness.

### Assessment

No findings. The migration replaces MariaDB-generated foreign-key names with four explicit identifiers formed from the validated 0..25-byte prefix plus short fixed suffixes. The longest is 40 bytes, below MariaDB's 64-byte identifier limit. Names remain distinct across the four certificate constraints and across distinct prefixes in the same schema. Referenced tables, columns, keys, DDL order, compatibility fingerprint and all previously approved certificate behavior are unchanged.

The corrected schema test constructs the physical v25 baseline through the public catalogue, adds the literal four-table v26 expectation, validates a 25-byte prefix, installs a second distinct 25-byte prefix in the same database, and proves replay of both preserves the complete combined DDL and rows.

Record `1789419389095745000-7eb5f0eaf40a41d8bc8a3753a3b2c2b6` is GREEN with exit 0, empty stderr, and `PASS DEADLINE-TRANSFER-CERTIFICATE-001 schema`. It starts and ends at candidate `24bb928a97b2691dd2a0d8fa7951dd06bfc937721de51bc2763ad65612f53f03` and executable `8d1d8443...`; the package retains the identical executable digest after append-only review lifecycle changes changed the candidate digest to `236ac45d...`. Thus the reviewed specification, test and production bytes match the GREEN execution. The seven other runtime checks were retained GREEN at the same production executable before the root test-lifecycle append; no broader certificate logic changed.

Current-Yii2 CI policy is a separate root-owned correction. This review does not waive mandatory CI and authorizes no old-pilot or rapid compatibility work.

### Verdict

`APPROVED`

Gate 5 passes for the bounded maximal-prefix correction at executable source `8d1d8443b7305efc965307d86e138cceb0f5e20f71a218c9f0b9f3a85172fca8`.
