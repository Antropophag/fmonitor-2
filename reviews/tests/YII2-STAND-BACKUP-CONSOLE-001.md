# Gate 3 review — YII2-STAND-BACKUP-CONSOLE-001

- Date: 2026-09-13
- Reviewer: independent `php_backup_reviewer`; authored none of the reviewed specification, OpenSpec artifacts, tests, or evidence.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T131402Z-6b672afd40/package.json`
- Reviewed reconstructible source: package candidate source `d4596661ecfebefd93f5c0728278221187cb6efb088cc412c017ee68ff62621b`, executable source `a3c3a927b54f795d2b6aa886e7df2bfdc93e510004cf181b9197ab90e73e96c5`, over base `404aa6858b8fdd61ac0e8151f09a67000a387ead`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T131402Z-6b672afd40/snapshot/source.patch`, SHA-256 `8bfec4ce6f34f3563b27d5b5cfe6aded256682e0a61e2766856c736ce2cebd39`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T131402Z-6b672afd40/verification-plan.json`, SHA-256 `506bd63187fbd4a165bc1d43ac84f81c6e9b4d960dbd1afde3b26d6776daf416`.

## Assessment

The transport test does invoke the real production entry point: `PHP_BINARY bin/yii` in an isolated subprocess. That is the correct public seam. The reviewed production paths are PHP/Yii2 paths, and the ownership test rejects the specifically retired `tools/delivery/stand-backup.py`. Python appears only in the external verification/Quality Graph harness in this candidate.

That correct seam choice is not enough to approve the submitted matrix. The executable tests exercise only argv rejection and a missing-manifest rejection, followed by lexical/file-existence ownership checks. They never create or verify a backup. Consequently an implementation that merely registers a PHP controller, returns the two expected errors, and contains appropriately named empty PHP files can pass every submitted assertion while omitting the backup protocol promised by the contract.

## Findings

1. **HIGH — Gate 1 does not define the promised backup behavior as observable, independently calculable acceptance cases.** Locations: `specs/YII2-STAND-BACKUP-CONSOLE-001.md:18-26`, `:30-32`. The contract lists exact-target admission, three independent byte hashes, a canonical content-addressed manifest, an atomic verified pointer, append-only outcomes, an exclusive lease, semantic replay, and crash-consistent failure ordering, but defines none of their input formats, canonical bytes/layout, success and failure envelopes/exits, persisted paths/facts, replay equivalence/conflict rules, lease behavior, or ordered fault outcomes. `verify` is named but its accepted/corrupt/missing/ambiguous cases are unspecified. Even the authorization condition for recording mode (`test-* project`) has no declared source or rejection envelope. These omissions prevent expected values from being derived independently and leave the executor free to choose behavior after Gate 3. Correction: return to Gate 1 and add a bounded acceptance matrix with worked fixture bytes and independently computed expected digests/manifest bytes, exact create/verify results and persisted facts, all relevant rejection reasons/exits, test-mode/project authorization, replay/conflict and lease cases, and explicit fault points with the required pre/post-state ordering.

2. **HIGH — Gate 2 has no success/bundle/verify coverage for the core slice.** Locations: `tests/Yii2/yii2_stand_backup_console_001_test.php:8-13`; normative behavior at `specs/YII2-STAND-BACKUP-CONSOLE-001.md:18-22`. The only behavior after argv validation is `create --manifest=/missing`, expected to return `TARGET_INVALID`. There is no isolated exact-target fixture, no successful `create`, no call to `stand-backup/verify --bundle-digest=...`, and no assertion over DB/artifact/session hashes, canonical manifest content/address, durable operation outcome, or atomic verified pointer. File existence at lines 5-6 does not make the test sensitive to any of these behaviors. Correction: drive both commands through `php bin/yii` against deterministic temporary fixtures and assert exact output plus exact filesystem facts/bytes derived without calling production helpers; corrupt each protected component and prove verify fails closed while the prior verified pointer and payload remain unchanged.

3. **HIGH — replay, concurrency/lease, and crash-consistent failure ordering are entirely untested.** Locations: `specs/YII2-STAND-BACKUP-CONSOLE-001.md:20-22`; both submitted Yii2 tests. There is no repeated operation-id case, semantic-same versus semantic-conflict case, overlapping subprocess/lease witness, injected filesystem/backup fault, or before/after inventory proving append-only outcomes and preservation of the previous verified bundle. Plausible regressions such as overwriting history, accepting a conflicting replay, publishing before verification, or advancing the pointer after a partial write all pass. Correction: add deterministic black-box replay and concurrent subprocess cases and a test-only recording/fault port reachable only under the specified authorization; for each material fault point assert exact terminal envelope, append-only outcome, absence/cleanup of partial publication as specified, and unchanged prior verified pointer.

4. **HIGH — production fail-closed wiring and output-safety promises are not covered.** Locations: `specs/YII2-STAND-BACKUP-CONSOLE-001.md:24-32`; `tests/Yii2/yii2_stand_backup_console_001_test.php:10-13`. No invocation without `FMONITOR_STAND_BACKUP_TEST_MODE=1` proves `PRODUCTION_DRIVER_UNAVAILABLE`; no non-`test-*` project proves recording-port denial; no runtime-wiring failure or injected Throwable proves exit `64 CONFIGURATION_INVALID` and redaction. The two redaction canaries are not the actual `/missing` argument, and there are no credential, payload/native-fragment, or Throwable canaries. The exact single-JSON/newline/empty-stderr contract is asserted only for a small rejection subset. Correction: cover production wiring, authorization boundaries, malformed environment/runtime configuration, and deterministic Throwable/native-payload failures at the real Yii seam, with exact envelopes and canary redaction on every terminal class.

5. **MEDIUM — the no-Python/no-rapid-pilot ownership guard is too narrow to prove the stated candidate-wide production boundary.** Locations: `specs/YII2-STAND-BACKUP-CONSOLE-001.md:13-14,35-36`; `tests/Yii2/yii2_stand_backup_ownership_001_test.php:5-9`. The test scans only three planned PHP files and one exact Python pathname. It passes if console configuration, another included PHP file, a shell wrapper, or a differently named Python artifact becomes the reachable backup implementation. It also accepts a thick controller because it checks only registration and filenames. Correction: retain the lexical checks as supplemental guards, but add a reachable composition/load-closure assertion for the registered `stand-backup` command and candidate-wide production entrypoint inventory that excludes Python and `rapid-pilot`; add a narrow ownership witness showing the controller delegates protocol work to `app/RuntimeRestore` rather than implementing it itself.

## Evidence

The two retained intended-RED records are source-bound to candidate source `337f84a4ff072a29e89e57e0be050775b9be2c03c2d8e32e2c4123c9980d91f6` and executable source `a3c3a927b54f795d2b6aa886e7df2bfdc93e510004cf181b9197ab90e73e96c5`, with matching end source. They fail with exit 255 on the intentionally absent PHP production files. This is valid evidence that the proposed files are missing, but not RED evidence for the unasserted bundle, verify, replay, lease, failure-ordering, or production-wiring behavior. The verification inventory control is GREEN and `git diff --check` is clean; neither closes the behavioral gaps.

Harness state reports PR/CI/deployment as `UNKNOWN`. They are not treated as GREEN, approval, or deployment authorization.

## Verdict

`CHANGES_REQUESTED`

Gate 4 is blocked. Return to Gates 1 and 2, make the exact bundle/failure/replay/concurrency/crash contract observable, add sensitive real-`php bin/yii` black-box tests, capture fresh intended RED on one exact source, and resubmit the complete package for independent Gate 3 review.

---

## Correction review — 2026-09-13

- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T132021Z-ee3edc82ae/package.json`.
- Exact reviewed source: reconstructible snapshot over base `404aa6858b8fdd61ac0e8151f09a67000a387ead`, candidate source `405c82f2bc6cd76c3ba50ce8d8d3940e93e96564e83f01085eacc7fc753c3e37`, executable source `c558c311baec58911f0585d18da44df28ded1c43cf3c4367130ecbdf3327a47f`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T132021Z-ee3edc82ae/verification-plan.json`, SHA-256 `d0f6c76796ede6ec0c467a92998ffb34f47d501fd3475f06a340dfbedfa747a8`.
- Reviewed delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T132021Z-ee3edc82ae/delta.patch`.
- Independence is unchanged; this reviewer authored none of the correction artifacts or retained evidence.

