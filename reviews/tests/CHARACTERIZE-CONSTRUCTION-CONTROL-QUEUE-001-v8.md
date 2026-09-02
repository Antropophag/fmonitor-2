# Independent Gate 3 test rereview v8 — CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001

- Date: 2026-09-02
- Reviewer: separately tasked agent `/root/construction_queue_test_rereview_v8`
- Test author: separately tasked agent; not this reviewer
- Reviewed state: dirty shared worktree; exact content hashes below
- Verdict: **CHANGES_REQUIRED**

The reviewer did not edit the executable specification, reviewed test,
test-support helpers, RED evidence or production code. This append-only review
record is the reviewer's only change to the slice.

## Findings

### 1. Blocking — PID/thread binding is still helper-authored, and teardown population is incomplete

The pre-dispatch population comparison now correctly queries all four owned
usernames and requires exactly `_s`, `_a`/`_b`, or `_x`. This closes the v7
extra-connection gap at that one boundary. However, the claimed independent
PID binding compares two values supplied by the future reviewed helper itself:
its readiness manifest supplies `pid`/`slot`/`nonce`, and its MariaDB client
supplies the matching `_fm2_pid`/`_fm2_slot`/`_fm2_nonce` connect attributes.
The outer observer reads those values independently, but does not independently
derive that the declared PID owns the socket represented by `PROCESSLIST_ID`.
A helper can therefore make a different process open the connection while
copying a live PID and the same nonce into both self-authored channels.

Population is also not proved closed after teardown. The serial loop waits only
for the expected slot and then checks only `_s`; concurrent teardown does not
require the complete four-account population to become empty. The `_x` path
writes its teardown release but never waits for `_x` connection disappearance
before cleanup. Exact live PID-to-socket/processlist evidence and a full
all-owned-accounts zero-population check after every teardown are required.

### 2. Blocking — filesystem/session identity omits the roots and mishandles special entries

`ccqTree()` now records uid/gid, mode, device, inode, link count, size and all
three timestamps for descendants, which is positive progress. It does not
record the identity of `$artifactChild` itself or of the separately named
`sessions` directory itself: `RecursiveDirectoryIterator` only contributes
their children. An empty session directory therefore fingerprints as `[]`, so
chmod/chown/replacement/timestamp changes to that directory pass. The same is
true for the owned tree root. In addition, entries are classified with
`isDir()` and otherwise hashed with `hash_file()` without an explicit symlink
or other-file-type rejection/identity branch, so an introduced link is not a
safe complete tree identity.

The per-group filesystem comparison also deletes every `protocol/*` entry and
`request.log` from both snapshots rather than validating their expected exact
identity/evolution. At minimum, the contract's owned tree and owned session
directory roots must be explicitly fingerprinted, and all possible entry types
must be bounded rather than followed or silently excluded.

### 3. Resolved from v7 — exact grants are now checked

`ccqAssertSelectOnlyGrants()` parses MariaDB-escaped database and table
qualifiers, requires the exact configured database, requires all and only the
nine declared tables plus one global `USAGE`, rejects extra grant rows, and
compares complete normalized profiles across `_s`, `_a`, `_b`, and `_x`. This
v7 blocker is resolved statically.

### 4. Blocking — pre-drop ownership validation is not exact or complete

Cleanup now re-enumerates exact names and account/host pairs, validates grants,
blocks DROP on a partial/unexpected namespace, attempts all authorized drops,
and surfaces cleanup errors as `SETUP_FAILURE`. Those are material corrections.
But table ownership is accepted when `SHOW CREATE TABLE` merely contains the
expected name and `ENGINE=InnoDB`. Any unrelated/replaced InnoDB table with an
expected token name passes and is dropped. The test already holds the exact
fixture definitions/state and must compare against an independently retained
ownership manifest or exact expected definitions before destructive cleanup.

Artifact ownership is validated only after SQL drops have begun and only by
parent realpath, basename, directory and non-symlink checks; its complete
expected tree/session ownership is not validated before recursive removal.
Thus the RED evidence statement that the complete table/account/host/grant set
is validated "before any SQL or artifact removal" is stronger than the test.

### 5. Blocking — the public-response acceptance matrix remains substantially under-asserted

The test does not yet prove several normative observations independently:

- raw log `method` and `raw_target` are only required to be strings, not matched
  to the exact case/path/query; request nonces are helper-authored, not generated
  by the meta-test;
- exact header sets are not enforced, duplicate/extra headers are collapsed or
  accepted, and `Content-Type`, both CSP variants, forbidden `Retry-After` on
  401/403, HTML GET length, and GET/HEAD header parity are incomplete;
- denied/inactive/unauthenticated statement windows are not checked for absence
  of queue-owned case/event/operation reads;
