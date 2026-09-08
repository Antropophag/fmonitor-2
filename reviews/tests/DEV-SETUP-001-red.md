# DEV-SETUP-001 RED evidence

- Date: `2026-09-08`
- Author: `/root/setup_contracts`
- Scope: Gate 1 specification and Gate 2 executable acceptance test only
- Public seam: `bash tools/delivery/setup.sh [--check]`, `make setup`, `make doctor`
- Verdict: **RED — implementation absent; independent Gate 3 review required**

The isolated test copies delivery inputs into a temporary checkout and puts
trace-only runtime, Git, npm and Docker executables first in `PATH`. It does not use
the network, a live Docker daemon, the real `../shlz-ui`, or production data. The
four behavioral examples all reached the deliberate public-script existence
assertion. This is the intended missing-behavior RED rather than an uncaught
`FileNotFoundError`; the independent manifest contract already passes.

Command and exact result:

```text
$ python3 tests/Verification/development_setup_001_test.py
test_check_rejects_mismatched_existing_tree_without_repair ... FAIL
test_failed_fresh_shlz_build_never_publishes_destination ... FAIL
test_incompatible_runtime_fails_before_dependency_or_build_mutation ... FAIL
test_manifest_has_runtime_pins_and_tcpdf_has_one_authority ... ok
test_successful_repeat_reuses_dependencies_and_preserves_user_file ... FAIL

AssertionError: False is not true : INTENDED_RED DEV-SETUP-001 public setup script is missing

Ran 5 tests in 0.041s
FAILED (failures=4)
EXIT=1
```

Syntax and diff hygiene:

```text
$ python3 -m py_compile tests/Verification/development_setup_001_test.py
EXIT=0
$ git diff --check
EXIT=0
```

Reviewed-input hashes before this evidence file:

```text
ecbcab284b9bf389e328a7ec8d4de2cee6d5c1002cccb25a80de468fae6ee22d  specs/DEV-SETUP-001.md
232f4f190b999bb10ddda80214d5228a3e376c112b9023b9fff0655b3f88be5b  tests/Verification/development_setup_001_test.py
```

This record does not approve its own tests and does not authorize implementation
before a separate Gate 3 verdict. No catalog, Makefile, CI, application, deployment,
or live dependency was changed by this test-author slice.
