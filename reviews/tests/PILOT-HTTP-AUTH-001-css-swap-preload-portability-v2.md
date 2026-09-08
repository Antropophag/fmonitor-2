# PILOT-HTTP-AUTH-001 CSS swap portability — independent Gate 3 rereview v2

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/http_auth_uppercase_rereview`
- Reviewed correction commit: `3614d6418814d67ccd9c3eca0880218a84d9bd95`
- Prior independent review: `e94c03110752968cfc27ed16f6f9b67419799aa4` (`NEEDS_CHANGES`)
- Public seam: real PHP HTTP server serving `/pilot/assets/shlz.css` while the test changes the configured path after production's successful initial `lstat`
- Verdict: **APPROVED**

## Rereview result

All requirements from the prior portability review are closed without changing
production or weakening the approved HTTP/CSS oracle.

The support library now routes both platform wrappers through one process-wide
one-shot helper. Synchronization occurs only after the real stat returns
success, only for the byte-exact configured target path, and only once even if
libc/PHP reaches more than one exported wrapper. Non-target and failed-stat
calls cannot create READY.

On glibc Linux, `lstat` and `lstat64` retain their correct distinct buffer
types. Each resolves and calls its exact `RTLD_NEXT` symbol before invoking the
helper; a missing symbol sets `errno=ENOSYS` and returns `-1`, so it cannot call
a null pointer or fabricate success. `lstat64` is guarded by both `__linux__`
and `__GLIBC__`, preventing its ABI from leaking into Darwin or other libc
builds.

On Darwin, the library uses dyld's `__DATA,__interpose` mechanism for `lstat`.
Its replacement obtains the real no-follow result through `fstatat(AT_FDCWD,
..., AT_SYMLINK_NOFOLLOW)`, avoiding recursion through the interposed symbol.
The test compiles a dynamic library and supplies only the Darwin loader
variables to the isolated child. Linux retains ELF shared-library compilation,
`-ldl` and `LD_PRELOAD`. Any other OS, missing compiler/symbol tool, failed
compile, unsupported imported stat ABI or absent required export fails setup;
there is no platform skip.

After compilation, the test inspects the current PHP binary's imported stat ABI
and the built library's symbols. More importantly, executable controls prove
the selected wrapper actually runs: non-target success leaves READY absent,
missing-path failure leaves it absent, exact-target success creates it, and a
fresh child with `FMONITOR_TEST_CSS_SWAP_DISABLE_ABI=<selected>` reproduces the
old missing-READY condition while all real stat return values stay correct.
This closes the possibility that a loose symbol-text match alone could approve
an inert library.

Each race mode runs in a fresh server process, so the shared one-shot flag is
fresh. The parent waits at most five seconds for READY, applies only its
task-owned exact path mutation, writes RELEASE, then validates:

- unchanged directory entry returns `200` and the exact original CSS bytes;
- regular-file to symlink replacement returns the exact redacted `503` and no
  attacker or partial original bytes;
- different-regular-inode replacement returns the same fail-closed result.

Loader variables are assembled only into the child environment passed through
`/usr/bin/env -i`; they do not affect the reviewer shell, database, parent raw
HTTP client or production configuration. Every marker, target, replacement,
library and server is bounded by existing `try/finally` cleanup. Both full runs
left no owned schema, PHP server or `.test-artifacts` child.

## Independent verification

### macOS arm64 / native PHP

```text
$ FMONITOR_TEST_DB_HOST=127.0.0.1 \
  FMONITOR_TEST_DB_PORT=23306 \
  FMONITOR_TEST_DB_ADMIN_USER=root \
  FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/pilot_http_auth_001_test.php
PASS: PILOT-HTTP-AUTH-001 HTTP boundary
```

### Linux arm64 / `fmonitor2-verify-runner:latest`

The repository and sibling public `shlz-ui` checkout were copied read-only into
a disposable container-owned `/home/fmonitor/workspace`, then the test ran as a
non-root user so unreadable-file behavior remained meaningful:

```text
PASS: PILOT-HTTP-AUTH-001 HTTP boundary
```

An initial root-container run is excluded because root can read the `0000`
negative fixture. A first non-root Linux run reached well beyond all CSS swap
cases and encountered one transient foreign connection during a later
request-resource isolation assertion; an immediate fresh disposable run passed
the entire unchanged test. Neither excluded run showed a preload, READY,
unchanged, symlink or regular-swap failure.

Additional checks:

```text
php -l tests/InstallationProcess/pilot_http_auth_001_test.php       PASS
php -l tests/Support/css_lstat_swap_preload_probe.php              PASS
git diff --check 3614d641^..3614d641                               PASS
```

## Sensitivity and negative controls

The controls are independent enough for Gate 3:

- expected booleans are fixed literals, not copied from interposer output;
- `FMONITOR_TEST_CSS_SWAP_DISABLE_ABI` disables only the ABI selected from the
  current PHP import, proving an incorrect wrapper recreates missing READY;
- failed `lstat` still returns false and non-target success still returns true,
  while neither creates READY;
- exact-target success must both return true and create READY;
- race expectations remain derived from the approved descriptor-identity
  contract and literal fixture bytes;
- attacker bytes are rejected independently of response status.

The helper sets its one-shot state immediately before exclusive READY creation.
Because every control/race invocation gets a fresh process and unique
task-owned marker path, a stale marker or second wrapper invocation cannot make
the matrix pass accidentally.

## Reviewed hashes

```text
1fc3be0f2509943f7b3158c3c93b1442b9d1f3d107e4b7d44944493e927ca1e9  tests/InstallationProcess/pilot_http_auth_001_test.php
eda63c2ca641a0eb141a6198e63137c1a2419233a53c48797b2be8886c60be19  tests/Support/css_lstat_swap_preload.c
c0b5b86646e654f52907c391309f879a0364af91f7f8da70c75a21e1b3f55b9a  tests/Support/css_lstat_swap_preload_probe.php
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
66806572db401c1fc5c7087ff7cc0b20e72e4f06203040b6cbc76ee3a1326826  app/PilotHttp/PilotHttp.php
```

## Verdict

**APPROVED** for the exact three corrected test/support artifacts at commit
`3614d6418814d67ccd9c3eca0880218a84d9bd95`. Production is unchanged and no
Gate 4 behavior implementation is required. The cumulative
`PILOT-HTTP-AUTH-001` boundary test is independently GREEN on native macOS and
the available Linux verification image.
