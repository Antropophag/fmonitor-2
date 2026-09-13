# Independent Gate 5 code review — YII2-STAND-BACKUP-CONSOLE-001

- Review date: 2026-09-13.
- Reviewer: separately tasked `php_backup_reviewer`; authored none of the normative contract, tests, Gate 4 implementation, or verification evidence.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T134758Z-c638302eda/package.json`.
- Exact reviewed source: reconstructible snapshot over base `404aa6858b8fdd61ac0e8151f09a67000a387ead`, candidate source `9f97509fd3164768ff4b54b537510c2a27cb3696776f4892338bac9c3b8e9c71`, executable source `e86c7fc0832abcd94b5d9d2dc1c9f17772bf031bd8b4dfe4b2b9152e00cbbb05`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T134758Z-c638302eda/snapshot/source.patch`, SHA-256 `a17c948f50a3057bbbe040b7c9961d19a28901b0a6f34b1f783f07f6f9cdaebc`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T134758Z-c638302eda/verification-plan.json`, SHA-256 `4acd4326789ac8fc38780cf4882c1f973215ab3a792f504589daa3c342d03132`.
- Gate 3 approval: `reviews/tests/YII2-STAND-BACKUP-CONSOLE-001.md`, final narrow rereview source `a45d3ae349f8ac946fc5697083bc616fecc310743aeeb84ddda1ebae4f98ceb1`, verdict `APPROVED`.

## Findings

1. **HIGH — replay of a durable `OUTCOME_UNKNOWN` removes the lease that the contract requires to remain held.** Locations: `app/RuntimeRestore/StandBackupApplication.php:18,30-31`; contract `specs/YII2-STAND-BACKUP-CONSOLE-001.md:83-97`. Timeout/interrupt/effect-unknown appends a durable `OUTCOME_UNKNOWN` record and correctly leaves the lease. On the next same-operation invocation, `replay()` skips bundle verification because the outcome is not success, then unconditionally calls `releaseLease()`, deletes the matching lease, and returns the old UNKNOWN result. The contract says UNKNOWN preserves the lease and does not permit an automatic repeated effect; after this replay a later invocation can reacquire the lease and call the driver again (the duplicate history check does not retain the removed mutual-exclusion barrier). This breaks the crash/ambiguity safety invariant despite all current tests being GREEN. **Correction:** never release a lease when replaying `OUTCOME_UNKNOWN`; restrict replay cleanup to the explicitly recoverable durable success and durable definite-failure cases. Add a public-seam regression witness: produce a durable UNKNOWN, replay it one or more times, prove byte-identical outcome/history/lease/staging and zero new driver effects, then prove another create remains blocked. Because this exposes a test-sensitivity gap, the new test requires Gate 2/3 approval before correction Gate 5.

2. **HIGH — the filesystem boundary does not implement the contract's no-follow guarantee and uses a predictable, overwrite-following atomic temporary path.** Locations: `app/RuntimeRestore/StandBackupFilesystem.php:9-11`; uses throughout `StandBackupApplication.php:27-32`; contract `specs/YII2-STAND-BACKUP-CONSOLE-001.md:41-46,56-62,100-112,121-130`. `regularBytes()` performs `lstat()` and then a separate `file_get_contents()`. An entry can be exchanged for a symlink between those calls, causing PHP to follow it and read outside the admitted bundle/evidence root; the same check/use race exists around directory enumeration and later member reads. `atomic()` always opens `<destination>.tmp` with mode `wb`, so an already present symlink at that predictable name is followed and truncated before rename. This can disclose external bytes into a manifest/digest or overwrite an external file, contradicting fail-closed no-destructive-effect and non-symlink child requirements. Static symlink fixtures pass because they do not exercise replacement between check and open or a hostile `.tmp`. **Correction:** move reads/writes behind a filesystem port that obtains a no-follow handle atomically (or verifies the opened handle identity against the parent entry without a replaceable gap), and create atomic temporary files with exclusive unpredictable names in the verified parent, rejecting all pre-existing/symlink entries before same-filesystem rename. Add deterministic seam-level race/hostile-temp witnesses proving no external read/write and unchanged verified state; independently review the test delta.

## Conformance and evidence assessment

Apart from these findings, the implementation follows the bounded architecture. `bin/yii` reaches the registered Yii controller; raw argv is validated by a thin adapter and protocol/state ownership resides in `app/RuntimeRestore`. The PHP owner independently computes canonical bytes and hashes, validates target identity, serializes append-only outcomes, publishes bundle then record then pointer, maps errors to closed JSON, and contains no Python or `rapid-pilot` dependency. Python remains confined to external black-box tests. Production recording is guarded by test mode, test project, and external fixture location; the non-recording port fails closed without a live backup or deployment action.

The package contains eight focused records, all `GREEN` and bound to candidate source `9f97509fd3164768ff4b54b537510c2a27cb3696776f4892338bac9c3b8e9c71` and executable source `e86c7fc0832abcd94b5d9d2dc1c9f17772bf031bd8b4dfe4b2b9152e00cbbb05`: Yii transport, exact-target admission, concrete bundle/verify, preservation, replay/concurrency, runtime boundary, ownership, and verification inventory. `git diff --check` is clean. Those results demonstrate the reviewed matrix but do not cover the UNKNOWN-replay lease loss or check/use symlink/temporary-file attacks above.

Harness reports PR/CI and deployment `UNKNOWN`. They are not treated as GREEN, merge, approval, or authorization for live backup/deployment.

## Verdict

`CHANGES_REQUESTED`

Gate 5 is blocked for exact source `9f97509fd3164768ff4b54b537510c2a27cb3696776f4892338bac9c3b8e9c71`. Return the two test-sensitivity gaps through Gate 2/3, correct the PHP replay and filesystem boundaries, capture fresh exact-source focused GREEN evidence, and resubmit the complete corrected production delta for independent Gate 5 review.

---

## Gate 5 correction rereview — 2026-09-13

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T141211Z-24515a2811/package.json`.
- Corrected exact source: reconstructible snapshot over base `404aa6858b8fdd61ac0e8151f09a67000a387ead`, candidate source `1305d614eae35f8950c9893c0f8714f0b1a86b0f1c7ea076b8eaa27973c33448`, executable source `43d332d669a3484df59d9684d2ed46b11093f505093135e0343da78c86f69b27`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T141211Z-24515a2811/snapshot/source.patch`, SHA-256 `31ea6d50c2677e2ee5d2e73eb7f6a2899a055d5e7b08f6e722930495da7bf616`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T141211Z-24515a2811/verification-plan.json`, SHA-256 `e248427b547984a3d311931be7640cbd20d547c9ff95786ff3ec045ccef27c47`.
- Reviewed delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T141211Z-24515a2811/delta.patch`.
- Gate 3 correction approval: `reviews/tests/YII2-STAND-BACKUP-CONSOLE-001.md`, sections “Gate 5 correction test review” and “Editorial replay-test correction review.” Reviewer independence is unchanged.

### Prior finding disposition

- Prior finding 1 is resolved. `replay()` returns a validated durable `OUTCOME_UNKNOWN` record without entering lease release. The approved test proves two identical replays preserve lease, staging, append-only history and effects, then proves a different operation remains `LEASE_HELD`.
- Prior finding 2 is substantially resolved. `regularBytes()` opens the candidate, compares the opened descriptor's device/inode to both pre-open and post-open directory entries, and reads only after identity/type agreement; the deterministic swap-after-lstat test is GREEN without reading or changing the external canary. `append()` similarly delays seek/write until descriptor and entry identity agree. `atomic()` no longer writes through the predictable `<destination>.tmp`: it rejects that hostile entry and uses a random same-directory name opened with exclusive `x+b`. The approved hostile-temp test is GREEN and the external canary remains unchanged.

### New finding

1. **HIGH — exclusive temporary creation can loop forever on a persistent filesystem failure.** Location: `app/RuntimeRestore/StandBackupFilesystem.php:14`. `atomic()` uses `do { random name; fopen($t, 'x+b'); } while ($f === false)`. `fopen` returns false not only for an astronomically unlikely name collision, but also when the directory becomes read-only/unwritable, is removed/replaced, exhausts space/inodes, hits descriptor limits, or encounters another persistent I/O error. In all such cases the command spins indefinitely instead of returning a bounded safe definite/UNKNOWN outcome, violating the contract's requirement that every filesystem exception terminate safely and undermining crash recovery/operator control. The current hostile-temp test covers an occupied legacy name but not a failed exclusive create. **Correction:** bound retries to a small explicit collision budget and distinguish/reject persistent failure (for example, retry only when the generated entry demonstrably exists; otherwise throw immediately), cleaning any created temp before propagating. Add a deterministic recording-port witness that forces exclusive-temp creation failure and requires prompt nonzero safe output, unchanged external/published state, and no leaked temp. Because this is a new test-sensitivity gap, route the test through Gate 2/3 before Gate 5 rereview.

### Evidence and verdict

All eight focused records are GREEN and exact-source bound: transport, target, bundle/filesystem correction, preservation, replay correction, architecture, PHP ownership, and verification inventory. `git diff --check` is clean. This evidence resolves both original findings but does not exercise permanent exclusive-create failure.

CI and deployment remain `UNKNOWN`; they are not treated as approval, merge, GREEN, or live-operation authorization.

`CHANGES_REQUESTED`

Gate 5 remains blocked for exact source `1305d614eae35f8950c9893c0f8714f0b1a86b0f1c7ea076b8eaa27973c33448` only on bounded exclusive-temp failure handling. Approve a sensitive correction test independently, make the minimal PHP correction, obtain fresh exact-source GREEN evidence, and resubmit for narrow Gate 5 review.

---

## Terminal Gate 5 rereview — 2026-09-13

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T142822Z-a24aaeaed1/package.json`.
- Exact reviewed source: reconstructible snapshot over base `404aa6858b8fdd61ac0e8151f09a67000a387ead`, candidate source `59c95d2bc7935c038ca27d5cecdda3e2490c6b5b8f8c29bb53754304788f9b4a`, executable source `c876e11d8633b1ecc1e54d7402942e17dc06f959c77d318db3f289442f8a0944`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T142822Z-a24aaeaed1/snapshot/source.patch`, SHA-256 `2494265575f7d6e46259eea763aae458f156d1139d462f24e252a7e1b17be25f`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T142822Z-a24aaeaed1/verification-plan.json`, SHA-256 `a53a7ae6ff09244c717e0a3a774913fa6c744d7a2508cc4f53906f61d6ae3190`.
- Reviewed delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T142822Z-a24aaeaed1/delta.patch`.
- Gate 3 correction approval: `reviews/tests/YII2-STAND-BACKUP-CONSOLE-001.md`, “Atomic exclusive-create failure test review,” source `b75bf8a7974289a15ac368b4f24e8408b005a271d31282f63ea097a7f366cc20`, verdict `APPROVED`.
- Reviewer independence is unchanged.

### Complete finding disposition

No findings.

The final bounded-atomic finding is resolved. `StandBackupFilesystem::atomic()` permits at most four exclusive random-name attempts. A failed `x+b` open is retried only when the generated path now has an entry, i.e. the collision/race case; when no entry exists the failure is treated as persistent and propagated immediately. Four collisions also terminate with an exception. The authorized recording fault is raised before an open attempt and reaches the existing safe `OUTCOME_UNKNOWN` publication-phase mapping. Created handles are closed and owned temporary files are removed on later write/fsync/rename errors. Thus permission, capacity, descriptor, removed-directory and other persistent errors cannot spin indefinitely, while the temporary name remains unpredictable, same-directory, and exclusively created.

The full candidate retains the earlier corrections: durable `OUTCOME_UNKNOWN` replay returns the validated record without releasing its lease; another operation remains blocked. Member reads and append writes compare the opened handle identity/type against pre/post directory entries before reading or writing. Hostile legacy temp symlinks are rejected without external mutation, and verified pointer publication uses the exclusive random temporary entry. The Yii controller remains a thin argv/JSON adapter over the PHP `app/RuntimeRestore` owner. No production Python or `rapid-pilot` seam, live restore/deployment action, or destructive lifecycle was introduced.

All eight package records are `GREEN`, have no source drift, and are bound to candidate source `59c95d2bc7935c038ca27d5cecdda3e2490c6b5b8f8c29bb53754304788f9b4a` and executable source `c876e11d8633b1ecc1e54d7402942e17dc06f959c77d318db3f289442f8a0944`: Yii transport, exact target, bundle/verify including bounded atomic and hostile filesystem cases, preservation, replay/concurrency, architecture boundary, PHP ownership, and verification inventory. `git diff --check` is clean. The normative contract remains bound at SHA-256 `08697e366dcdf00da9ff93660e83e1a3f2e48a5a759172c8fba32d61fbb95348`.

Harness reports PR/CI and deployment `UNKNOWN`. They are not treated as GREEN, merge, or authorization; live backup/deployment remains outside this review.

### Terminal verdict

`APPROVED`

Gate 5 passes for exact source `59c95d2bc7935c038ca27d5cecdda3e2490c6b5b8f8c29bb53754304788f9b4a`. Root must preserve the reviewed bytes when committing/publishing and obtain the required exact-source CI before merge-ready status. Deployment remains separately unauthorized.

---

## Post-CI correction Gate 5 review — 2026-09-13

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T144658Z-5651625987/package.json`.
- Exact reviewed source: reconstructible snapshot over commit `fbec3d5257c270f57c72f11b7b2d1fc40a1416a5`, candidate source `1012b628df2abf94bdaeccfd93d19c18bf9fd4a25bb4bb807aa72570db757428`, executable source `c2b87416c0fca8851ff621be75c515b22fc5fa589bdfe617fc6a1a4c268ceb27`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T144658Z-5651625987/snapshot/source.patch`, SHA-256 `f13fce534eb1b24fe8f9d1726877d70c8d76e5027b6c40dbc483550777e9546e`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T144658Z-5651625987/verification-plan.json`, SHA-256 `585f9783d32aaf67b68c4e423664bf50f662ebfdf1b102fac2b23e1d4b00c433`.
- Gate 3 correction approval: `reviews/tests/YII2-STAND-BACKUP-CONSOLE-001.md`, “Post-CI unreadable-observer correction review.”
- Reviewer independence is unchanged.

