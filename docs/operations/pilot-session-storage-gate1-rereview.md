# PILOT-SESSION-STORAGE-001 — independent Gate 1 rereview

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **CHANGES_REQUIRED**

The reviewer did not author or edit the executable specification, OpenSpec
artifacts, production or tests. This rereview supersedes the first verdict only
where closure is explicit. Gate 2 remains unauthorized.

## Reviewed hashes

```text
adc39faf8d096fb1c628b3231e2dc584e5262f48fe9fbf3a96e8cac4264ad8f0  specs/PILOT-SESSION-STORAGE-001.md
aeada56c9582db6fdcce3fa7a377f2067a366191190cfc166ed03afd77a62484  docs/operations/pilot-session-storage-gate1-review.md
4d926e4cdf39675bb1ae404142b1c1b5db5af8e8f35e6364b6e9ae671b432a04  openspec proposal.md
a3b7abf872ac5d2f8e78956629e3feee46176b7c247b44056d34110aeeba6356  openspec design.md
c0de7be4c07b49a0291a3dee05e55b288c98858ec640cfcb1edcff6ef8dfb8f7  openspec delta spec.md
13071d8611f1fee0c32410d7573891628a8ada3286ea731b6cab24893318bb8e  openspec tasks.md
d5c776a36a27377972c7b5f897ebcd95bead8154612cfdac8b1fbb98a869f406  rapid-pilot/LocalAuth.php
33b298c2f28a7c9ee493270ded4b7d54c9192cd0a1d529ea4a7daeb5a7697f1a  rapid-pilot/UserAccessView.php
b075db40047c604e5f71f992379e2caeafcf7f945acb80062d9b62b645008727  compose.yaml
```

## Closed findings

- Anonymous/new IDs are now specified as adapter-owned 64 lowercase hex from
  exact `random_bytes(32)`, with independent 16-byte stage/tombstone tokens,
  eight collision attempts, `ID_COLLISION` and `ENTROPY_FAILED`. Request values
  cannot select a new ID.
- New anonymous publication and regeneration use hard-link no-clobber rather
  than overwrite-capable rename. Existing same-ID writes remain atomic rename
  under that ID lock. Tombstone publication is also no-clobber and no session or
  tombstone target may be overwritten.
- Start distinguishes null, valid missing and invalid grammar. Missing supplied
  ID remains unauthenticated without new cookie/file; regeneration old-missing
  is stale unavailable; destroy old-missing is idempotent success with deletion
  cookie.
- A closed unavailable list and internal-only fresh 12-hex correlation are now
  present; the exact response explicitly has no correlation header.
- Secure cookie selection now uses only exact outer-trusted
  `FMONITOR_TRUSTED_REQUEST_SCHEME=https`; `http` omits Secure, absent/other
  fails configuration, and raw forwarded/client headers are ignored.
- GC now has a 10,000-entry ceiling, oldest-mtime then binary selection of at
  most 100, explicit overflow failure and an intended lock-file retirement
  rule. This closes the simple first-100 prefix-starvation defect.
- All earlier path/mode/file grammar, write/durability, tombstone crash states,
  response buffering/headers/redaction, routes, two-consumer ownership,
  restart, cleanup and Gates 1–5 remain intact.

## Remaining findings

### R1 — regeneration API and collision retry contradict lock ownership

Section 2 says no caller supplies a new session ID, but section 5 still defines
`regenerate(old,new,data)`. Make the public adapter operation exact—for example
`regenerate(old,data) -> OK(newId)`—with new ID generated internally. Otherwise
tests and consumers cannot know whether `new` is hostile input or an internal
candidate.

More importantly, step 3 handles EEXIST after old has already been unlinked by
generating a wholly new ID. At that point the operation holds locks for old and
the original new candidate, not the replacement candidate. Publishing the
replacement without its hash lock violates “every write/regenerate of ID locks
that ID”. Acquiring it after old may violate the required binary lock order and
deadlock against another two-ID operation.

Use an implementable sequence:

1. generate candidate before mutation;
2. acquire old+candidate locks in binary order;
3. under those locks validate old committed and candidate committed absent;
4. if candidate exists, release both and retry from step 1, before staging or
   old invalidation;
5. only after a collision-free locked candidate stage/fsync and perform
   old→tombstone/unlink then hard-link publication.

With cooperating writers, EEXIST after the locked absence check is then a
same-uid/out-of-contract swap or typed publish failure, not a post-invalidation
retry to an unlocked ID. Pin the exact failure/crash outcome and maximum eight
pre-mutation retries.

The anonymous `writeCommit` collision branch likewise must state that the newly
generated replacement ID lock is acquired before its stage/target operation and
the obsolete candidate lock is released safely.

### R2 — lock timeout has two incompatible category literals

Section 3 says timeout → `SESSION_LOCK_TIMEOUT`, but the closed enum contains
`LOCK_TIMEOUT` and no `SESSION_LOCK_TIMEOUT`. Choose one exact safe category and
use it in lock behavior, enum, fault matrix and log. A closed enum cannot contain
a different spelling from the normative primitive outcome.

### R3 — GC cannot associate random stage/tombstone files with an ID lock

The lock retirement rule says a lock file is eligible only when its committed/
stage/tombstone “targets” are absent. Committed and lock names share the session
ID hash relationship, but stage and revoked names contain only independent
random tokens. No filename or metadata links them to a session ID, so the GC
cannot prove which random stage/tombstone belongs to a given lock.

Choose an exact implementable relation:

- include lowercase session-ID hash in stage/revoked grammar and validate it;
- or define a safe internal metadata format whose ownership/content is
  independently authenticated before association;
- or retire a stale lock only when no committed file for the ID and no stage or
  tombstone exists anywhere in the instance (conservative but observable), with
  exact 10,000 ceiling behavior.

Then specify how GC derives the corresponding ID lock for stage/revoked cleanup,
especially tombstones after the old committed name has gone. It must not delete
an active artifact using only age/name while a cooperating operation owns its
relevant lock. Add mixed >100 file tests for association, oldest ordering,
locked/newer/unknown/wrong-mode preservation and lock retirement.

## Feasibility notes

Hard-link publication is available on the supported same-filesystem Linux
host/image and gives the required EEXIST no-clobber behavior. Directory fsync
and link/unlink fault mapping remain testable. The old→tombstone hard link plus
old unlink still gives the intended crash states once candidate locking is
fixed. The trusted scheme variable and public/internal correlation split are
also implementable at the outer boundary.

## Verification

```text
openspec validate define-pilot-session-storage-contract --strict
Change 'define-pilot-session-storage-contract' is valid

git diff --check -- specs/PILOT-SESSION-STORAGE-001.md
exit 0, empty output
```

## Verdict

**CHANGES_REQUIRED.** Most first-review findings are closed, but regeneration
must generate/lock collision candidates before invalidating old state, the lock
timeout category must be unique, and GC needs a real stage/tombstone-to-lock
association. Correct those exact points and request a fresh independent review;
Gate 2 remains prohibited.
