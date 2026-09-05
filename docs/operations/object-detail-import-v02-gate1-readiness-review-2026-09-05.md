# CHARACTERIZE-OBJECT-DETAIL-IMPORT-001 v0.2 — independent Gate 1 readiness review

Date: 2026-09-05. Reviewer: `/root/selection_contract_reconciliation`.
Reviewed repository HEAD: `4db6c004d444b129c56b33879e7f9cc896dc3762` plus the uncommitted
root-authored v0.2 executable/OpenSpec amendments identified by hashes below.
The reviewer authored none of the reviewed artifacts.

Verdict: **CHANGES_REQUIRED**. This is a technical consistency/readiness
review, not owner approval, Gate 1 approval, RED, Gate 3 or implementation
permission. The existing table-transfer approval is not being reconsidered.

## P0 — the declared public oracle excludes the required dry-run actions

The executable spec's “Public oracle seam” says every behavioral action SHALL
run the real child with literal arguments including `--apply`. The new normative
schema-precondition axis requires four dry-run failures and one clean dry-run,
which by definition must omit `--apply`. Both cannot be true. An implementation
could follow the public seam and never exercise dry-run, or follow the later
axis and violate the universal public-seam requirement.

Replace the universal argv with two exact forms:

```text
php rapid-pilot/import-production-object-details.php --captured-at=<value> --page-size=1 --apply
php rapid-pilot/import-production-object-details.php --captured-at=<value> --page-size=1
```

State that serial mutation/replay/conflict/source-rejection cases use the first,
while the clean dry-run and four dry-run precondition cases use the second.
This must be coherent in the executable spec and delta spec before owner review.

## P1 — owned filesystem evidence and cleanup targets are deferred to Gate 2

The spec requires an exact artifact child and says the Gate 2 test will
enumerate the owned artifact files. Gate 1 therefore does not yet define what
the verifier may create/delete or what exact evidence survives until
observation. The private-server section similarly says “private evidence” stores
image identity, child exit/stdout/stderr and snapshots without naming files or
stating whether this evidence is ephemeral per scenario or retained after the
run. This leaves cleanup scope and the “none survives” assertion dependent on
the future test implementation.

Close this by listing the exact relative files under
`object-detail-<token>/` (or state that there are no filesystem evidence files
and all evidence remains in bounded parent memory), their creation modes and
their cleanup order. Reconcile “private evidence saves” with the requirement
that no verifier-owned artifact survives. The ambient decoy must be outside the
owned child and its exact relative identity must also be fixed before RED.

## P1 — bounded process/container/listener protocol has no numeric bound

The contract correctly forbids sleeps as an oracle and requires reaping, but it
does not give a maximum for container readiness, each importer child, listener
probe/accept, graceful stop or forced removal. A hung source handshake or Docker
startup can therefore satisfy the prose indefinitely, and two test authors can
choose materially different failure classifications. Define fixed monotonic
deadlines and the exact escalation from terminate to kill/remove. Timeout before
an asserted child result is `SETUP_FAILURE` only for container/listener setup;
timeout of a healthy-started behavioral child should be stated explicitly as
`REGRESSION_FAILURE` (or another single chosen outcome). Generated PID/port still
must remain outside normalized output.

## Confirmed constructible and coherent points

- A new disposable container can contain a database literally named
  `fmonitor2_demo`; the importer endpoint guard checks that name, a nonempty host,
  port 1..65535, sentinel values and actual `@@hostname`, not membership in the
  shared demo server. Supplying the inspected immutable image ID to Docker and
  publishing an ephemeral port on `127.0.0.1` is constructible. The contract
  correctly forbids the existing shared test container.
- The source database can coexist on that private server under
  `fm2_odci_<token>`. Source SQL reads exactly `fm_fields`, `fm_view_fields`,
  `fm_fields_values` and `fm_maintable`; SELECT on those four tables plus USAGE
  is sufficient for its read-only consistent-snapshot transaction.
- After the future precondition is inserted, the stated target grants cover the
  importer's data path: SELECT on cases, generation sentinel and both family
  tables covers ordinary and `FOR UPDATE` reads inside the explicit transaction;
  INSERT on the two family tables covers accepted writes. No UPDATE/DELETE,
  CREATE/ALTER/DROP, global or role grant is needed. `SELECT @@hostname`,
  transaction control and the read-only `information_schema` metadata used by
  `isCompleteCompatible` do not require an extra object mutation grant. Setup
  should compare normalized `SHOW GRANTS FOR` output from the admin connection,
  because a child principal cannot generally inspect arbitrary principals.