No findings. The post-CI executable delta is confined to the external Python test observer and delivery/review records. The three production PHP owners are byte-identical to the terminal-approved package: `StandBackupApplication.php` SHA-256 `c462e416512443a64d0e1cebfbf8d527815f3420ee85bc6c1f76cd607f5e5dcd`, `StandBackupFilesystem.php` `275d4d5f8ccd7e5016a584a20c4f34fe42c735664855d0fa41e5a5ad6f1b3253`, and `StandBackupController.php` `8f9c4ac07c9400e111e402888fa8dfe2478951a9b060be184842d8a0b9d4ae6e`. Therefore the approved Yii/PHP ownership, UNKNOWN lease preservation, descriptor identity/no-follow checks, hostile-temp protection, bounded exclusive creation, canonical bundle/history/pointer semantics, and absence of a Python production seam remain unchanged.

All eight mapped checks are GREEN and exact-source bound: Yii transport, target admission, complete bundle/verify including unreadable/hostile/bounded atomic cases, preservation, replay/concurrency, architecture, PHP ownership, and verification inventory. `git diff --check` is clean.

Quality Graph `34762933379` remains a failed run with the complete known inventory recorded in the associated test review: e2e failed on the observer `PermissionError`; integration, unit, fast, and governance succeeded; aggregate verify failed. This Gate 5 approval covers the correction source but does not convert that prior CI to GREEN. Harness currently reports the new correction's CI/deployment as `UNKNOWN`.

### Post-CI correction verdict

`APPROVED`

Gate 5 passes for exact source `1012b628df2abf94bdaeccfd93d19c18bf9fd4a25bb4bb807aa72570db757428`. Root must publish exactly these bytes and obtain a terminal GREEN/`VERIFY_OK` Quality Graph for the corrected exact commit before merge-ready status. Deployment/live backup remains unauthorized and `UNKNOWN`.