### Resolution assessment

- Prior findings 2 and 3 are resolved at the test-artifact level. The correction restores a substantial target/bundle/verify/preservation/replay/concurrency/fault matrix. `tests/Support/stand_backup_contract.py:113-143` constructs an external subprocess whose executable command is `php <repository>/bin/yii stand-backup/create|verify`; it independently computes canonical JSON and SHA-256 values and observes stdout, stderr, exit status, external-effect records, and filesystem facts. The Python code is a black-box fixture/runner rather than the production backup seam.
- Prior finding 4 is substantially resolved by production-driver and fixture-authorization cases plus native/fault canaries and exact single-object output checks.
- Prior finding 5 is partly resolved: the new architecture test adds runtime-boundary and destructive-lifecycle guards, while the retained PHP ownership test checks registration. The production candidate still contains no Python backup implementation path; the only newly added Python is under `tests/`.
- Six fresh RED records for transport, target, bundle, preservation, replay, and architecture plus the PHP ownership RED are bound at start and end to source `405c82f2bc6cd76c3ba50ce8d8d3940e93e96564e83f01085eacc7fc753c3e37`, with executable source `c558c311baec58911f0585d18da44df28ded1c43cf3c4367130ecbdf3327a47f`. They fail on the intentionally absent PHP/Yii2 owner/controller. This is valid missing-seam RED. Verification inventory is GREEN at the same source.

