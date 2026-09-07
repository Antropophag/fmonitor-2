# Operational review: PILOT-LOCAL-TRUSTED-SCHEME-001

- Reviewer: `/root/original_gate5`, independently tasked agent; did not perform recovery or author its evidence.
- Operator: root implementation agent.
- Configuration source: `87c618d4eba3b69b06b36a4d3556eb7d5f55a920`; unchanged external recipe approved in `PILOT-LOCAL-TRUSTED-SCHEME-001.md`.
- Verdict: `APPROVED` — bounded local Users503 configuration recovery and preservation evidence complete.

## Findings and independent checks

No blocking findings. Read the actual recreation log, before/after container inspection, raw state snapshots, preservation summary and authenticated route evidence. Independently parsed and compared `state-before.json` with `state-after-before-smoke.json`: the entire captured structures are equal. All 51 tables retain exact DDL and sorted rows; the active manifest is unchanged, including nonce; all 3,308 prior session-file hashes are identical and no new session appears before smoke. This satisfies the stronger preservation result promised by the no-bootstrap recipe.

Container inspections retain the exact image ID `sha256:8fa07372e5076ca5d488a8c8cde42257d832c9fb7a199e0813e95d78a829ec8b`, named volume `fmonitor2-local-preview_pilot-state` and existing read-only healthcheck mount. The additional mount is the approved read-only runtime wrapper. The recreation log reports only the pilot service recreated and healthy; the operations record identifies `--no-deps --no-build --pull never`. No new-source image, migration, bootstrap, import or volume reset is represented by this operation.

Authenticated smoke evidence changes Users GET from the prior 503/21-byte unavailable response to 200 at `/pilot/admin/users`, with a 12,456-byte page. The successful login reaches `/pilot/objects`; roles and subsequent objects GET also return 200. An earlier login POST remains on the login page, and is not counted as successful authentication. Route execution belongs to the operator; this reviewer inspected its captured results rather than sending another login request.

Independently compared the post-smoke snapshot: all existing session hashes, manifest values and every table's rows remain exact. The sole DDL-string difference is the AUTO_INCREMENT value on the auth-attempts table; removing only that numeric allocator value makes its before/after DDL identical. Its rows remain empty. This difference occurs after the clean preservation checkpoint during ordinary task-owned login activity, not during recreation; no domain or authorization row overwrite is concealed.

Evidence root: `/Users/antropophag/.local/state/fmonitor2-verification/users-preview-20260907`, especially `container-before.json`, `container-after.json`, `state-before.json`, `state-after-before-smoke.json`, `state-after-smoke.json`, `preservation-summary.json`, `recreate.log`, `smoke-summary.json`, `routes-before-recovery.json` and `routes.json`. Sensitive raw snapshots remain external.

## Scope

The local configuration recovery is complete. Preview still runs the old image; original-upload/reference and other current repository features were not deployed. This review does not assert full `VERIFY_OK`, parent migration/application completion or launch readiness. Only this review record was written; no runtime, source or test mutation, login, recreation or commit was performed by the reviewer.
