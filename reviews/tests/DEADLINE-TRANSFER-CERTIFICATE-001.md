# DEADLINE-TRANSFER-CERTIFICATE-001 — Gate 3 test review

- Date: 2026-09-14
- Reviewer: `review_certificate` (independent agent; authored none of the reviewed specification, tests, fixtures, or evidence)
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T175816Z-09c9dac8f7/package.json`
- Exact reviewed source: reconstructible snapshot over base `79b3a4cecae558d74d781f353e967f03c036b921`, candidate source digest `3d185a96f54f847619eb2b14f80cb386edbf3a33dbf9dcece5207bf9285207a2`, executable source digest `9d84546d473d3e49798f4c113641740710e366dcdc6f35113b27db7ac3a68c3b`
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T175816Z-09c9dac8f7/snapshot`, patch SHA-256 `11dbaaa238cb77e22b2880eee5003cde9c090e75ee26c8e3f36f6ffa19d0cb11`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T175816Z-09c9dac8f7/verification-plan.json`, SHA-256 `4e9209f4f41d063ec0eccd578012986c6c6f61f0a64219b64ef63de330dc7029`

## Findings

1. **CRITICAL — the schema/recovery contract is largely assertion-free.** The normative acceptance at `specs/DEADLINE-TRANSFER-CERTIFICATE-001.md:25,31,43` requires exact v25 types, nullability, primary/unique/foreign keys and checks, incompatible-existing-table refusal before DDL, additive v24-to-v25 migration, unchanged old profiles, backup/restore inventory and byte/counter preserving recovery. `tests/InstallationProcess/deadline_transfer_certificate_001_test.php:52-53,76-79` checks only column names, table/counter counts, table inclusion, and one live HEX reconstruction. An implementation with wrong signed/width/nullability definitions, absent uniqueness/FKs/checks, destructive or non-additive migration, no schema-fingerprint refusal, or broken v24/v25 restore can pass. Correction: add exact schema metadata/index/FK/check assertions, incompatible precreated-table no-mutation cases, clean and v24-forward frontier cases, unchanged legacy-profile witnesses, and v25 backup/restore round-trip with byte-identical chunks and counters.

2. **HIGH — atomic storage and corruption sensitivity does not cover the declared failure boundaries.** The contract at lines 9, 13, 19, 27, 31 and 33 requires rejecting a callback chunk over 65536 bytes, rollback on chunk insertion and commit failure, and fail-closed evidence for damaged hash, size, dates, or nonlinear lineage. The application test supplies only compliant chunks, injects a revision INSERT failure, uses the generic `beforeCommit` observer, and corrupts only `pdf_sha256` (`tests/InstallationProcess/deadline_transfer_certificate_001_test.php:42-49,60-61,71-82`). A service that accepts oversized callback chunks, commits partial chunk state, mishandles a real commit error, or validates only the hash passes. Correction: add distinct oversized-chunk, chunk-INSERT, commit-boundary, stored-size/date, and root/previous-link corruption cases and prove no partial root/revision/receipt/chunk activation.

3. **HIGH — serialization and idempotency coverage is incomplete.** The contract requires concurrent initial submissions to yield one initial revision and fingerprints to include all command metadata plus the PDF hash (`specs/DEADLINE-TRANSFER-CERTIFICATE-001.md:27,35`). The only process race is between corrections (`tests/InstallationProcess/deadline_transfer_certificate_001_test.php:62-68`), while collision mutations cover deadline, source label/locator and bytes but omit certificate date, correction reason, case identity, expected version, and media type (`:29-30`). A broken initial lock or partial fingerprint can pass. Correction: add a fresh-case concurrent-initial witness and table-driven same-identity collisions for every fingerprint field that can otherwise remain valid.

4. **HIGH — authorization ordering and imported-role defaults are not demonstrated.** The specification requires authorization before callback **and case lookup**, and requires defaults to work for later imported roles (`specs/DEADLINE-TRANSFER-CERTIFICATE-001.md:13,23`). Lines 22-23 prove callback suppression only against an existing case; the missing-case assertion uses an authorized actor (`tests/InstallationProcess/deadline_transfer_certificate_001_test.php:41`). The fixture checks the static role catalogue and manually grants only fixed role IDs 1, 6 and 7 (`tests/Support/DeadlineCertificateFixture.php:9-13`), so an implementation that probes case existence before denial or fails to grant capabilities through the role-import owner passes. Correction: assert an unauthorized missing-case request returns `FORBIDDEN` without byte acquisition or distinguishable lookup, and exercise a later imported role through the public permission owner.

5. **HIGH — the Yii test leaves material route and disclosure requirements insensitive.** The HTTP contract requires a revision to belong to the requested object, missing revision 404, `private,no-store`, all relevant wrong-method boundaries, complete history metadata, and no PDF bytes in HTML/JSON/logs (`specs/DEADLINE-TRANSFER-CERTIFICATE-001.md:39`). The test covers one valid download, missing object, collection DELETE, reason/link visibility and merely `no-store` (`tests/Yii2/deadline_transfer_certificate_http_001_test.php:17-26`). It does not try a valid revision under another object or a missing revision, require `private`, exercise method boundaries on download, assert current deadline/document date/actor/time, or inspect disclosure sinks. Those regressions can pass. Correction: add cross-object and absent revision 404 cases, exact cache directives and download method checks, complete rendered history/current metadata, and bounded byte-canary assertions for HTML/JSON/log output.

6. **MEDIUM — several public result/error mappings remain unobserved.** The application contract names `INVALID_PDF`, `STORAGE_UNAVAILABLE`, and `OUTCOME_UNKNOWN`; Yii maps unavailable to 503 and requires missing revision 404. The HTTP test observes 422 and 409 but no invalid PDF, storage failure, ambiguous outcome, or missing revision mapping. The application test also treats missing revision as `CASE_NOT_FOUND` by passing `999999` (`tests/InstallationProcess/deadline_transfer_certificate_001_test.php:54`), but does not prove behavior for a nonexistent revision ID distinct from a case identifier. Correction: add deterministic adapter injections for invalid PDF/storage/unknown outcomes, and a clearly nonexistent revision-ID download assertion.

## Traceability, determinism, and RED evidence

The tests cite the specification identifier and use the public application and Yii HTTP seams. Worked values, hash/size expectations, replay/history assertions, real-process correction contention, role denials, and 20 MiB/320-chunk reconstruction are independently derived and useful. The isolated fixture and barrier are deterministic enough for the exercised cases.

The retained application RED record `1789408639071618000-7935ba05b54d4b75ac84466c0604210f` is source-bound with `source_drift=false` and fails at the intended absent `DeadlineTransferCertificates` public owner after canonical fixture setup. The retained Yii RED record `1789408672150959000-86026601b9954429baa02c5aea602ac2` is source-bound with `source_drift=false` and fails at the intended missing default certificate capabilities. Both are valid RED for behavior they reach, but fail before the untested acceptance boundaries above. Syntax checks for the reviewed spec/test/support files and `git diff --check` pass. A reviewer-local restored checkout lacked ignored `vendor/`, so no replacement execution evidence is claimed. `openspec validate ... --strict` reports an existing RFC-2119 warning in the parent calculation delta; this does not resolve or add to the certificate Gate 3 findings.

CI and deployment remain `UNKNOWN` and are not treated as approval or GREEN.

## Verdict

`CHANGES_REQUESTED`

Gate 4 is blocked. Return to Gate 2, add the bounded sensitivity and recovery cases above, capture fresh intended RED on one exact source, and resubmit the complete certificate package for independent review. Calculator, publication, money integration, deployment and backfill remain outside this certificate review.

---

## Gate 3 correction review — 2026-09-14

- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T184928Z-ac0bd8ac40/package.json`
- Exact reviewed source: reconstructible snapshot over base `79b3a4cecae558d74d781f353e967f03c036b921`, candidate source digest `ad36b4293cfb1d9febf7b4f9aac4c99b0d921d4f8de3b030c2963da3806c495a`, executable source digest `3d951369afa6b0716b7fe519066c632e25282da05d06e05de1f54ba3615abf14`
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T184928Z-ac0bd8ac40/snapshot`, patch SHA-256 `dd9fe1a130562ebcb29d65382a1ff765238906f3fda632b52a68131f947b1af6`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T184928Z-ac0bd8ac40/verification-plan.json`, SHA-256 `a269f3d4888712ac06173d3b2c9a9d0a0d9a5b7d410970a6442e5d393ec97d77`
- Independence is unchanged: this reviewer authored none of the corrected contract, tests, fixtures, or RED evidence.