### Remaining finding

1. **HIGH — Gate 1 traceability remains unresolved: the normative specification was not corrected, while the test suite invents the material protocol.** The package binds `specs/YII2-STAND-BACKUP-CONSOLE-001.md` at the same SHA-256 `e63246d887bb45d812abcd11b06e7a3740813162e2063ebf1be452e45a49ba0d` as the returned package. Its lines 18-32 still provide only names for the required properties. In contrast, the new tests choose previously unspecified public facts: target manifest fields and authorization literal, canonical JSON algorithm, exact bundle member names and restore-manifest schema, pointer schema, operation-history schema, exit codes/reasons, replay equivalence, lease representation, and named durable/fault phases. The test docstrings additionally cite nonexistent `YII2-STAND-BACKUP-BUNDLE-001`, not the bound `YII2-STAND-BACKUP-CONSOLE-001`. No normative artifact in this package contains the asserted exact protocol. Therefore those expected values cannot be traced to the accepted specification or independently reviewed worked examples; an implementation could conform to the short contract while failing these tests, or be driven by test-authored design. Correction: amend the normative spec with the bounded exact A2-A5 contract now asserted (or explicitly cite and include an already approved stable normative contract if one exists), including schemas, canonical bytes, envelopes/exits, persisted facts, replay/lease semantics, and fault ordering; correct every test citation to `YII2-STAND-BACKUP-CONSOLE-001`; bind the changed spec and fresh tests/evidence in a new exact-source package.

### Correction verdict

`CHANGES_REQUESTED`

Gate 4 remains blocked only because the behavioral oracle has not been made normative and traceable. The launcher/seam correction itself is sound: tests run real `php bin/yii`, and Python remains outside production as the black-box harness. After the exact matrix is recorded in the bound specification and the stale spec identifiers are corrected, capture fresh intended RED and resubmit for narrow Gate 3 review. CI and deployment remain `UNKNOWN` and are not treated as approval or authorization.

---

