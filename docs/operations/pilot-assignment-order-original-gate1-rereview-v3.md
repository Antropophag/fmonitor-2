# Fresh independent Gate 1 rereview v3 — assignment-order original upload

Date: 2026-09-02  
Reviewer: separately tasked agent `/root/assignment_original_v3_review`  
Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v3  
Scope: comprehensive constructibility amendment only; no tests or production implementation reviewed  
Verdict: **CHANGES_REQUIRED**

## Exact reviewed artifacts

```text
174bb47ba47712b2672dcdd9b7efcee2dc41f74d3fc065abf163f8e8fa59bdde  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
d6a5261cbbd7f12c2c8fd5b21f9d23d93040576d0060a0730900d2617901c566  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
78d58088f30220403ff9b2d156c80369599f21b01d2abe1ccbc6b90f2e1cc4b3  openspec/changes/replace-pilot-registration-with-original-upload/design.md
71c3983790da69921f62fab54304b06a084a22ffa5c308f9454fba7321b96b63  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
4aae5261dae5e88152fca865c7fc7bfa3ea54a4974e11df2ea2b83e078981733  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
08a3f37cc6d03e1057f5ceb0347ff53c337a5369bef455b0e23961229c78cbf7  docs/operations/assignment-order-original-upload-gate2-constructibility-gap-2026-09-02.md
```

## Outcome

The v3 amendment closes the five findings from the v2 review in substantial
part. Upload and maintenance now have separate production/verification
factories and exhaustive dependency bundles; maintenance authorization has a
string principal; upload and maintenance repository mutations use typed commit
DTOs; storage exposes staged chunk writes, typed candidate pages, locks and
deletion; evidence has closed versioned schemas; and child workers reconstruct
adapters from serializable configuration while synchronization and result data
use separate bounded file descriptors.

Gate 1 still cannot pass. The published lock lifetime cannot protect an old
finalized orphan while an upload retry reuses it and commits its DB reference,
so the required maintenance safety property is not implementable through the
approved ports. The delta spec also omits one result reason that its own later
scenario and executable enum require.

## Blocking findings

### 1. The digest lock cannot span orphan reuse through the accepting DB commit

The contract says maintenance must take a digest-scoped lock, recheck the DB
reference and delete only an unreferenced blob. It also says an upload retry may
reuse a verified orphan under the same lock. But the upload application receives
only `AssignmentOrderOriginalPrivateContent` from
`AssignmentOrderOriginalPrivateStage::finalize()`. That content owns no lock or
lease and has no release operation. The only exposed lock is acquired explicitly
by maintenance, while `AssignmentOrderOriginalDigestLock::release()` therefore
cannot be held by the upload application through `commitAccepted()`.

For an orphan older than the cutoff, this permitted interleaving remains:

1. upload retry verifies/reuses the finalized content and `finalize()` returns;
2. maintenance acquires its lock, sees no committed reference and deletes it;
3. upload commits a revision referencing the now absent private content.

The one-hour cutoff does not close this race because reuse is specifically for
an already old orphan. Internal locking only inside `finalize()` also cannot
close it, because it necessarily ends before the subsequent repository commit.
The result violates the normative accepted blob/fact consistency and the claim
that reconciliation deletes only repeatedly proven unreferenced content.

Publish one exact coordination model that spans reuse/finalize, DB commit and
rollback: for example a typed finalized-content lease held by upload until the
commit outcome is resolved, with explicit release/ownership and maintenance
using the same exclusion domain. Define the failure and cleanup mapping for
lease acquisition/release. A RED author must not invent this cross-resource
critical section.

### 2. The delta result matrix excludes `TARGET_NOT_FOUND`

The executable result matrix and PHP enum include
`CONFLICT/TARGET_NOT_FOUND`, and the delta's later “Unknown target” scenario
requires it. But the delta Requirement “Result DTO и stable outcomes” gives an
exhaustive `CONFLICT` reason list containing only `SEMANTIC_COLLISION`,
`STALE_REVISION`, `TARGET_NOT_CURRENT` and `INITIAL_ALREADY_EXISTS`.

Add `TARGET_NOT_FOUND` to that normative result list. As written, a verifier
cannot satisfy both the closed matrix and the required unknown-target scenario.

## Prior-finding disposition and non-blocking observations

- **Maintenance construction:** resolved. Both factories, the dedicated
  maintenance authorizer, clock, storage/reference/request repositories and
  observer/fault/log dependencies are explicit.
- **Maintenance enumeration:** resolved apart from the cross-resource lock
  lifetime above. Candidate ordering, cursor, cutoff, bounded page, typed lock,
  reference recheck, delete outcomes and count derivation are specified.
- **Two-worker IPC:** resolved. Config is serializable, each child reconstructs
  its own real adapters, command/result/barrier use separate FDs, READY/RELEASE
  framing and five-second deadlines are exact, and parent cleanup/reaping is
  mandatory.
- **Repository payloads/evidence:** resolved. Accepted, attempt and maintenance
  mutations are typed and evidence JSON schemas are closed and versioned.
- **Streaming ownership:** resolved. The application writes bounded chunks to a
  storage-owned stage, with typed abort/finalize/close and ordered observer/fault
  surfaces. `completedBytesForInspection()` is bounded by the 20 MiB command
  limit and does not revert acquisition to a caller-owned whole-file API.
- Maintenance pagination advances past retained/failed candidates. A subsequent
  new run beginning at `cursor=null` can revisit them, but Gate 2 evidence should
  state this explicitly rather than retrying the terminal partial request ID.

## Verification evidence

```text
$ php -l <concatenated normative PHP blocks>
No syntax errors detected

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check
PASS (no output)
```

The working tree was already heavily dirty. This reviewer changed no reviewed
specification, OpenSpec artifact, test or production file; only this append-only
review record was added. Task 1.6 must remain open, owner exact-hash approval v3
must not be requested, and Gate 2 remains blocked until a fresh rereview approves
the amended exact hashes.