- Current apply executes two `CREATE TABLE IF NOT EXISTS` statements before DML.
  Thus exact schema plus the DDL-denied target principal provides the required
  production no-DDL RED; a missing future verifier alone does not. Current
  dry-run bypasses those CREATE statements and connects/reads source first, so
  malformed-family dry-run plus the listener provides the separate pre-source
  ordering RED.
- The loopback listener construction is valid: keep one bound listening socket,
  perform and accept/close one positive control connection, run/reap the child,
  then perform nonblocking accept. A queued connection, even if the client has
  already closed, proves source access. The missing numeric deadlines are the
  remaining issue, not the observation principle.
- Failure mapping is otherwise exact: schema absent/incompatible/inspection
  unavailable after a successful generation guard maps in both modes to exit 2,
  exactly `{"ok":false,"reason":"OBJECT_DETAIL_SCHEMA_REQUIRED"}` plus LF and
  empty stderr; argument and generation errors remain explicitly outside this
  new mapping. Each negative case snapshots schema, rows and decoys before and
  after and requires zero source connections.
- Clean dry-run output has exact key order, exit, newline and zero-mutation
  requirements. Apply/replay outputs and the three pinned rejection categories
  remain consistent with the actual serial importer. Exact PHP exception text,
  path and stack trace are correctly excluded.
- The independently recomputed material hashes match the draft:
  `5fbb37587f0bd1dff238fd1e97972b4e74d9ac4583c875961d9639e6022e0d15`
  for object 451301 and
  `5f3d14bbdc7708430233092240bc82fe3ab3e28867ef1e4b7bc14fbf542e2b89`
  for missing object 451302.
- The package preserves the listed UNKNOWN exclusions. Least-privilege fixture
  construction is infrastructure isolation evidence and does not approve a new
  FMonitor target-authorization product policy. It does not seed TEST-USER,
  import production-linked data, resolve quarantine transitions, or touch
  concurrency/cutover/premium semantics.
- OpenSpec strict validation passes and `git diff --check` passes at review time.

## Gate consequence

Resolve the argv contradiction and exact cleanup/deadline issues, update all
affected v0.2 artifacts coherently, and request a fresh independent technical
readiness review. Only then is the exact candidate ready to be presented for
the mandatory owner Gate 1 approval of the serial regression oracle. No new
product-policy question is identified, and the already approved canonical
table transfer must not be requested again.

## Exact reviewed SHA-256

```text
87ebfb47d6ca0c4d5c5b607f2cb6ee276466a72e68b4ff85e40c13c00e8ee66f  specs/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001.md
bc6c8ec30aa9a17d0074ebe3e5273febf3961534da1467d65fd7324ba0da3691  openspec/changes/characterize-object-detail-import/proposal.md
cf72aa312f86f1e2923b531fdad9c741dd5b78a45a50faeba6740e3749dc9c11  openspec/changes/characterize-object-detail-import/design.md
2e3e23e62b38a0b2093cf17785d6cb916eb566690817bb7a674c9e7825947716  openspec/changes/characterize-object-detail-import/tasks.md
f69b02e090df4a888a69b2e9c53cce262162b16314be2d4d8e37327ea7cfac0c  openspec/changes/characterize-object-detail-import/specs/verification/object-detail-import-characterization/spec.md
e9861ca0bc9f3e859f49961383e1931d7c92098a79534d4bec52f8ab2ef4812b  docs/operations/object-detail-import-authority-reconciliation-review-2026-09-05.md
be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40  specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md
069f8d75334380b1b0348ab0ed60b508c6152e0cf4f8daa118827c5f79696950  rapid-pilot/import-production-object-details.php
7f4d2ff47c3e0b69f0a4e44583901a07bff4fde5b851c601b310d6d438753a96  rapid-pilot/legacy-migration/WorkforceCatalogReconciliationCandidate.php
0de0578ade9509923a322464c52e5958c5038a3a09ec3063b8be4a6de255918e  rapid-pilot/AGENTS.md
```