- the small page does not associate exact PTO `data-completed` values and
  no-inspection labels with the required rows, reject foreign/non-positive
  checklist links, or prove the required activity tokens;
- pagination navigation links and the required separate source inspection of
  `control-queue.js` are absent;
- repeat/concurrent equivalence compares only object IDs, not byte-equivalent
  normalized projection tokens;
- statement auditing scans only `SQL_TEXT` for a short verb list and does not
  reject null/truncated/unclassifiable events, history gaps/overflow,
  transaction-write forms, or all stored-program invocation forms as specified;
- `ccqAssertAudit()` is dead code, and no healthy deliberate expectation
  perturbation is demonstrated. The resource-owning fake is currently allowed
  to count as rejected when it fails at malformed readiness/fixture existence,
  before exercising the corrected PID, challenge, response, or mutation guard.

These omissions allow plausible wrong implementations to pass and therefore
block Gate 3 independently of the four v7 corrections.

## RED and static verification

Canonical focused reproduction remains correctly classified as environment
setup failure before mutation, not as behavioral RED:

```text
timeout 35 php tests/Verification/characterize_construction_control_queue_001_test.php
SETUP_FAILURE: performance_schema must already be enabled
exit 2
```

The refreshed evidence records two disposable MariaDB 11.4.7 invocations with
the required startup instrumentation, each failing for the intended missing
worker behavior:

```text
RED_ASSERTION: production HTTP verifier must expose exact positive fixture/accounts before requests; evidence={"tables":[],"accounts":[],"stderr":"ASSERTION_FAILURE: production HTTP construction-control queue matrix is not implemented\n"}
exit 1
```

This is reproducible in principle and the tracer itself is properly RED, but
the incomplete sensitivity and safety assertions above mean it is not yet an
approvable Gate 2 contract.

Fresh static checks passed:

```text
php -l tests/Verification/characterize_construction_control_queue_001_test.php
php -l tests/Support/characterize_construction_control_queue_verifier.php
php -l tests/Support/construction_control_queue_response_only_fake.php
openspec validate characterize-construction-control-queue --strict
git diff --check -- <reviewed test/support/evidence files>
```

## Required corrections

1. Derive PID/socket/processlist/thread ownership from an outer-observed source,
   not two helper-authored assertions, and require the full owned connection
   population to be empty after every teardown, including `_x`.
2. Fingerprint the owned root and session-directory root plus exact safe entry
   types/metadata; validate expected protocol/log evolution rather than broadly
   excluding it.
3. Retain the corrected exact database/table and identical grant checks.
4. Validate exact expected table definitions/state and complete artifact/session
   ownership before any corresponding destructive action; keep cleanup failures
   surfaced as `SETUP_FAILURE`.
5. Complete the exact HTTP/header/projection/denial/source/history/concurrency
   assertions and add a healthy deliberate perturbation that reaches the guard
   it is intended to test.

Refresh RED evidence after those corrections and request another fresh Gate 3
review. Gate 4 remains closed.

## Exact reviewed hashes

```text
f0dadc560979970968e265e8e26f0bfed42101a00dcaaed2f8cc7cf311c0243d  specs/CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001.md
b7b2b8ae6558edcb9e73f4c39d26e010e393668e2982cc3db69235c9f7f2a433  tests/Verification/characterize_construction_control_queue_001_test.php
3434db2b537321c1bcccc6a38281680d228d5a69d08b453bff0db2e6ed3d428a  tests/Support/characterize_construction_control_queue_verifier.php
1feabaa9fd529e8bdb2cf3609fdcc7da18455dbb86d29edcca45cc85f19052b8  tests/Support/construction_control_queue_response_only_fake.php
6e771e081b39b5e61b893d3356118d780234f4a50b85b78021570e6a0ae84c37  docs/operations/construction-control-queue-red-evidence.md
72470225e10f0fababa58b09b796858ec944c299dd8b086bbb1c411f116f089b  stable normalized transcript (six LF-terminated lines)
de7167bc108bdbf3fbd348434c4818324455eb900935c6c974dd3f4ed93e3a02  openspec/changes/characterize-construction-control-queue/proposal.md
bf0ac6a294aa34ef379237208eb6b765117fdef1564a9cc693fa9c0cc615178b  openspec/changes/characterize-construction-control-queue/design.md
a361549f4ab8920218fc4438877811de971da8695cbc608a2ad153cd98aef412  openspec/changes/characterize-construction-control-queue/specs/verification/construction-control-queue-characterization/spec.md
259d04a7f9d414720fb788fa7d21bbb0da5278f169051138ac2baececfe9d12e  openspec/changes/characterize-construction-control-queue/tasks.md
```
