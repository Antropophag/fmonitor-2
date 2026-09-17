## Why

Focused checks rebuild unchanged OS, PHP, Composer and uv dependency layers when
only executable source identity changes, because metadata-only build arguments
are declared before those dependency `RUN` steps. Issue #180 requires a bounded
cache correction with measured A/B evidence, without changing dependency pins,
identity semantics, CI selection or the harness architecture.

## What Changes

- Move source/lock metadata arguments to the image metadata boundary after all
  dependency installation steps.
- Preserve exact source/lock labels, image identity checks and cold builds.
- Add a cheap structural regression plus a bounded real-BuildKit A/B proof.
- Record external public-command wall time separately from the existing internal
  command duration.

## Capabilities

### New Capabilities

- `delivery/focused-checks-dependency-cache`: cache and identity behavior of the
  existing focused-check container build.

### Modified Capabilities

Нет.

## Impact

Only `tools/delivery/Dockerfile.focused-checks`, its focused verification,
contract/evidence documents and OpenSpec lifecycle artifacts change. Product
code, dependency versions, CI composition, FAST/Gates and `RUN_IN_PROFILE_RESULT`
remain unchanged.