### Prior finding resolution

- Prior finding 1 is resolved. The dedicated schema suite now covers exact columns, MariaDB types, nullability, defaults, collation, primary/unique keys, foreign keys, InnoDB, all four incompatible-preexisting-table refusals before DDL, clean v25 and additive v24-to-v25 migration, preservation of old rows/counters/grants, migration replay, real public fresh-owner provisioning, and fixed hashes for historical v22-v24 recovery profiles. The recovery suite builds the actual runtime image and performs real `mariadb-dump --hex-blob` backup/restore, comparing every v25 row and counter and reading the restored PDF/history through the public seam. The clarified contract explicitly does not require SQL CHECK constraints and correctly uses `MEDIUMBLOB`, since a `BLOB` cannot hold a permitted 65536-byte callback chunk.
- Prior finding 2 is resolved. Separate oversized callback chunk, revision insert, PDF-chunk insert, real connection loss at commit, before/after-commit observer, stored byte-size/date/previous-link/root-version/hash corruption, and no-partial-fact assertions now cover the declared failure boundaries.
- Prior finding 3 is resolved. A fresh-case process race covers concurrent initial submission, while valid fingerprint collisions now vary certificate date, deadline, source label/locator, case, expected-version/mode, correction reason, and PDF content. Media type has only one valid contract value; invalid alternatives are covered as command validation.
- Prior finding 4 is resolved for the corrected contract. Unauthorized missing-case submission proves authorization precedes lookup/bytes. Existing-role migration grants and later fresh provisioning are exercised through the named current permission owners; no hypothetical role-import feature remains in scope.
- Prior finding 5 is mostly resolved. Cross-object and absent revision downloads, all download methods, collection PUT/PATCH/DELETE, private/no-store/nosniff/attachment headers, ISO dates/times, current/history values, card return navigation, and PDF-byte canaries across pages, JSON/errors and available logs are covered.
- Prior finding 6 is resolved. Yii now covers unsafe PDF 422, real storage failure 503, DI-injected committed-unknown 503 plus same-identity recovery, and a distinct nonexistent revision ID.

