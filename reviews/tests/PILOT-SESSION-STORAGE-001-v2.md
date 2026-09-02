# Independent test rereview v2 — PILOT-SESSION-STORAGE-001

Date: 2026-09-02  
Reviewer: separately tasked fresh agent `/root/session_test_rereview`  
Independence: reviewer did not author or edit the specification, tests, support
launchers, RED evidence, or production code; this record is the reviewer's only
change  
Gate: 3  
Verdict: **CHANGES_REQUIRED**

## Exact reviewed hashes

The executable contract still matches the owner-approved hash:

```text
2afa029374583b18ed06d6eb37f8c9e3857b3366ac5e516f1eb3b07de8ba8ad0  specs/PILOT-SESSION-STORAGE-001.md
```

Corrected Gate 2 artifacts reviewed:

```text
edfd9603f13a43af734b5c644ecd53da43ba73c82687f3ccb6d2e08e68c75197  tests/Support/PilotSessionStorageScenario.php
c73c2254131f7c1cf4db12c85760531fca3f26317969c32f5c2eb4e25a8f5130  tests/Support/pilot_session_storage_scenario_runner.php
ae3778526c8c26584db8897f2c4d5a96a071a6adcd52ed87552a3c5ce3423e9e  tests/Support/pilot_session_fabricated_runner.php
0d8bf6cf329dd59c2dae835bb7ae844192a707dfef69ddf444d8446f26adec14  tests/Support/pilot_session_hanging_runner.php
ad933020006a36fee1cc21f26f6a20072d7c0adbd27f6de9f1904599f72bcd53  tests/InstallationProcess/pilot_session_storage_filesystem_001_test.php
d65c9858e482a353c3372deff0f0c695a5148c8342fa8eb11b8db621423f0b78  tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
20d465be78ab8ff417caa6ea352546ac330469f9a93dd9f31ea09cf23383c8c1  docs/operations/pilot-session-storage-red-evidence.md
d5b8003c54ed003450eeca6d7ef42e4d69f851bbc196c74a76b2d0cea14b6883  reviews/tests/PILOT-SESSION-STORAGE-001.md
```

## Prior-blocker disposition

1. **Self-attesting seam — partially corrected, still blocking.** The protocol
   suite now makes one real asset request and one real GET `/pilot/login`
   request, and the filesystem parent can take material snapshots. However, all
   45 filesystem/crash cases and 19 of the remaining protocol/consumer/restart/
   cleanup cases still accept the future dispatcher's JSON as the normative
   observation. The parent snapshot is used only for broad zero-mutation and
   mode/confinement checks; it does not derive the claimed result, bytes,
   operation order, lock ownership, crash boundary, GC order, consumer wiring,
   response timing, cookie continuity, or restart outcome.

   The echo adversary proves only that a runner which creates no entry fails the
   single `write_new_atomic` material-count check. An equally fabricated runner
   can create one arbitrary 0600 file and echo every expected array. That would
   satisfy the generic post-loop checks without invoking `PilotSessionStorage`,
   either HTTP consumer, Compose, or the required lifecycle. The sensitivity
   defect from the first review therefore remains.

2. **Normative phase matrix — not corrected.** Scenario names enumerate many
   phases, but assertions remain aggregate child claims such as
   `categories`, `oldValid`, `binaryAscending`, `directoryFsync`, `calls`, and
   `sameVolumeRootInstance`. There is no test-owned fault schedule or event
   trace and no independently observed old/stage/tombstone/new path+inode+bytes
   state for each open/read/lock/write/fflush/fsync/link/rename/unlink/close
   boundary. The same applies to candidate retries, lock timing/order, exact GC
   eligibility/order/limit, log correlation/redaction, identity swaps, and
   response-before-durable-commit sensitivity.

   More fundamentally, the canonical scenario runner exits 66 after the class
   existence check; none of the scenario actions exist. Production code alone
   cannot make these tests GREEN. Completing the runner later would change the
   reviewed test implementation during Gate 4, which is forbidden without a
   new Gate 2 RED and independent Gate 3 review. Gate 2 is not complete yet.

