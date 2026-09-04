# PILOT-HTTP-AUTH CSS swap fixture portability correction

Date: `2026-09-04`

Initial independent review: `e94c03110752968cfc27ed16f6f9b67419799aa4`
(`NEEDS_CHANGES`).

## RED classification

The original support library exported only `lstat`. The authoritative Linux
PHP binary imports `lstat64@GLIBC_2.33`, while Darwin requires dyld interpose
rather than ELF `LD_PRELOAD`. The old five-second READY timeout was therefore
fixture setup failure, not evidence about production CSS race handling.

## Corrected Gate 2 candidate

Commit: `3614d6418814d67ccd9c3eca0880218a84d9bd95`

```text
1fc3be0f2509943f7b3158c3c93b1442b9d1f3d107e4b7d44944493e927ca1e9  tests/InstallationProcess/pilot_http_auth_001_test.php
eda63c2ca641a0eb141a6198e63137c1a2419233a53c48797b2be8886c60be19  tests/Support/css_lstat_swap_preload.c
c0b5b86646e654f52907c391309f879a0364af91f7f8da70c75a21e1b3f55b9a  tests/Support/css_lstat_swap_preload_probe.php
```

The interposer now has one shared one-shot exact-path/post-success helper,
separate correctly typed `lstat` and glibc `lstat64` wrappers, safe resolver
failure, and Darwin dyld interposition using a non-recursive `fstatat` real
primitive. Unsupported OS/ABI is a setup failure, never a skipped assertion.

The executable controls prove that non-target and failed-stat calls do not
publish READY, the target call does publish READY, and disabling the imported
ABI wrapper reproduces the missing-READY RED. All three race modes reach the
handshake: unchanged serves exact original bytes; symlink and different-inode
replacement return redacted `503` with no attacker or partial original bytes.

Full public-boundary result on both environments:

```text
macOS arm64 Homebrew PHP: PASS: PILOT-HTTP-AUTH-001 HTTP boundary
Linux arm64 fmonitor2-verify-runner: PASS: PILOT-HTTP-AUTH-001 HTTP boundary
```

PHP lint and `git diff --check` pass. Production was not edited.

Fresh independent Gate 3: `0eb66f5` (`APPROVED`). The reviewer independently
repeated the complete HTTP verifier on native macOS and Linux arm64, including
the symbol/negative/disabled-wrapper controls and all three race modes. This
test-only portability correction is complete.