### Remaining findings

1. **HIGH — client-supplied actor/case fields can still override the authenticated route context without failing the HTTP test.** The normative HTTP contract at `specs/DEADLINE-TRANSFER-CERTIFICATE-001.md:39` says the actor comes only from the session and the case is resolved from the object route. Every POST in `tests/Yii2/deadline_transfer_certificate_http_001_test.php` omits both `actorId` and `installationCaseId`. Consequently, an adapter that normally uses the session and route but trusts optional multipart overrides would pass all current assertions. This permits an OTIZ reader to submit `actorId=18` and write, or an FKR writer to attach a certificate to another case while posting to object 4512. Correction: add hostile multipart `actorId` and `installationCaseId` fields, including an OTIZ-session write attempt, and prove the session actor/route object exclusively determine authorization and persisted case.

2. **MEDIUM — exact command-shape and rendered actor-name requirements remain weakly tested.** The application contract at line 11 requires canonical lowercase UUIDv4, positive integer types, trimmed nonempty source strings with both 500-character limits, and nonempty correction reason up to 1000 characters. The invalid matrix at `tests/InstallationProcess/deadline_transfer_certificate_001_test.php:43` has only `BAD`, one string actor, empty sources, a 501-character label, and null correction reason; it does not distinguish uppercase/wrong-version UUIDs, string case/version IDs, whitespace-padded/whitespace-only sources or reason, locator overflow, reason overflow, or accepted exact boundaries. The Yii contract also requires actor ID **with the name**, while `tests/Yii2/deadline_transfer_certificate_http_001_test.php` searches only for the loose substring `18`, which may occur elsewhere on the page. Correction: add a compact table for those exact validation boundaries and assert the fixture actor's full name together with actor ID in the relevant history row.

### Corrected RED and verification evidence

All four fresh records are bound at start/end to exact source `ad36b4293cfb1d9febf7b4f9aac4c99b0d921d4f8de3b030c2963da3806c495a` with `source_drift=false`:

- application `1789411742765494000-b48147361c83488caa627aacc9c61263` fails for the absent certificate public owner;
- schema `1789411742775776000-33ea71ed47524243a590159716c1ca57` fails for the absent v25 migration;
- recovery `1789411742777215000-9713417e23724071be7195b50bb28ac1` fails for the absent v25 recovery profile;
- Yii `1789411742784324000-366d1426bafc405fa2ca607260d83341` fails for the absent default certificate capabilities.

These are valid intended RED failures after successful fixture setup for their respective boundaries. Syntax checks for the corrected contract/test/support files and `git diff --check` are GREEN. The CRITICAL verification plan includes all four acceptance commands plus governance, runtime-storage, architecture and one exact-source CI full suite. CI and deployment remain `UNKNOWN` and are not treated as approval or GREEN.

### Correction verdict

`CHANGES_REQUESTED`

Gate 4 remains blocked on the two bounded test-sensitivity gaps above. Preserve the resolved schema/recovery, storage, concurrency, authorization-order, HTTP mapping and disclosure coverage; capture fresh intended RED after correction and resubmit the exact source for independent review.

---

## Gate 3 final correction review — 2026-09-14

- Final package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T185603Z-31cfdf78c3/package.json`
- Exact reviewed source: reconstructible snapshot over base `79b3a4cecae558d74d781f353e967f03c036b921`, candidate source digest `4318e28caf06c9e25108a986284152007fefccfda90612d2f2e97d4f046609ea`, executable source digest `c088f6c4cf13309cbe43cba6b1ed70608a88edeacb34e2fe09f160e2f8fadcb3`
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T185603Z-31cfdf78c3/snapshot`, patch SHA-256 `812e8a1da8b7e3a9cb6ab55640ab6ba0f56203db25c318c8cea1d3b5f9ba7f25`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T185603Z-31cfdf78c3/verification-plan.json`, SHA-256 `160afa7fe8861935d86fbea1c0d9e46ed68d0166d07bd2a6bc483d677aa97b2f`
- Review scope remains only `specs/DEADLINE-TRANSFER-CERTIFICATE-001.md` and its certificate tests/support. The native-input specification and test present in the snapshot are outside this verdict. This reviewer authored none of the reviewed artifacts or evidence.

### Finding resolution

- The remaining HTTP authority finding is resolved. The initial FKR submission carries hostile `actorId=96` and `installationCaseId=999999`, then asserts the persisted revision is actor 18/case 6101 from the authenticated session and route. Each denied role, including OTIZ actor 96, submits forged actor 18 and remains forbidden. Later operations carry valid alternate case/actor values and the terminal database assertion proves no revision was attributed to another actor or case.
- The remaining command-shape and actor-display finding is resolved. The application matrix now distinguishes uppercase, non-v4 and invalid-variant UUIDs; string/float/nonpositive IDs and versions; invalid date types and zero date; leading/trailing/whitespace-only source and reason values; wrong media-type casing; locator and reason Unicode overflow; and accepted 500/1000-character Unicode boundaries plus a leap date on a fresh case. It also proves caller-transaction refusal without consuming the caller transaction, missing-case refusal before byte acquisition, and actor-scoped request identity. The Yii assertion selects the exact initial-revision history row and requires the stored actor's full name plus standalone actor ID 18 in that row.

### Complete assessment and RED evidence

No findings remain in the agreed certificate scope. Taken together, the application, schema, recovery and Yii tests trace the normative public seams, independently derive exact values, exercise rejection/no-mutation behavior, authorization, replay/collision/races, immutable history, bounded 20 MiB database chunking, v25 migration and recovery, and user return/download behavior. The tests remain deterministic and isolated from production systems. Parent calculation/publication/payment work, native-input work, deployment, import and backfill are not approved by this review.

Four fresh intended RED records are bound at start/end to source `4318e28caf06c9e25108a986284152007fefccfda90612d2f2e97d4f046609ea`, executable source `c088f6c4cf13309cbe43cba6b1ed70608a88edeacb34e2fe09f160e2f8fadcb3`, with `source_drift=false`:

- application `1789412143558171000-5269cd47b76c4a19bd901fab22820152` fails for the absent certificate owner;
- schema `1789412143558168000-a3bcc922d89c41799f9b354d50457d44` fails for the absent v25 migration;
- recovery `1789412143563178000-2da6e8a161344a938c72b4f155709961` fails for the absent v25 recovery profile;
- Yii `1789412143568507000-3f85e3f27b354b0d8db96710f9ee654d` fails for the absent certificate capabilities.

Each failure occurs after its relevant fixture setup and for missing production behavior. Syntax checks for every certificate spec/test/support file and `git diff --check` are GREEN. The CRITICAL plan retains all four focused acceptance commands, governance, runtime-storage, architecture and one exact-source CI full run. CI and deployment remain `UNKNOWN` and are not treated as approval or GREEN.

### Final verdict

`APPROVED`

Gate 3 passes for exact source `4318e28caf06c9e25108a986284152007fefccfda90612d2f2e97d4f046609ea`. Gate 4 may proceed against this reviewed certificate package. Any later certificate contract or expectation change requires a new Gate 2/3 cycle.

---

## Gate 3 implementation-era test delta — 2026-09-14

- Frozen package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T192613Z-9f7c30d127/package.json`
- Exact reviewed package source: reconstructible snapshot over base `79b3a4cecae558d74d781f353e967f03c036b921`, candidate `f38aa3426068aa9c53568d7c63a8ae46a6249c9ab1d0847409169253b7bb414f`, executable `fea23c5e52f0f8fee4bcc7f145c1dda9e33524ba95bc790d727270bec27616a5`
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T192613Z-9f7c30d127/snapshot/source.patch`, SHA-256 `c5d1797101ff1c03d797932f25aac819f29cd2e553f6859a15669a73d1a7c8df`
- Review scope: only the post-approval safe-filename assertion and `PreopeningFixture::start`/HTTP unknown-outcome setup delta. This reviewer authored neither delta.

### Assessment

No findings. Replacing the malformed filename regex with `strpbrk($filename, "/\\\r\n") === false` preserves the approved requirement while correctly detecting slash, backslash, CR and LF. The fixture extension stops and closes an existing PHP server, allocates a new port and unique router file, writes the optional complete bootstrap before process launch, and retains the original default bootstrap when no override is passed. The HTTP test uses this seam to start the lost-response observer before its first request, then restarts the ordinary server in `finally`; it no longer rewrites a router concurrently with a live server. This is deterministic setup plumbing and does not change certificate expectations or production behavior.

Retained HTTP record `1789413976265040000-b4c9932edc354554aa3d72d1a7fd6a60` is GREEN with exit 0, source/end source `f38aa3426068aa9c53568d7c63a8ae46a6249c9ab1d0847409169253b7bb414f`, executable source `fea23c5e52f0f8fee4bcc7f145c1dda9e33524ba95bc790d727270bec27616a5`, and `source_drift=false`. It prints `PASS DEADLINE-TRANSFER-CERTIFICATE-001 Yii flow`. Syntax and `git diff --check` are GREEN for the frozen delta.

### Delta verdict

`APPROVED`

The root-authored test-setup delta remains valid Gate 2 evidence. This approval does not approve the production implementation or evidence from a different source.

---

## Gate 3 six-finding correction review — 2026-09-14

- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T193422Z-54e7660ebc/package.json`
- Exact reviewed source: reconstructible snapshot over base `79b3a4cecae558d74d781f353e967f03c036b921`, candidate source `1f6090fbdc8934260bdfb8de49265cf8166d481243d0c357b8f188a84fbc4c58`, executable source `35f005246bc75dcd6e16d1cd892f930bd580776d0236b366a77b98f7f56f76cb`
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T193422Z-54e7660ebc/snapshot`, patch SHA-256 `55cd019e7541eeb630b89d73ae98b937edffe39fed7c46d64b5e58846a66e9e2`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T193422Z-54e7660ebc/verification-plan.json`, SHA-256 `2aa87a8eb3a81c6c1eaf6e7927da6088c3aed285892b57d4ca5b1a03cb37664a`
- Review scope: root-authored Gate 2 corrections for the six findings in `reviews/code/DEADLINE-TRANSFER-CERTIFICATE-001.md`. The unchanged recovery test and concurrent OTIZ work are outside this bounded correction review. This reviewer authored none of the reviewed artifacts or evidence.

