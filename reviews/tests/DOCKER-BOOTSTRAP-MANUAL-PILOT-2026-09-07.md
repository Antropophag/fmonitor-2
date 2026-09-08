# Docker bootstrap manual-pilot test correction — independent review

- Reviewer: Codex agent `/root/auth_review`; did not author the test correction.
- Review base / current `HEAD`: `77701c590b540419ccb904b1aacf13c4a7be7186`.
- Verdict: `APPROVED`.

## Exact identity

```text
ee4138ef61fd4623d80ba03bf8c58c52cbf885a0fba008e7fd1480e982af885e  tests/InstallationProcess/docker_bootstrap_manual_pilot_test.php
370588e6094c1cfa6dcb30affaffe5ed2c77eb543a33c1f7022bfae4c861c10e  exact binary diff
```

## Findings

No blocking findings remain. The fixture now points both canonical DB variables
and inherited `FMONITOR_DEMO_DB_*` aliases at its isolated random database. This
prevents suite-level demo credentials from redirecting `rapid-pilot/start.php` to
another database while preserving the same bootstrap and login assertions.

The server is launched through the existing `process_group_exec.php` handshake.
The published PID is independently checked as the process-group ID before release.
Cleanup sends TERM to the owned group, waits boundedly on group and leader liveness,
then sends group SIGKILL whenever the group remains alive even if the leader has
already exited. It waits again, closes every retained pipe and reaps the leader.
The group ID is cleared only after both group and leader are absent. If the first
explicit stop cannot prove absence, `finally` retains the group ID and retries before
fixture teardown. This closes the previously identified descendant-orphan gap.

The RED log reaches the exact stand-bind assertion under contaminated suite
credentials. The corrected focused run reports `docker_bootstrap_manual_pilot_test:
PASS`; the supplied post-run process probe found no port-18092 workers. Existing
owner/invitation/role/sentinel/manifest/mode and login-page expectations remain intact.

## Verification evidence

```text
bootstrap-env-red.log: Expected start bind true, Actual false
bootstrap-env-green.log: docker_bootstrap_manual_pilot_test: PASS
PHP lint: PASS
git diff --check: PASS
```

The reviewer did not rerun the database test while another process owned the shared
DB slot. No production code, stand, persistent data or external state was changed.
