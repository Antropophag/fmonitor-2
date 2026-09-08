# Code review: VERIFICATION-INVENTORY-001

- Reviewer: `Codex agent /root/audit` (independent; did not author the specification, tests, implementation, or compatibility amendment)
- Reviewed commit: `b7ff4e1` (`b7ff4e1` includes production commit `115d46c55cbc5544a2266491bfb16686274126e4`)
- Comparison base: `d5f8f2d7d96171dbc152766b517501c8fe0abaf1`
- Specification: `openspec/changes/explicit-verification-inventory/specs/verification/inventory/spec.md`
- Approved tests: `reviews/tests/VERIFICATION-INVENTORY-001.md`, including the append-only QUALITY-GRAPH-CI-SETUP-001 Gate 3 restart
- Verdict: `APPROVED`

## Review

The implementation conforms to the approved first slice. `tools/verification/suites.tsv` explicitly contains the preserved baseline membership and order: 117 unit entries, 120 DB entries, 18 original characterization entries, one E2E entry, and the new inventory contract once in characterization. The existing E2E membership remains in DB and standalone E2E, and the bootstrap child remains unchanged, as required for this preparatory slice.

`load_inventory` validates the complete catalog before list output or test execution. It rejects missing catalogs and directories, non-three-field records, unknown suites/runtimes, missing or escaping paths, duplicate `group/path` pairs, and unregistered tests in both protected PHP families and the Verification Node family. Selection follows catalog order and no longer reads test source or invokes `rg`. Empty arrays are handled by indexed loops; the independent tests ran under the repository host's GNU Bash 3.2.57.

Execution preserves the prior operational distinctions. Unit and DB collect failures and continue through remaining members; characterization retains fail-fast for its two Python prerequisites and aggregates later PHP failures; DB and E2E retain the MariaDB preflight. Every dispatched top-level test retains its `VERIFY` line and receives one `VERIFY_TIMING` record with suite, runtime, path, nonnegative Bash `SECONDS`, and actual exit status. Timing is emitted to stdout only, so concurrent runners do not share a mutable artifact. Lint and RED modes retain their commands; catalog validation is a new fail-closed prerequisite shared by the runner.

The QUALITY-GRAPH-CI-SETUP-001 amendment correctly reconciles the intentional removal of `rg`. Its exact unit/DB list assertions run without `rg`, and missing catalog remains an exact pre-output setup failure. The real test-tool image build, commit provenance label, required PHP/extensions, root/non-root execution and network-isolation assertions are unchanged and pass.

The documentation accurately limits the result: this slice makes composition explicit and adds coarse timing; it does not claim that CI is faster, remove duplicated E2E execution, change branch protection, deploy runtime code, or complete issue #25. It explains that child execution is included in parent timing and that sums do not equal full wall-clock time.

No product domain behavior, authorization, append-only history, database schema, stand data, HTTP route, or deployment configuration changes in the reviewed diff. The implementation stays within the verification owner seam.

## Verification evidence

Independent reviewer rerun on exact `b7ff4e1`:

```text
$ python3 tests/Verification/verification_inventory_001_test.py
Ran 15 tests in 5.026s
OK

$ python3 tests/Verification/verification_native_suites_001_test.py
Ran 9 tests in 2.141s
OK

$ php tests/Verification/quality_graph_ci_setup_001_test.php
QUALITY-GRAPH-CI-SETUP-001 PASSED
```

Author evidence reviewed:

```text
php tests/Verification/harness_full_aggregation_001_test.php        PASS
php tests/Verification/harness_fresh_test_lifecycle_001_test.php    PASS
bash -n tools/verification/run.sh                                   PASS
git diff --check                                                    PASS
openspec validate explicit-verification-inventory                   PASS
make architecture-check                                             ARCHITECTURE CHECK PASSED (7 rules)
```

The reviewer also ran `git diff --check d5f8f2d..b7ff4e1`; it passed with no output.

The full `make verify` was deliberately not run for this intermediate infrastructure slice. Therefore this approval establishes focused conformance and regression coverage only; it is not a new `VERIFY_OK`, release, deployment, CI-parity, or completion claim for issue #25.

No blocking findings remain. Gate 5 is `APPROVED` for commit `b7ff4e1`.
