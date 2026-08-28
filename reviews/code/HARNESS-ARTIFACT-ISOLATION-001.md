# Code review: HARNESS-ARTIFACT-ISOLATION-001 v0.4

- Gate: 5 — final independent code review
- Reviewer: separately tasked agent `/root/harness_artifact_isolation_gate5_v04`
- Implementation author: other separately tasked agents
- Reviewed ancestry: HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`; dirty-tree bytes pinned below
- Specification: `HARNESS-ARTIFACT-ISOLATION-001 v0.4` (`APPROVED`)
- Approved test review: `reviews/tests/HARNESS-ARTIFACT-ISOLATION-001.md` (`APPROVED`, v0.4)
- Date: `2026-08-28`
- Verdict: `APPROVED`

## Standards

No documented-standard violation or material smell-baseline finding was identified. The change remains confined to test support and the four named HTTP acceptance callers. The filesystem guard owns validation/fingerprinting, the task-root helper owns lifecycle, and route-specific HTTP, DB, server, and assertion logic stays in each caller.

## Spec

No blocking or non-blocking finding was identified.

- **Unified post-snapshot verdict:** validation and the initial snapshot occur before the callback and retain configuration/read-failure semantics. After a valid before-snapshot, any snapshot `TestFailure` is converted to exact `Protected HTTP read-only path changed.`; inequality produces the same verdict. Callback identity is preserved only when unchanged, while mutation correctly takes precedence.
- **Path and snapshot safety:** roots must be canonical, absolute, real non-symlink objects beneath home and the repository/approved sibling boundary. Duplicate and prohibited overlap cases fail before callback. An exact protected file may be inside mutable, but protected directories may not overlap mutable. Traversal does not follow symlinks, sorts relative paths bytewise, and fingerprints type, permissions, bytes/size or link target. The configured CSS alone is read; the full `../shlz-ui` checkout is not scanned.
- **Snapshot coherence:** byte/link reads are bracketed by fresh `lstat` checks across identity, type, permissions, ownership, size and write-time state, excluding access time. Disappearance, type replacement, incoherent reads, and concurrent mutation fail closed.
- **Shared-parent preservation:** creation tolerates a concurrent creator and validates `.test-artifacts`. Cleanup revalidates the exact caller/token descendant, recursively removes only owned regular files, symlinks and directories without following links, then revalidates but never removes or changes the shared parent. The v0.4 tracer asserts canonical path, type, permissions and ownership preservation.
- **Four callers:** `pha`, `pol`, `poc`, and `ppf` each use a unique owner root with separate mutable and protected-store trees, an exact task-owned CSS copy, both protected paths, and common exact-root cleanup. Existing DB snapshots and product assertions remain.
- **Previously missed reads:** `phaTrustedServerFixture()` now guards its successful raw request; rejected raw-host requests were already guarded. `pocConcurrentGets()` guards both sockets and its optional in-flight sensitivity callback as one operation. Other migrated read-only raw primitives are reached through guarded wrappers. The intentional CSS-swap fixture mutates its own test files under its separate security contract and is not an unguarded read-only assertion.
- **Concurrency/sensitivity:** bounded monotonic handshakes prove sibling writes are ignored, while protected directory/CSS mutation, deletion, and file-to-directory replacement receive the exact mutation verdict. Fixture restoration occurs only after verdict capture; production CSS remains unchanged.
- **Security:** failures use fixed redacted literals and disclose no path or bytes. Product routes, production storage, DB permissions, and production integration behavior are unchanged.

The approved tracer catches both the original shared-directory false positive and plausible weakening of protected-store/CSS checks.

## Verification evidence

Per instruction, this reviewer ran no behavior test or full suite. Accepted Gate 4/root evidence:

```text
php tests/InstallationProcess/harness_artifact_isolation_001_test.php   PASS
php tests/InstallationProcess/pilot_http_auth_001_test.php              PASS
php tests/InstallationProcess/pilot_object_card_001_test.php            PASS
fresh sequential repository baseline                                    45/45 PASS
```

Reviewer-local checks:

```text
PHP lint: guard, task-root helper, tracer, all four callers, bootstrap   PASS
Static unified post-snapshot verdict analysis                            PASS
Static shared-parent lifetime/cleanup analysis                           PASS
Static all-four-caller coverage analysis                                 PASS
Static path/overlap/coherence/security analysis                          PASS
Static no-full-shlz-ui-scan analysis                                     PASS
```

## SHA-256 reviewed-input manifest

```text
f40cc1fdfa5f3a9afda7b75acfe832181e5c1eeb4890ab6231e07d0e8f4919c6  specs/HARNESS-ARTIFACT-ISOLATION-001.md
40319cf710b8922b87ff407b30f2fabd7b4f9e4eb6b6007a13170a5e10a28abc  reviews/tests/HARNESS-ARTIFACT-ISOLATION-001.md
800d135a043633260ce59440579f35f4dcf16553c61f7e149d82825c9e6c3509  tests/bootstrap.php
036f719fea1c6e8fe9c46da60281e411e7fd5686f463ca6145f6b2f4e708f165  tests/Support/HttpReadOnlyFilesystemGuard.php
d6f6731715e4007126541caab75ed6e4099f0192d12783090b0921a3bc0c68da  tests/Support/TaskOwnedArtifactRoot.php
7d6371755c0404c08da6972dd44dbed16ca9a24f47ff97af2a92d36de7860014  tests/InstallationProcess/harness_artifact_isolation_001_test.php
43bb4ee0db54d7d06d29f49354dea13642b172fd864f8aa8bcf250583644abc6  tests/InstallationProcess/pilot_http_auth_001_test.php
b8becf9b47b322078ed330711b4a27e05778946e706ca25d0281a5fb747e5ce6  tests/InstallationProcess/pilot_object_list_001_test.php
e5dc0907d18bc9eca3e8180e73625b8d036fdb733f69e2532fc02bdda4fd02e6  tests/InstallationProcess/pilot_object_card_001_test.php
9aa1268e5967387c482cde573bc5861b08e1d7f23762b91bb90ac7667f1ad1a7  tests/InstallationProcess/pilot_prepare_form_001_test.php
721a3a6e06efb18da2702c379a785c5ed863c251622b059437bea95deccb5d54  docs/development-process.md
201885dc684287c1526c4657e5a9dd71f23d7dca74423fb5f329169e03fea358  PRODUCT.md
68f38cae8a69b33bb194e5b6f5d3809f4ddb90004d59af6b7a8a3c5b11870037  CONTEXT.md
METADATA  reviews/code/HARNESS-ARTIFACT-ISOLATION-001.md
```

Summary: Standards — pass, 0 findings. Spec — pass, 0 findings. Gate 5 is `APPROVED`.
