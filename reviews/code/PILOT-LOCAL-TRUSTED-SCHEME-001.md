# Code review: PILOT-LOCAL-TRUSTED-SCHEME-001

- Reviewer: `/root/original_gate5`, independently tasked agent; not implementation/test/recipe author.
- Implementation author: root implementation agent.
- Reviewed source: `87c618d4eba3b69b06b36a4d3556eb7d5f55a920`.
- Specification: v0.2, final Gate 1 v3; independent Gate 3 APPROVED.
- Verdict: `APPROVED` for the local configuration fix and the concrete pending runtime-only recovery recipe below.

## Findings

No blocking findings. Production configuration adds only literal `FMONITOR_TRUSTED_REQUEST_SCHEME: http` to the existing direct loopback HTTP profile. Published port/address, authorization, native session ownership and backend fail-closed validation remain unchanged. The fixture's ordinary env argv preserves explicitly supplied empty strings before executing the same PHP/router process; it introduces no application reconstruction or native interception.

Reviewed the real effective-Compose/native-router test and its approved RED. Positive configuration comes from actual Compose output despite conflicting ambient https. Successful native Users GET verifies rendered action tokens against owner-committed administrator session data and checks unchanged database/private-original state. The missing-scheme plus forged forwarded-http control retains exact 503 with no session-token mutation. The correction does not grant permissions or accept a client-supplied scheme.

Inspected `green.log`, `fixture-regression.log`, and session-fault evidence: native Compose/missing controls pass with successful cleanup, original HTTP flow/replay/correction passes, and native session fault handling passes. The operations record supplies session-token regression, changed lint/diff and seven-rule architecture results. Earlier setup attempts are explicitly excluded from GREEN. Independently ran changed fixture PHP lint, runtime `sh -n`, snapshot PHP lint, and commit diff-check: PASS.

## Pending runtime recipe review

Independently read the actual running preview container's entrypoint and `rapid-pilot/start.php`, not only repository copies. Its immutable image ID is `sha256:8fa07372e5076ca5d488a8c8cde42257d832c9fb7a199e0813e95d78a829ec8b`, user `fmonitor`, working directory `/workspace/fmonitor-2`. The external wrapper repeats both existing socat listeners and executes that existing start.php. It omits migration, bootstrap, import and directory creation. The script requires the existing home path; startup continues to consume the existing ready manifest. No new application source is mounted.

The candidate override retains the old image tag, owned state volume and approved healthcheck mount, adding only the read-only runtime wrapper mount/entrypoint. Actual effective candidate config has the literal http binding inherited from the reviewed base Compose. Ordinary bootstrap would update more than the permitted nonce, so omitting it is appropriate for this already initialized contour; the approval does not widen the allowed database delta. With this recipe the intended comparison is stronger: all DB DDL/rows and manifest values unchanged.

The read-only snapshot collects every current database table's SHOW CREATE TABLE and sorted complete rows in a consistent read-only transaction, then existing session-file hashes and decoded active manifest. It closes its own DB transaction/connection and writes no domain state. Before/after snapshots must succeed and be compared before any new smoke login. Preserve the same immutable image ID and volume identities during recreation, with no build/pull, migration or bootstrap. Existing session hashes must match; subsequent task-owned smoke login may create only normal session/login effects. The recipe does not establish those outcomes until the actual operation and comparison run.

Reviewed external artifact identities under `/Users/antropophag/.local/state/fmonitor2-verification/users-preview-20260907`:

- `runtime/start.sh`: SHA-256 `a2b0f21a60ce63b865d0cb05a99bb857d486697d2909d8bd230ae23465a5667d`.
- `candidate.override.yaml`: SHA-256 `87cca640f0c1e7f6f4724cb0af561f0c585d732f1146e585e123440ad5379568`.
- `snapshot.php`: SHA-256 `2b3a600548b29a30d98e5435014bf28ade3d369cb1df7273fc10cf2da64504f1`.

## Required changes and completion boundary

None before applying this reviewed recipe within the already authorized owned-local recovery scope. This is a pre-operation review, not a claim that preview Users is recovered or state preservation has been demonstrated. Actual recreation, exact preservation comparison, health/login/users/roles smoke and cleanup evidence remain required afterward. No new-source deployment, remote mutation, full `VERIFY_OK` or launch approval is implied. Only this review record was written; no configuration, runtime artifact, source or test edits, commit or recreation were performed by this reviewer.
