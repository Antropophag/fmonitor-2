# Code review: VERIFICATION-NATIVE-SUITES-001

- Reviewer: `/root/original_gate5`, independently tasked agent; did not author runner or tests.
- Implementation author: root implementation agent.
- Reviewed source: `a8e6e9202df5c4e92f91877e8116d2e3f85f190f`, against `c93e69021b60a828fe3ec4b3df52592375113a7f`.
- Specification: VERIFICATION-NATIVE-SUITES-001 v0.1.
- Approved test review: `reviews/tests/VERIFICATION-NATIVE-SUITES-001.md`; Gate 1 in adjacent record.
- Verdict: `CHANGES_REQUESTED`.

## Blocking finding

**P2 — empty discovered arrays fail under supported Bash 3.2 with nounset.** The implementation expands `"${node_files[@]}"` unconditionally for list/unit execution, and similarly expands unit/db/sorted arrays during sorting and dispatch. Bash 3.2 with `set -u` treats an empty array expansion as an unbound variable. The specification requires the three directories to exist; it does not require every discovered interpreter/suite inventory to contain a file. Therefore a valid empty inventory causes an unexpected shell failure instead of the specified list/execution result.

Independently reproduced using `/bin/bash` 3.2.57 in a temporary synthetic tree containing the actual runner, all three mandatory directories, `tests/InstallationProcess/u_test.php`, and `d_test.php` with the DB marker, but no `*_test.mjs`. `list unit` emitted the PHP unit line and then exited 1 with `run.sh: line 87: node_files[@]: unbound variable`. `list db` returned its expected row and exit 0. No interpreter or production application was intercepted in this read-only reproducer. A concise result is preserved outside the repository as `native-suites-20260907/gate5-empty-inventory-finding.json` under the verification evidence root.

Required correction: retain nounset and use Bash-3-compatible guarded expansion for potentially empty discovered and temporary arrays. Add independent sensitivity for an existing empty Node directory and empty PHP unit/db inventories, including successful empty list results and appropriate execution/prerequisite behavior. New or amended tests require demonstrated RED and renewed independent Gate 3 before the correction; this review does not authorize changing expectations to require nonempty directories.

## Other review findings and verification

The current repository inventory maps correctly: 22 AssignmentOrderComposition PHP files exactly once, three in unit and 19 in db, plus the Node client in unit. Independently checked unique inventory membership, PHP-first ordering and lexical sorting through both public list commands. Existing InstallationProcess source-marker classification is retained; no exclusions or protected E2E changes appear. List is before DB prerequisite and interpreter dispatch. Unit PHP failure is isolated in a subshell so Node continues; failures remain aggregated and identified. The minimal diff leaves old characterization, E2E, lint and RED bodies intact.

Independently ran the seven approved Python scheduler harness tests on `/bin/bash` 3.2.57: all PASS. Also ran `/bin/bash -n tools/verification/run.sh` and `git diff --check c93e690 a8e6e92`: PASS. The existing harness exercises nonempty inventories and therefore does not catch the blocking case above. It models scheduler dispatch only and is not evidence that the real native verifiers passed.

The real `make verify` at `a8e6e92` is still running in `native-suites-20260907/make-verify-native-runner.log`. At review time the log records test DB reset, migration frontier 15 and architecture seven-rule PASS, and has begun actual unit execution including the three native command members. Complete 22-PHP-plus-Node execution and terminal full-run evidence remain to be inspected; no complete GREEN, full `VERIFY_OK`, integration or launch approval is asserted. Per the implementation agent's coordination, runner/test edits wait until that process is terminal.

Only this review record and external reproduction evidence were written. No runner/test production changes or commit were made by the reviewer. Preserve this record when the correction receives a follow-up review.