### Assessment

No findings.

1. The schema test clones the valid four-table definition, independently introduces wrong primary key, missing unique key, missing FK, MyISAM engine, wrong collation, and a column default, and requires both `isCompleteCompatible()` refusal and `apply()` refusal before creation of the deliberately missing fourth table. This is sensitive to the full migration/readiness fingerprint defect and checks no-DDL behavior for same-column conflicts.
2. The application test requires `currentEvidence()` to equal the complete revision metadata plus its independently calculated source envelope, resolving the incomplete-result gap.
3. It corrupts the earlier revision's certificate date, deadline, revision number and previous link while a later revision remains current, and requires all three read seams to fail closed. This catches the misplaced per-revision lineage/date condition.
4. The HTTP test uses forbidden engineer/administrator sessions against existing and missing collection/download routes and requires uniform 403, directly detecting object resolution before authorization.
5. The contract now explicitly defines fingerprint fields in fixed contract order and ignores associative insertion order and undeclared transport-only fields. The test reverses the declared command array, adds an extra field, and requires replay of the original revision, giving the correction an independent semantic witness.
6. The application test sorts and compares exact download keys for both historical revisions, so the undeclared internal `revision` payload cannot remain exposed.

The corrections preserve the previously approved certificate matrix and do not weaken authorization, history, storage, recovery, or HTTP behavior. Expected values come from the normative contract and existing accepted revisions rather than the planned corrections.

