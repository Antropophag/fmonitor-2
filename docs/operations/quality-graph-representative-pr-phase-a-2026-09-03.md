# Quality Graph representative PR phase A

- PR: https://github.com/Antropophag/fmonitor-2/pull/1
- Base: `main`
- Initial head: `99a934fcd65d5d837f0fc84080f291b5fbccde19`
- Merge policy: open, `DO NOT MERGE`; no branch-protection change.
- Existing harness local outcome: FAIL with `architecture-check,unit-test,db-test,e2e-test` on main-equivalent application files.
- Quality Graph focused tests: PASS.
- `make quality-graph-validate`: PASS, graph digest `d19c74290776c2f4a5cf63480a3a6719d2b10c096bc991cdea488d14afc76401`.

## Bootstrap observation

The PR `opened`, then a deliberate close/reopen generated `reopened`; GitHub reported no checks and no workflow runs for either event. Actions are enabled (`allowed_actions: all`), while the Actions workflow listing is empty because no workflow exists on `main`. This push adds the observation and creates a `synchronize` event as a final phase-A trigger probe.

Until a real runner produces node artifacts for the PR head, phase A provenance/parity is not proved. Publisher phase B is also unavailable because `workflow_run` requires the publisher workflow on the default branch. No absence of checks is interpreted as success.
