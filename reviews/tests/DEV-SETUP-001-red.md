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

## Additive RED corrections, 2026-09-08

After review, the fixture copied the generated Docker/Compose inputs used by setup
and its trace classifier was narrowed to npm source-tree mutations. The focused suite
was GREEN 5/5. A manifest parser security example was then added. Before the parser
correction it reached `--check` and failed because `EXTRA=$(touch SENTINEL)` created
the sentinel; the other five tests passed. After the parser correction that example
passes without a filesystem write.

The owner then added the bounded portable ZIP adapter to Gate 1. Its public Unicode
listing/extraction test has a deliberate missing-adapter RED rather than an execution
exception:

```text
$ python3 tests/Verification/development_setup_001_test.py
test_check_rejects_mismatched_existing_tree_without_repair ... ok
test_failed_fresh_shlz_build_never_publishes_destination ... ok
test_incompatible_runtime_fails_before_dependency_or_build_mutation ... ok
test_malformed_manifest_is_rejected_without_command_substitution_or_writes ... ok
test_manifest_has_runtime_pins_and_tcpdf_has_one_authority ... ok
test_successful_repeat_reuses_dependencies_and_preserves_user_file ... ok
test_zip_adapter_lists_and_extracts_unicode_entry_without_mutation ... FAIL
AssertionError: False is not true : INTENDED_RED DEV-SETUP-001 portable unzip adapter is missing
Ran 7 tests in 5.742s
FAILED (failures=1)
EXIT=1
```

The verification inventory correction separately preserves the prior unit digest by
removing exactly the newly registered setup-test line before hashing. Its full focused
suite passes 15/15. Current additive-review hashes are:

```text
1dfdcf0452c639f3b972eb732c64a345ca62873e5896f8e88eb5669d0663d099  specs/DEV-SETUP-001.md
89e40761a2794aea384bd17b6113cdb94fface97110a3eb6c7fc02d5d453fe15  tests/Verification/development_setup_001_test.py
7986319885c422172860bb9996e559a960119975b52cf80d9a5b0d9fff09314a  tests/Verification/verification_inventory_001_test.py
```