### RED evidence

All three records start and end at source `1f6090fbdc8934260bdfb8de49265cf8166d481243d0c357b8f188a84fbc4c58`, executable source `35f005246bc75dcd6e16d1cd892f930bd580776d0236b366a77b98f7f56f76cb`, with `source_drift=false`:

- schema `1789414431583688000-79c4c3354ef748948b7cd23e1bd53df4` fails because the current fingerprint accepts the same-column wrong primary key;
- application `1789414431577319000-3b3883a8320e430a94f50a17d414b264` fails because reordered semantic replay returns `OPERATION_CONFLICT`;
- HTTP `1789414431571772000-94283c8d22a647ff9e8d3964333638db` fails because a forbidden missing-object request returns 404 instead of 403.

These are intended missing-behavior failures after fixture setup. The full-result, prior-lineage and exact-download assertions appear later in the application test and are structurally sensitive to their respective defects; one executable naturally stops at its first RED assertion. Syntax checks for the corrected certificate spec and three tests, plus `git diff --check`, are GREEN.

The bounded correction plan intentionally excludes unchanged recovery from this Gate 3 rerun. A fresh complete Gate 5 package must still run all four certificate checks at one exact source. Full CI and deployment remain `UNKNOWN`.

### Correction verdict

`APPROVED`

The six bounded corrections may proceed to implementation against exact source `1f6090fbdc8934260bdfb8de49265cf8166d481243d0c357b8f188a84fbc4c58`. Changed expectations or scope require another Gate 2/3 review; final Gate 5 and exact-source verification remain required.

---

## Gate 3 recovery compatibility delta — 2026-09-14

- Final package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T194550Z-0c3d0d1716/package.json`
- Exact source: candidate `719b1c81888252e0d199c94e58b0e3bfe0a3f563420cf246882a2c786032a1dc`, executable `612923bd364f53de61c46374016d3c7e7be7a3768c3fda673265f42565f8970e`, base `79b3a4cecae558d74d781f353e967f03c036b921`
- Scope: the root-authored recovery-test delta. This reviewer authored neither it nor its evidence.

### Assessment

No findings. The test exports the actual public `app/` and `bin/` trees from retained v24 commit `79b3a4ce` into a random directory outside the checkout and mounts them read-only. It proves that the historical public tool refuses a live v25 backup with exit 70 and no bundle publication or row/counter mutation, and refuses a v25 bundle restore with exit 67 before target DDL or private-state creation. It then proves the current tool restores the complete v25 table, binary-chunk and counter inventory and serves the restored certificate through public read/download seams.

Recovery record `1789415080604781000-5c28799772704ebdb967bd9114a099b4` is GREEN with exit 0 on the exact candidate/executable source and prints `PASS DEADLINE-TRANSFER-CERTIFICATE-001 recovery`. PHP syntax is GREEN.

### Delta verdict

`APPROVED`

This decision is confined to schema-v24/v25 recovery behavior and introduces no rapid-pilot compatibility requirement.

---

## Gate 3 schema-v26 integration delta — 2026-09-14

- Frozen root package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T200621Z-c02e97116a/package.json`
- Exact source: candidate `04c2a9529014ecf6a082112abdca2f3ad0e0adb9f94dbdd7aaad8bf2239207e4`, executable `4c19a6d379546d68b28d843c186d929f3660aac9b979c194f84a9771636f6a38`, base `d6daceea01cd5e4a6dd20b56921f767224cd1fb5`
- Snapshot patch SHA-256: `acf621d336caccc8c59d7b74e5adf35ab2c5b05fef94e0f56bc8009c4ca0b803`
- Scope: root-authored certificate schema/recovery and numerical current-frontier test changes only. Publication/native-input tests and later UI changes are excluded. This reviewer authored none of the reviewed expectations.