## Final narrow rereview — 2026-09-13

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T132502Z-872c957668/package.json`.
- Exact reviewed source: reconstructible snapshot over base `404aa6858b8fdd61ac0e8151f09a67000a387ead`, candidate source `a45d3ae349f8ac946fc5697083bc616fecc310743aeeb84ddda1ebae4f98ceb1`, executable source `82055cf5c3b48377a9a4bc5931f56e83139047a4cc5a8a81ce213043423ed608`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T132502Z-872c957668/snapshot/source.patch`, SHA-256 `8e473aeee6657233246465719ba21efdc4e02f4315e8c7e6e922c58137dba344`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T132502Z-872c957668/verification-plan.json`, SHA-256 `26b34c8391065bdf572ecbc75e3bdde9a6b3d0de217115408956b8a0ea936e18`.
- Reviewed delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T132502Z-872c957668/delta.patch`.
- Independence remains unchanged; this reviewer authored none of the specification, tests, corrections, or retained evidence.

### Finding disposition

The sole remaining traceability finding is resolved. The bound normative contract now has SHA-256 `08697e366dcdf00da9ff93660e83e1a3f2e48a5a759172c8fba32d61fbb95348` and explicitly defines the PHP/Yii public seam and recording authorization, exact-target rejection frontier, canonical target and restore serialization, three bundle members and independently recomputed hashes, bundle/pointer publication order, failure and UNKNOWN preservation, append-only operation records, semantic replay/conflict, lease/concurrency behavior, crash recovery boundaries, safe envelopes, and verifier traversal/corruption rules. These requirements trace directly to the previously reviewed target, bundle, preservation, replay, and boundary assertions; expected bytes and digests remain independently computed by the external test fixture.

All affected Python test/support docstrings now cite `YII2-STAND-BACKUP-CONSOLE-001`. The full matrix still invokes `php <repository>/bin/yii stand-backup/create|verify`; Python only arranges isolated fixtures and observes the public process/filesystem boundary. No Python backup production seam is introduced, and the PHP ownership plus architecture guards retain the prohibition on `rapid-pilot`, Python runtime ownership, destructive lifecycle, and the retired `tools/delivery/stand-backup.py` path.

Fresh intended-RED records for the two PHP tests and five black-box/architecture tests are bound at start and end to candidate source `a45d3ae349f8ac946fc5697083bc616fecc310743aeeb84ddda1ebae4f98ceb1` and executable source `82055cf5c3b48377a9a4bc5931f56e83139047a4cc5a8a81ce213043423ed608`. They fail because the PHP/Yii controller and owner are intentionally absent, not because of fixture setup. The verification inventory is GREEN at that source, and `git diff --check` is clean.

No new findings. CI and deployment remain `UNKNOWN`; this Gate 3 decision neither treats them as GREEN nor authorizes live backup or deployment.

### Final verdict

`APPROVED`

Gate 3 passes for exact source `a45d3ae349f8ac946fc5697083bc616fecc310743aeeb84ddda1ebae4f98ceb1`. Gate 4 may proceed against this reviewed contract and matrix. Any later change to normative expectations or executable tests requires a new Gate 2/3 review.

---

## Gate 5 correction test review — 2026-09-13

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T135650Z-ccc7117a49/package.json`.
- Exact reviewed source: reconstructible snapshot over base `404aa6858b8fdd61ac0e8151f09a67000a387ead`, candidate source `83d55ca73332a1e62546aaac5f9e27931063451d36045aea8d8aa8475b9ac9ac`, executable source `abcbde8d456960e34b40e0c094c5ab02b49b19abf91a45b0e341d04c3779cf37`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T135650Z-ccc7117a49/snapshot/source.patch`, SHA-256 `694a28a3376fcf46995914afba977e71971026b63f6f079d5fc8dfd3da46b4da`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T135650Z-ccc7117a49/verification-plan.json`, SHA-256 `d96517cee39eaeda968dce34de57ee0f2ad895d8106547a5c454631e7558bb52`.
- Reviewed delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T135650Z-ccc7117a49/delta.patch`.
- Scope is limited to the two regression groups requested by the Gate 5 findings and their verification binding. Reviewer independence is unchanged.

### Assessment

No findings.

