# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 domain correction review v2

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/command_gate5_domain_gate3_v3`
- RED author: separately tasked agent `/root/command_gate5_red_domain`
- Reviewed commit: `183171ac95f4c0ef71ecb20b421e0fff8518271b`
- Reviewed production: `6c4fb5b70065cabb19adab23f6004714c1f0699a`
- Gate 5 finding record: `fa97cfd5f5ca900424bcf9863eff66ed6b701a2e`
- Prior Gate 3: `4d88ec79d3dde4d1976455a453493e3ea9762e1c`, `CHANGES_REQUESTED`
- Contract: approved `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v54
- Verdict: **APPROVED**

This reviewer did not author the executable tests, RED evidence, production
implementation, or planning artifacts. This append-only record supersedes only
the prior Gate 3 verdict for findings 3–6. Storage/parser findings 1–2 and their
separate correction are excluded from this review.

## Prior finding resolved

The prior review found that the in-process lifecycle assertion could not detect
worker bootstrap fabrication. The exact reviewed increment now invokes the real
`AssignmentOrderOriginalVerificationWorkerBootstrap` entry through its public
command/barrier-in/barrier-out/result protocol. Its valid but unauthorized
command must terminate at authorization, before stream, stage, finalize, or the
real `AFTER_PRIVATE_FINALIZE_BEFORE_COMMIT` callback. Therefore the only valid
barrier oracle is zero READY bytes. Current production instead fabricates a
finalized entry and lock, emits READY, and binds a no-op lifecycle observer; the
new test fails directly on that fabricated READY.

The parent does not create a finalized fact or lock. It writes RELEASE only
after observing the already-invalid nonempty READY, solely to let the broken
worker finish. This cannot make the expected empty channel pass. The final
private-root assertion independently rejects fabricated metadata, locks, and
content.

## Worker harness review

The four dedicated child protocol descriptors 3–6 are separate AF_UNIX socket
pairs for command, release, READY, and result; stdout/stderr are separate pipes.
After the command write end is shut down, every child-output endpoint (READY,
result, stdout, stderr) is nonblocking and drained in one bounded three-second
loop. The loop captures the child exit code only at the observed transition to
not-running, then drains remaining bytes nonblocking. The corrected oracle is
exact: exit `0`, one exact authorization-denied result tuple, no READY, empty
stdout/stderr, and no private artifact.

Every open endpoint is closed in `finally`. If the child remains live, teardown
sends SIGTERM, polls for at most 500 ms, then sends SIGKILL, polls for at most a
further 500 ms, and calls `proc_close()` to reap it. Thus neither an absent
READY/result nor a broken child can turn the RED into an unbounded test. Database
and filesystem cleanup are restricted to random-token targets checked against
exact regular expressions before creation.

## Findings 3–6 sensitivity

The three approved executables retain independent, public-seam sensitivity:

- one shared test-owned repository accepts INITIAL for assignment orders 81 and
  82 independently, detecting a global empty-root lineage query;
- authorization, terminal-request, and fingerprint `UNAVAILABLE` require exact
  `FAILED/PERSISTENCE_FAILURE/true` before stream reads;
- rolled-back and throwing attempt persistence must replace an otherwise
  unaudited rejection with the same technical tuple, exactly once;
- malformed duplicate release, missing release end, reversed release dates,
  and release after order date are inserted into constraint-free source tables
  and must invalidate the complete composition rather than a filtered subset;
- a missing-table real MariaDB accepted commit must be `ROLLED_BACK`, never CAS
  `CONFLICT`;
- correction lineage/composition drift requires exact semantic collision before
  stream access;
- the in-process real finalize callback requires its returned lease to be live,
  while the separate worker negative test proves READY ownership by that callback.

Expected tuples, identities, call counts, and empty-channel values are literal
test-owned values. The tests do not derive expectations from production. The
MariaDB negative composition cases rely on existing positive composition suites
for completeness; within this correction they correctly distinguish malformed
all-row input from the reviewed partial-row filtering defect.

## Independent RED reproduction

All three files pass PHP lint. `git diff --check 183171a^` exits zero. Exact
commands against the reviewed production fail after successful bootstrap for
the intended behavioral reasons:

```text
$ php tests/InstallationProcess/assignment_order_original_gate5_domain_red_001_test.php
Authorization UNAVAILABLE has the only contract-valid technical tuple.
Expected: FAILED / PERSISTENCE_FAILURE / true
Actual:   REJECTED / PERSISTENCE_FAILURE / true
exit 255

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/assignment_order_original_gate5_mariadb_red_001_test.php
Malformed all-row composition case 0 is invalid, not filtered into a valid snapshot.
Expected identity: NULL
Actual identity:   composition-81-v1
exit 255

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/assignment_order_original_worker_post_finalize_negative_001_test.php
Unauthorized command emits no post-finalize READY because no actual finalize lifecycle event occurred.
Expected: ''
Actual:   'READY 00000000-0000-4000-8000-000000000560\n'
exit 255
```

The worker command completed in under one second on the known-bad production;
its bounded paths were also inspected independently rather than inferred from
that quick failure.

## Exact reviewed hashes

```text
f788e80143c25cda53fb02d79a4089248ce6079fcf1586b6aeb65b53d5ba6486  tests/InstallationProcess/assignment_order_original_gate5_domain_red_001_test.php
40545c57c70239975062e0e677d3f4f82e89e5b0ef7944ffa270949468a8c916  tests/InstallationProcess/assignment_order_original_gate5_mariadb_red_001_test.php
84cd4b2a8a8cf751eb461de1fc1a48f042194489e8bd0dde045ba25c1043dea0  tests/InstallationProcess/assignment_order_original_worker_post_finalize_negative_001_test.php
20cdd033a4615e20b3c4597d9cc932ae2fc3faa06b2ce96dd08f42931fb46f5e  docs/operations/assignment-order-original-command-gate5-domain-red-evidence-2026-09-05.md
6f15c749fac17d032bd58e0ff23d5f63994ef0ea53f8599cce346c2847c4df56  docs/operations/assignment-order-original-worker-post-finalize-negative-red-evidence-v2-2026-09-05.md
```

Gate 3 for findings 3–6 is **APPROVED** on the exact reviewed bytes. This
authorizes minimal production correction only; it grants no Gate 5 approval and
does not approve the separately scoped storage/parser tests or implementation.