### Assessment

No behavioral test findings. The certificate schema test independently proves a populated upstream feedback v25 advances only through v26, preserves every v25 row and counter, adds the exact four certificate tables and one auto-increment owner, and pins the v25 profile file hash. The recovery test expects the exact v26 manifest inventory of 77 tables and 42 auto-increment owners while retaining the historical-tool refusal and current dump/restore witnesses. Current-frontier consumers use terminal version 26 and contiguous `range(1,26)`; literal recovery inventories include the four certificate tables and certificate revision counter. The eight new tests are registered in the suite/category inventories.

Independent runs from the restored snapshot are GREEN:

- `deadline_transfer_certificate_schema_001_test.php`
- `deadline_transfer_certificate_recovery_001_test.php`
- `jobs_schema_001_test.php`
- `production_schema_frontier_001_test.php`
- `otiz_settlement_schema_001_test.php`
- `yii2_clean_stand_provisioning_001_test.py`

These local runs are reviewer observations, not retained harness evidence. The package contains no retained records, and the checkout recovery harness result described as `UNKNOWN` remains `UNKNOWN`.

Several assertion descriptions still name older frontiers (for example “v24” or “version25”) after their executable values were correctly changed to 26. They reduce diagnostic clarity but do not weaken the asserted values. The exact 25 checks in the old demo adapter are known retained compatibility behavior outside this scope.

### Delta verdict

`APPROVED`

The root test delta is sensitive to the schema-v26 integration. The two contradictory normative prose references identified in the code-review disposition still require correction and a new exact-source package.

---

## Gate 3 schema-v26 final correction disposition — 2026-09-14

- Final package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T201555Z-63a287a5ba/package.json`
- Exact source: candidate `76539fec0ff2f9314a33e437cc5827abcefaa8fc65ea331665524daad8e24ecd`, executable `011b284d4a8eb03ee4a5ada7663c104869c848fd5ac4d11e043c024a83f9a541`, base `d6daceea01cd5e4a6dd20b56921f767224cd1fb5`
- Snapshot patch SHA-256: `0b5302c3f7ea61bfc3ac1121a8bbed832a778187dac1284c6dda2d7884c952f1`; plan SHA-256: `800749077465d8a14410906f33bd5bfc7ea08624b17dd49c8c940a8d4cb5d8f4`

No findings. The accepted root frontier delta consistently uses terminal schema 26 and complete ledgers, retains the byte-identical feedback v25 profile, composes the exact v26 77-table/42-counter recovery inventory, covers v23→24/25/26 forward migration, and registers all eight new tests. The two stale certificate-contract phrases are corrected exactly. Historical exact-25 demo checks remain outside this scope by owner decision and require no rapid-pilot compatibility work.

All eight new focused tests are exact-source GREEN. Certificate records are application `1789416961399559000-aab5b69a7a3e40de8385a7bc1014b494`, schema `1789416962568777000-0ac88614cacb4873a1e539c0017f9a7a`, HTTP `1789416963726836000-b5d69b5b6c94431da2e6fcccadd06f69`, and recovery `1789416964884520000-b0737c44e6c043a8a209340536542b28`. Each exits 0 and starts/ends at candidate `76539fec...` and executable `011b284d...`. Architecture record `1789416985558315000-f5c5e2648e364e408b8816151743d829` is likewise exact-source GREEN.

### Final delta verdict

`APPROVED`

The schema-v26 root test/frontier correction is accepted on the final exact source.
