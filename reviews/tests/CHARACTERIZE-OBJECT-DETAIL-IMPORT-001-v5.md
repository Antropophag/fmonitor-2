# Test rereview: CHARACTERIZE-OBJECT-DETAIL-IMPORT-001 v0.2 — oracle v5

- Date: `2026-09-05`
- Reviewer: fresh separately tasked agent `/root/object_detail_import_gate3_v2`
- Test author: a different previously tasked agent
- Reviewed worktree: dirty authoritative worktree at HEAD `5c711f2d26c4618b0f91c2512f91db51999dd311`
- Public seam: `php tests/Verification/characterize_object_detail_import_001_test.php`, invoking the real importer CLI as a child
- Verdict: `CHANGES_REQUESTED`

The reviewer authored none of the reviewed test, specification, production,
migration, approval, or RED-evidence bytes. Execution used only fresh synthetic
Docker fixtures. It did not access VPN, legacy/production data, shared
databases, production credentials, or protected E2E state.

## Pinned identities

```text
a2e9f65a20bd6e33c740c774094508b32a33faa6e0bdf4ace498e31f024e24c9  specs/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001.md
cc9d604301e50d28eb8908380381484ce6dfec9ed56cfd6202924a91c9d5c201  tests/Verification/characterize_object_detail_import_001_test.php
668b51891c956202cc4d6fe0d37287fdf615489e56bdfa66eea087d0d899b449  exact six-line expected normalized transcript, LF after every line
d3a3f5c303a765e1d11501742ddea2c35b83e75542907ad62c6429359f52e5f0  docs/operations/object-detail-import-red-evidence-v5-2026-09-05.md
442fd28a8fe5ea4628068084b0ddfaf3b8d166b58cd26339763b7dbec38791d1  docs/operations/object-detail-import-v02-owner-approval-2026-09-05.md
069f8d75334380b1b0348ab0ed60b508c6152e0cf4f8daa118827c5f79696950  rapid-pilot/import-production-object-details.php
2bc47395cdaf61974c493907a84d8f8f25dc034e10c140a8fd851ca4845ace8a  app/InstallationProcess/ObjectDetailSnapshotSchemaMigration.php
```

The dated owner record approves the exact v0.2 specification hash and
supersedes the stale pending-status prose inside those unchanged spec bytes.

## Blocking finding

### HIGH — acquisition can leak an artifact child or ambiguously created container

Parent ownership is correctly established for the ordinary worker lifecycle,
but `odciAcquire()` does not cover every acquisition failure:

1. Line 184 creates the exact artifact child and changes its mode before the
   function enters the `try` at line 186. If `mkdir()` succeeds but `chmod()`
   fails, `odciFail()` unwinds without entering the catch/release logic, leaving
   `object-detail-<token>` behind.
2. The Docker create operation runs inside the `try`, but `$owned` is populated
   only after `odciDocker(['create', ...])` returns successfully and its stdout
   supplies the ID. If that CLI times out, overflows, loses its response, or
   otherwise throws after the Docker daemon created the exact named/labeled
   container, `$owned` remains null. The catch then removes only the artifact
   child and never inspects the exact container name. A tmpfs-backed container
   can therefore survive a failed acquisition.

Both cases contradict the specification's cleanup-on-success-or-failure rule
and its requirement that incomplete cleanup cannot pass. They occur before the
new worker process-group containment and parent `finally`, so the v5 timeout
probes do not cover them.

Required correction: begin acquisition cleanup before the first owned mutation.
Track artifact-child ownership immediately after successful creation. For an
ambiguous Docker-create result, inspect only the exact previously proven-vacant
name, establish the expected token label and immutable image identity, retain
the returned container ID, and perform the same exact bounded release. Preserve
foreign state if identity cannot be proven. Add deterministic fault probes for
post-`mkdir` failure and response-loss/timeout after exact container creation,
proving no child directory, container, volume, or decoy mutation remains.

## V4 findings verified as corrected

The v5 implementation resolves the findings in
`reviews/tests/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001-v4.md`:

- the parent precreates and retains the exact token, artifact child, container
  ID, immutable image ID, port, and observed volume identities;
- each worker calls `posix_setsid()` before loading the verifier, and the parent
  verifies PID equals PGID before allowing descendant work;
- TERM/KILL targets the owned negative PGID, containing importer descendants;
- a real live-grandchild probe proves group identity, group termination, direct
  worker reap, and disappearance of the grandchild PID;
- a separate supervisor-timeout probe uses a real parent-owned tmpfs container
  and artifact child, then proves parent cleanup independent of worker `finally`;
- the normal parent loop releases exact resources after every worker result or
  exception;
- the listener cleanup guard now covers server creation, address parsing,
  positive connection/accept, behavioral execution, and all acquired socket
  resources; its setup-fault probe executes the real socket closer.

The earlier exact-v12 compatibility, read-only snapshot, grants, tmpfs,
distinct-credential, capture, complete-row, eight-refusal, source-control,
expected-value, and external-CLI seam corrections remain present. Test-owned
DML is limited to fixture setup, scenario arrangement/restoration, and
independent observation; it does not reproduce importer behavior.

## Independent RED and ordinary cleanup evidence

The exact focused command produced the intended genuine RED:

```text
$ tools/verification/run.sh red tests/Verification/characterize_object_detail_import_001_test.php
REGRESSION_FAILURE: real importer attempted runtime CREATE on the exact precreated v12 family under the verified DDL-denied principal
RED_ASSERTION: expected failing behavior observed in tests/Verification/characterize_object_detail_import_001_test.php
[wrapper exit 0]
```

The meta-test completed its containment/resource probes and two distinct normal
workers before comparing their normalized results. The RED follows exact public
v12 creation, compatible read-only inspection through the DDL-denied principal,
and an unchanged full target snapshot. It is caused by the real importer runtime
`CREATE`, not setup or a verifier-authored substitute.

Reviewer post-run checks found the Docker volume list byte-identical to the
pre-run list, no labeled object-detail container, no token child or ambient
decoy, the common artifact root present, and no surviving verifier/importer/probe
process. PHP lint passed and `git diff --check` produced no output. No old Docker
volume was inspected or removed.

These ordinary-path results do not exercise the acquisition failures described
above.

## Verdict

`CHANGES_REQUESTED`

Gate 4 remains paused. Make acquisition failure cleanup complete and sensitive
to post-create response loss, capture RED evidence at the resulting exact test
hash, and obtain another fresh independent Gate 3 review before production
changes.