3. **Qualifying RED — partially corrected, still insufficient for the whole
   slice.** The protocol RED is valuable: a real LocalAuth GET reaches the real
   router and observes 500 instead of required 503 after a successful exact
   favicon preflight. The filesystem RED reaches a test-owned dispatcher and
   fails at the first class-existence check. Because every filesystem action is
   still absent and no later dispatcher scenario can execute, that RED does not
   demonstrate sensitivity of any filesystem, crash, GC, or close assertion.
   There is also no real UserAccessView, HEAD, POST, login/logout success,
   cookie/CSRF/return-to/old-ID, or Compose restart RED; those remain JSON
   expectations behind the unimplemented dispatcher.

4. **Timeout/reap/cleanup — partially corrected, still blocking.** The direct
   hanging child probe is bounded and the reviewer observed no surviving named
   child. Normal failing runs remove the task parent. But the claimed
   attempt-all/foreign-scope guarantees are not implemented:

   - sentinels are merely sibling files under `/tmp`; they are not the actual
     compatibility root, Compose volume, or a foreign tree, so their names do
     not prove those resources remain untouched;
   - sentinel creation occurs before `try`, and each `finally` performs cleanup
     sequentially; the first cleanup/hash/unlink exception prevents later
     resources from being handled, rather than retaining the first error while
     attempting all cleanup;
   - constructor failure after shared-parent creation has no cleanup owner;
   - process helpers do not track process groups/descendants, and server stop
     does not assert that termination/reaping succeeded;
   - no controlled setup-failure, child-timeout-with-owned-artifact, crash, or
     cleanup-failure probe verifies both complete owned cleanup and preservation
     of real sentinels;
   - `cleanup_scope` and `processesReaped` are still supplied by the child JSON.

## Additional traceability gaps

- Real asset priority covers only `favicon.svg`; the contract fixes known
  CSS/JS/SVG/font and unknown asset behavior.
- Raw HTTP observation covers only invalid-config LocalAuth GET. It does not
  exercise UserAccessView or exact GET/HEAD/POST regeneration/write/destroy
  failures, full success responses, multivalue Set-Cookie, redirects, cookie
  port/no-port/Secure, CSRF, safe return-to, old-ID invalidation, or GC.
- `snapshot()` records mode, size, link count and digest, but not uid, inode/
  device identity or file bytes; it cannot substantiate several explicit
  ownership and identity-swap claims.

## Reproduced RED and hygiene evidence

```text
php tests/InstallationProcess/pilot_session_storage_filesystem_001_test.php
exit 255
RuntimeException: scenario config_absent_compatibility_default failed with exit 65:
INTENTIONAL_RED: public IdentityAccess PilotSessionStorage seam is missing

php tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
exit 255
TestFailure: INTENTIONAL_RED: real LocalAuth invalid storage maps 503
Expected: 503
Actual: 500

test ! -e /tmp/fmonitor2-session-storage-tests
exit 0 after each suite

pgrep -af 'pilot_session_hanging_runner.php'
no child process (excluding the review shell command itself)

pgrep -af 'rapid-pilot/router.php|pilot_session_hanging_runner.php'
no child/server process (excluding the review shell command itself)
```

All six PHP test/support files pass `php -l`. Targeted `git diff --check` is
clean.

## Gate decision

Gate 3 remains closed. Keep OpenSpec task 2.2 unchecked. Implement the complete
test-owned scenario actions before review, replace result claims with
test-owned material/event/raw-HTTP observations for every normative phase and
both consumers, execute a real test-owned stop/start persistence boundary, and
make cleanup genuinely attempt-all under controlled failure. Then capture RED
that reaches those actions and request another fresh independent review of the
new exact hashes. Gate 4 MUST NOT begin on these artifacts.
