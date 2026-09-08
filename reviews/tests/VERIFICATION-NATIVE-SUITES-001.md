# Test review: VERIFICATION-NATIVE-SUITES-001

- Reviewer: `/root/original_gate3`, independently tasked agent; not author of the specification, test or runner implementation.
- Test author: root implementation agent.
- Reviewed base: `c93e69021b60a828fe3ec4b3df52592375113a7f`, new test identified below; runner remains unchanged.
- Specification: version 0.1, independently Gate 1 APPROVED in adjacent record.
- Public seam: `bash tools/verification/run.sh list unit|db` and `unit|db` in an isolated synthetic scheduler tree.
- Verdict: `APPROVED`.

## Findings

Read the normative specification, Gate 1 record, OpenSpec proposal/design/tasks/delta, actual runner and Python test. The harness copies the actual verification tools into a temporary tree, constructs independently named fixture members, and invokes the public CLI. Its trace-only interpreters model scheduler exits; they do not replace production application functions or intercept native transport, persistence or database behavior. Each test owns a fresh temporary tree and cleans it through unittest cleanup. PATH excludes real Node, making the missing-Node test sensitive to actual executable absence.

Expected membership comes from the specification's explicit example: four PHP unit members followed by one Node member, and two PHP db members. The native extra filename has no SQL marker, so classification cannot accidentally pass through the old heuristic. Exact list output checks ordering, uniqueness and LF; execution traces check every member and interpreter in the same order. PHP plus Node failures remain separately visible and do not stop dispatch. DB failure is placed before another member to prove continuation. Missing directory and malformed list commands require SETUP_FAILURE rather than empty success. No protected E2E or old test exclusions are edited.

One sensitivity gap was found during review and corrected by the author before approval: the original trace script returned on `php -r` before recording execution, so read-only list could have invoked a database prerequisite invisibly. Final test writes an independent interpreter-invocations log before that branch and requires the log to be absent after both list commands. Thus list cannot pass by secretly invoking PHP/Node, including `-r`. The existing path inventory also detects files created by list. File-call execution traces remain separate from prerequisite invocations.

No remaining blocking findings in this bounded scheduler test. The harness proves dispatch, not actual native verifier outcomes. The specification's real canonical execution of all 22 PHP tests plus Node, existing regression/architecture checks, full verification and independent Gate 5 remain required for delivery.

## RED and identity

Command executed by root: `python3 tests/Verification/verification_native_suites_001_test.py`.

Reviewed initial intended RED in `/Users/antropophag/.local/state/fmonitor2-verification/native-suites-20260907/runner-red-v1.log`, and final amended RED in `runner-red-v2.log` in that directory: seven unittest failures, exit 1. Missing list is explicit SETUP_FAILURE, native members are omitted from actual traces, omitted failing verifiers return success, and missing family is incorrectly accepted. Setup and interpreter harness run successfully. The earlier `runner-red.log` wrong-interpreter-path failure is discarded setup evidence and does not count as RED. This reviewer inspected logs and artifact hashes; no separate test execution is claimed.

```text
2db7bebc7d4d3ded6affe4a6dbe1dd00dfe2bd152237d8447375a46d39e0d08e  specs/VERIFICATION-NATIVE-SUITES-001.md
d6aa99e251f86038cee42ea5475d840bde615effa2982e1e978ccbfc27a5184e  tests/Verification/verification_native_suites_001_test.py
df14abe06006ee05fca451a9a5b11f5de7cdb9b437f99ca850de3223a3584074  tools/verification/run.sh
```

Gate 4 may begin without weakening the reviewed expectations. Only this review record was authored by the reviewer.
