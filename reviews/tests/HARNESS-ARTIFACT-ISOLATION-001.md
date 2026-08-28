# Test review: HARNESS-ARTIFACT-ISOLATION-001 v0.4

- Gate: 3 — fresh independent review of all v0.4 Gate 2 inputs
- Reviewer: separately tasked agent `/root/harness_artifact_isolation_gate3_v04`
- Test author: another separately tasked agent; this reviewer authored neither specification nor tests
- Reviewed ancestry: HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`; dirty-tree bytes pinned below
- Specification: `HARNESS-ARTIFACT-ISOLATION-001 v0.4` (`APPROVED`)
- Public seam: `FMonitor2\Tests\Support\HttpReadOnlyFilesystemGuard::observe(...)`
- Observed intended RED: deletion after a valid before-snapshot returns obsolete `Protected HTTP read-only path could not be read.` instead of the unified mutation verdict
- Date: `2026-08-28`
- Verdict: `APPROVED`

## Findings

No blocking findings.

The v0.4 deltas are observable and regression-sensitive. The tracer independently requires the shared trusted `.test-artifacts` parent to survive cleanup with the same canonical path, type, permission and ownership metadata. It separately deletes the exact configured protected file and replaces that file with a directory only after a valid before-snapshot; both branches require exact `TestFailure("Protected HTTP read-only path changed.")`. The current support seam fails at the deletion assertion with its older read-error verdict, so the RED is specifically caused by the missing v0.4 post-snapshot normalization rather than setup.

The newly explicit caller gaps are closed in the reviewed Gate 2 inputs. `phaTrustedServerFixture()` wraps its successful raw socket transaction directly in `observe(...)` with the configured CSS and protected artifact-store paths. `pocConcurrentGets()` wraps both requests in one observation, and its optional test-only callback deliberately mutates the protected sentinel after both requests are sent; exact mutation-verdict sensitivity proves the concurrent operation is guarded. Existing public request helpers remain guarded, and no product expectation is replaced by the filesystem check.

## Independent assessment

- **Traceability and public seam — pass.** The tracer cites v0.4 and invokes only the specified public test-support seam. Caller coverage is exercised at the raw HTTP helper boundary, not through helper-private implementation details.
- **Intended RED — pass.** Setup, subprocess handshake, validation, callback-exception, sibling-isolation, protected-directory mutation and protected-file byte mutation phases complete before the exact deletion assertion fails. Expected and actual verdicts isolate the missing behavior.
- **Deletion/read/type sensitivity — pass.** Deletion and regular-file-to-directory replacement occur inside the observed callback after a valid initial snapshot. Each requires the independently literal redacted mutation verdict; restoration happens only in `finally` after verdict capture.
- **Shared-parent preservation — pass.** The test records canonical path, full mode, UID and GID before owned-root creation and compares them after exact child cleanup. Cleanup rejects ancestors, siblings, basename changes, noncanonical roots, symlinks and untrusted ownership/modes; it never removes the parent.
- **Sibling-owner isolation — pass.** Ready/release/done coordination guarantees the sibling creates, changes and removes its sentinel only during the callback. The callback result must remain exact.
- **Caller coverage — pass.** All `pha` raw request entry points route through a guard, including rejected Host requests and the trusted fixture's successful manually composed request. Both `pocConcurrentGets()` responses are enclosed by one guard with a protected-write sensitivity probe. `pol`, ordinary `poc`, and `ppf` filesystem-observing reads continue to use the same seam.
- **Expected-value independence — pass.** Callback result, fixture bytes, malicious bytes and exact verdict come literally from the approved specification. No expected fingerprint is derived from planned implementation.
- **Implementation independence — pass.** Tests state public outcomes and ownership boundaries; they do not assert traversal structures, serialization, private methods or an algorithm.
- **Determinism and cleanup — pass.** Handshakes use monotonic deadlines capped at five seconds and no sleep-based ordering. The reproduced RED left zero tracer roots and no sibling worker. Production CSS is read-only; deliberate writes target only the owned copy.

Gate 4 may minimally normalize every after-snapshot disappearance, type replacement or read failure to the exact mutation verdict, then complete the four caller migrations without changing product or DB assertions.

## Verification evidence

```text
$ php -l tests/InstallationProcess/harness_artifact_isolation_001_test.php
No syntax errors detected in tests/InstallationProcess/harness_artifact_isolation_001_test.php

$ php -l tests/InstallationProcess/pilot_http_auth_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_http_auth_001_test.php

$ php -l tests/InstallationProcess/pilot_object_card_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_card_001_test.php

$ php tests/InstallationProcess/harness_artifact_isolation_001_test.php
Expected: 'Protected HTTP read-only path changed.'
Actual:   'Protected HTTP read-only path could not be read.'
at harness_artifact_isolation_001_test.php:383
exit code: 255

post-run hai-owner-* / hai-sibling-* roots: 0
post-run matching sibling-writer processes: 0
```

Focused `pha` evidence supplied with the handoff was accepted as supporting evidence and was not rerun; no full suite was run at Gate 3.

## SHA-256 reviewed-input manifest

```text
f40cc1fdfa5f3a9afda7b75acfe832181e5c1eeb4890ab6231e07d0e8f4919c6  specs/HARNESS-ARTIFACT-ISOLATION-001.md
7d6371755c0404c08da6972dd44dbed16ca9a24f47ff97af2a92d36de7860014  tests/InstallationProcess/harness_artifact_isolation_001_test.php
43bb4ee0db54d7d06d29f49354dea13642b172fd864f8aa8bcf250583644abc6  tests/InstallationProcess/pilot_http_auth_001_test.php
b8becf9b47b322078ed330711b4a27e05778946e706ca25d0281a5fb747e5ce6  tests/InstallationProcess/pilot_object_list_001_test.php
e5dc0907d18bc9eca3e8180e73625b8d036fdb733f69e2532fc02bdda4fd02e6  tests/InstallationProcess/pilot_object_card_001_test.php
9aa1268e5967387c482cde573bc5861b08e1d7f23762b91bb90ac7667f1ad1a7  tests/InstallationProcess/pilot_prepare_form_001_test.php
d6f6731715e4007126541caab75ed6e4099f0192d12783090b0921a3bc0c68da  tests/Support/TaskOwnedArtifactRoot.php
38cc3a96ed6c23b7b88f6860b811d81933b5c4bfbba4f45c11bbe7a2da68edf7  tests/Support/trusted_host_server.php
800d135a043633260ce59440579f35f4dcf16553c61f7e149d82825c9e6c3509  tests/bootstrap.php
721a3a6e06efb18da2702c379a785c5ed863c251622b059437bea95deccb5d54  docs/development-process.md
201885dc684287c1526c4657e5a9dd71f23d7dca74423fb5f329169e03fea358  PRODUCT.md
68f38cae8a69b33bb194e5b6f5d3809f4ddb90004d59af6b7a8a3c5b11870037  CONTEXT.md
METADATA  reviews/tests/HARNESS-ARTIFACT-ISOLATION-001.md
```
