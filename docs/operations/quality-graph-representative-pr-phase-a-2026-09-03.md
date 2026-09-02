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

The synchronize probe did trigger run https://github.com/Antropophag/fmonitor-2/actions/runs/33687521869 for head `6f940539bb6289db9108323399662b6d789aa7a1`. The prior head run `33687490663` was cancelled by concurrency and was not reused.

| Node | Result | Result v0 provenance |
|---|---|---|
| `graph-validation` | PASS | PR `1`; head `6f94053...`; run `33687521869`; attempt `1`; digest `d19c74290776c2f4a5cf63480a3a6719d2b10c096bc991cdea488d14afc76401` |
| `delivery-evidence` | FAIL, `failureKind: command` | exact same PR/head/run/attempt/digest |
| `verify` | dependency-skipped | no result accepted because the blocking evidence node failed |

The failed and passed JSON artifacts were downloaded, inspected, and removed from local temporary storage. Missing final lineage evidence was not converted into success.

Runner provenance and the missing-evidence negative case are proved. Full phase-A matrix and positive parity remain incomplete because repository `main` is already RED. Publisher phase B is unavailable because `workflow_run` requires the publisher workflow/topology on the default branch. No absent or skipped check is interpreted as success.