The durable-UNKNOWN regression is sensitive to the exact code defect. It first creates a real `OUTCOME_UNKNOWN` through `php bin/yii`, snapshots the complete evidence tree and driver effects, then repeats the same operation twice and requires byte-identical public results with byte-identical lease, staging, history, and effects. It finally submits another operation and requires exit 75 `LEASE_HELD`, again with no mutation or driver call. The current source fails because the first replay removes `lease.json`; a correction that merely avoids a repeated driver during the immediate replay but drops the mutual-exclusion barrier will still fail the subsequent operation assertion.

The filesystem regression covers both vulnerable operations at the public seam. A pre-existing `verified.json.tmp` symlink targets an external canary, exercising the predictable atomic-write pathname; the member-swap fixture requests replacement of `database.sql` after metadata inspection, exercising the check/use gap rather than an already-static symlink. Both cases require a non-success definite/UNKNOWN outcome, unchanged external bytes, and no verified pointer. This is appropriately black-box: the Python fixture only arranges the adversarial filesystem state and launches the real PHP/Yii command; the correction must live in the PHP filesystem/application boundary. The cases are bounded to the normative no-follow, fail-closed, and no-destructive-effect requirements and do not introduce live backup or deployment behavior.

Retained record `1789307725059183000-a6c9fac61b7949b59c6f1cd4e8ac3e3a` is `INTENDED_RED` with no source drift and fails at the evidence-tree equality because the lease disappears on replay. Retained record `1789307725059880000-a5df13e3fc364b34900d384b01bd5c16` is `INTENDED_RED` with no source drift and fails because the current hostile filesystem path returns success. Both records are bound to source `83d55ca73332a1e62546aaac5f9e27931063451d36045aea8d8aa8475b9ac9ac` and executable source `abcbde8d456960e34b40e0c094c5ab02b49b19abf91a45b0e341d04c3779cf37`. All six unaffected transport, target, preservation, architecture, ownership, and verification-inventory controls are GREEN at the same source. `git diff --check` is clean.

CI and deployment remain `UNKNOWN`; they are not treated as GREEN or authorization.

### Correction test verdict

`APPROVED`

The two Gate 5 corrections may proceed against this exact reviewed test delta. Both changed suites and the complete focused plan must be GREEN on the corrected exact source before independent Gate 5 rereview; any expectation change requires another Gate 2/3 review.

---

## Editorial replay-test correction review — 2026-09-13

- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T141211Z-24515a2811/package.json`.
- Exact source: candidate `1305d614eae35f8950c9893c0f8714f0b1a86b0f1c7ea076b8eaa27973c33448`, executable `43d332d669a3484df59d9684d2ed46b11093f505093135e0343da78c86f69b27`.
- Scope: the one-line replay-test correction from the undefined/stale `evidence` name to the already captured `before` evidence snapshot. Reviewer independence is unchanged.

No findings. The correction removes a test-code `NameError`/wrong-scope reference without changing the approved behavior, public seam, inputs, expected outcome, or sensitivity: the assertion still requires the exact evidence tree captured after durable UNKNOWN to remain unchanged after the conflicting replay. The complete replay suite is GREEN in exact-source record `1789308605631281000-bd2538c98ad844fd85ad425721f999a9`; all other focused records are also GREEN at the same source.

### Editorial correction verdict

`APPROVED`

The corrected replay test remains an approved Gate 2 artifact for this exact source. This approval does not decide Gate 5 or treat CI/deployment `UNKNOWN` as GREEN.

---

## Atomic exclusive-create failure test review — 2026-09-13

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T141733Z-c11b431e20/package.json`.
- Exact reviewed source: reconstructible snapshot over base `404aa6858b8fdd61ac0e8151f09a67000a387ead`, candidate source `b75bf8a7974289a15ac368b4f24e8408b005a271d31282f63ea097a7f366cc20`, executable source `8c79af08b95fb3f22b02ae72e5ec7ca5c0a0dc47ce6d62ca7599cb2c0729fe70`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T141733Z-c11b431e20/snapshot/source.patch`, SHA-256 `f71ff19dfa223383df756656bfbab62a3e27e180a812ca40039e265b823b956e`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T141733Z-c11b431e20/verification-plan.json`, SHA-256 `a763d88e05ffcfc274a81e320a9e6693d52087a6d21927726e02b2cf51f1ad64`.
- Reviewed delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T141733Z-c11b431e20/delta.patch`.
- Review scope is the single persistent exclusive-temp failure regression and its verification binding. Reviewer independence is unchanged.

No findings. `test_persistent_exclusive_temp_failure_returns_safe_bounded_outcome` drives a normal admitted create through the real PHP/Yii subprocess while the authorized recording fixture forces the atomic exclusive-create operation to fail persistently. It requires the public call to return within two seconds, use a nonzero safe `BACKUP_INVALID`/`OUTCOME_UNKNOWN` envelope, and leave `verified.json` unpublished. This is sensitive to the Gate 5 defect: an unbounded retry cannot satisfy the wall-clock assertion, while treating a permanent open failure as success cannot satisfy the exit/outcome and publication assertions. The fixture flag is confined to the already authorized external recording port and does not alter production behavior or introduce a Python application seam.

Record `1789308926181654000-6673d19081e147c894ffbd3b30010fc2` is `INTENDED_RED`, exact-source bound with no source drift. The current implementation does not produce the required bounded failure outcome. The other seven transport, target, preservation, replay, architecture, ownership, and verification-inventory records are GREEN at the same candidate/executable source. `git diff --check` is clean.

CI and deployment remain `UNKNOWN`; they are not treated as GREEN or authorization.

### Atomic failure test verdict

`APPROVED`

The bounded exclusive-temp correction may proceed against this exact reviewed test. The changed bundle suite and complete focused plan must be GREEN on the corrected exact source before final Gate 5 rereview.

---

## Post-CI unreadable-observer correction review — 2026-09-13

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T144658Z-5651625987/package.json`.
- Exact reviewed source: reconstructible snapshot over commit `fbec3d5257c270f57c72f11b7b2d1fc40a1416a5`, candidate source `1012b628df2abf94bdaeccfd93d19c18bf9fd4a25bb4bb807aa72570db757428`, executable source `c2b87416c0fca8851ff621be75c515b22fc5fa589bdfe617fc6a1a4c268ceb27`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T144658Z-5651625987/snapshot/source.patch`, SHA-256 `f13fce534eb1b24fe8f9d1726877d70c8d76e5027b6c40dbc483550777e9546e`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T144658Z-5651625987/verification-plan.json`, SHA-256 `585f9783d32aaf67b68c4e423664bf50f662ebfdf1b102fac2b23e1d4b00c433`.
- Reviewed delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T144658Z-5651625987/delta.patch`.
- Review scope is the test observer in `tests/Support/stand_backup_contract.py`; reviewer independence is unchanged.

No findings. For an ordinary readable file `tree()` still records exact bytes. For a symlink it still records the link target without following it. For a mode-`000` regular file it now records the unreadable classification and exact size from metadata instead of calling `read_bytes()`. Thus the test continues to distinguish missing, changed type, changed permissions, and changed size while remaining portable when CI runs as an unprivileged user. The change does not weaken the production verifier expectation: the public command must still reject the unreadable member and preserve the complete evidence tree; it only prevents the external observer itself from raising `PermissionError` before making that assertion.

Quality Graph run `34762933379` is retained as FAILED: its full inventory had only the e2e unreadable-member `PermissionError`; integration, unit, fast, and governance jobs succeeded, while the aggregate verify job failed because e2e failed. This is coherent evidence for an observer portability defect, not a production acceptance failure, but the failed run is not reclassified as GREEN.

All eight current mapped records are GREEN at exact source `1012b628df2abf94bdaeccfd93d19c18bf9fd4a25bb4bb807aa72570db757428`, including the complete bundle suite with the unreadable case. `git diff --check` is clean.

### Observer correction verdict

`APPROVED`

The corrected observer remains a valid Gate 2 artifact. A new full exact-source Quality Graph run is still required; run `34762933379` is historical failed evidence, not approval.
