# PILOT-HTTP-AUTH-001 CSS swap preload portability — independent Gate 3 review

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/assignment_evidence_gate1`
- Reviewed repository commit: `550242d65177704558227c3451584dcde43995b1`
- Public seam: real PHP HTTP server serving `/pilot/assets/shlz.css` while the
  test swaps the configured CSS path after production's successful path stat
- Verdict: **NEEDS_CHANGES**

## Reviewed artifacts

```text
b23ba215387aa8328fcf71c7e71324f0363f403e7c776f84233b378e6043cb29  tests/Support/css_lstat_swap_preload.c
6d9bc6afd52a820af0cb216ec63b8cb5426bf4234609cdfc2ea83e584025d07d  tests/InstallationProcess/pilot_http_auth_001_test.php
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
66806572db401c1fc5c7087ff7cc0b20e72e4f06203040b6cbc76ee3a1326826  app/PilotHttp/PilotHttp.php
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
```

## Finding

The current preload fixture exports only `lstat`. That is not the ABI symbol
called by the authoritative Linux verifier PHP binary: its dynamic imports
include `lstat64@GLIBC_2.33` (as well as `fstat64`/`stat64`) and not `lstat` for
this operation. Production source explicitly calls PHP's fully qualified
`\lstat`, but the PHP binary's libc ABI selection determines the symbol reached
at runtime. The test interposer therefore never runs, never creates the READY
marker and times out in setup before any swap or response assertion.

The same missing READY marker was reproduced on macOS and three Linux images.
On this macOS host the PHP binary imports `_lstat`, but the harness supplies only
`LD_PRELOAD`; Darwin requires its own dynamic-loader insertion contract and may
compile/export the inode64 spelling depending on SDK feature macros. Thus the
current failure is not evidence that production accepted or rejected a race. It
is a fixture portability failure.

## Why the present test is not approvable

All three modes depend on the READY handshake before the parent performs the
mutation:

- `unchanged` must prove the hook was reached and then preserve the original
  successful response;
- `symlink` must replace the path only after the successful production stat and
  prove fail-closed response with no attacker bytes;
- `regular` must replace the inode at the same boundary and prove the same
  fail-closed/no-attacker result.

Without an intercepted symbol, none reaches its intended timing boundary. A
timeout is broken setup, not RED for missing product behavior, and cannot prove
unchanged success or symlink/regular sensitivity. Production must not change to
fit an interposer that targets the wrong platform ABI.

## Exact minimal Gate 2 correction

Keep the current environment-gated, exact-path, post-success synchronization
algorithm, but make the support library intercept the symbols actually used by
the supported verifier PHP binaries:

1. Extract one shared guarded helper that receives `(path, real_result)` and
   performs the current exact target comparison, one-shot READY creation and
   bounded RELEASE wait only when the real stat succeeded. Its one-shot state
   must be shared by every exported wrapper so an implementation or libc alias
   cannot synchronize twice.
2. Retain the `lstat` wrapper. Under explicit glibc compile guards, additionally
   export `lstat64(const char *, struct stat64 *)`, resolve exact
   `RTLD_NEXT, "lstat64"`, call the real function first, then invoke the shared
   helper. Do not cast `struct stat` and `struct stat64` buffers or redirect one
   wrapper through the other.
3. Use platform guards so the glibc-only type/symbol is never compiled on
   non-glibc libc or Darwin. Resolver failure must fail fixture setup safely; it
   must never call a null function pointer or fabricate a successful stat.
4. On Darwin, compile/load with the platform's dynamic-library and insertion
   mechanism (`DYLD_INSERT_LIBRARIES`, with the exact namespace mode required by
   the built artifact), and verify/export the symbol spelling imported by that
   PHP binary. If the SDK maps the C declaration to an inode64 symbol, provide
   the guarded compatible wrapper/alias rather than assuming Linux `lstat64`.
   On ELF retain `LD_PRELOAD`. The test must explicitly reject an unsupported
   platform/ABI as setup; it must not silently skip a swap case.
5. Preserve all current bounded process/file cleanup and environment isolation.
   The preload variables remain test-process-only; no preload token or support
   dependency may enter production.

This correction is confined to the test and support fixture. It requires no
change to `PilotHttp.php`, CSS ownership, response behavior or product spec.

## Required RED/sensitivity evidence after correction

A fresh Gate 2 run must record the platform, PHP imported stat symbol and built
library exported wrapper, then show READY is reached in each mode. With the
release protocol active, the unchanged case must return the exact original CSS;
both symlink and different-regular-inode swaps must return the specified
fail-closed response and never contain attacker bytes. The support test should
also demonstrate that a non-target path and a failed real stat do not publish
READY, and that disabling the needed ABI wrapper reproduces the current missing
READY failure. These checks distinguish genuine boundary sensitivity from a
marker created unconditionally by test code.

The expected values remain derived from the approved HTTP/CSS contract and the
literal test fixtures, not from production internals. The synchronization seam
is an external test harness around a real public HTTP request; it does not call
a private production method or mutate production state.

## Verdict

**NEEDS_CHANGES.** The diagnosed missing symbol/load mechanism makes the current
three swap cases non-executable on the tested platforms. The minimal correction
is the shared one-shot guarded `lstat`/glibc-`lstat64` interposer plus an explicit
Darwin compile/load/symbol path, followed by the unchanged/symlink/regular and
negative-control sensitivity evidence above. Production changes are neither
needed nor permitted for this correction. A fresh independent Gate 3 review is
required after the corrected test/support commit and demonstrated RED.

The reviewer changed no test, support or production file. Only this immutable
review record was added.
