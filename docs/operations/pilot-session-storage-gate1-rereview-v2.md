# PILOT-SESSION-STORAGE-001 — independent Gate 1 rereview v2

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **CHANGES_REQUIRED**

The reviewer did not author or edit the executable specification, OpenSpec
artifacts, production or tests. This rereview supersedes prior findings only
where closure is explicit. Gate 2 remains unauthorized.

## Reviewed hashes

```text
4f1df3b35ad272f8959e9643cd210adbf0a1e63ad8ee182ce6c16f8749c2d3e9  specs/PILOT-SESSION-STORAGE-001.md
aeada56c9582db6fdcce3fa7a377f2067a366191190cfc166ed03afd77a62484  docs/operations/pilot-session-storage-gate1-review.md
42395104e440b6f0a3fe22bffd38654c0b75691419cf2a8e76458f74d2cab91f  docs/operations/pilot-session-storage-gate1-rereview.md
4d926e4cdf39675bb1ae404142b1c1b5db5af8e8f35e6364b6e9ae671b432a04  openspec proposal.md
0424de9777518653303a6ca2af96a833ae5bc58c9a9b0552772233c14d8887ef  openspec design.md
fc09441b6be20d624ceada6f908784a8fb21a8fe4c3fcd84e35aa50341164690  openspec delta spec.md
13071d8611f1fee0c32410d7573891628a8ada3286ea731b6cab24893318bb8e  openspec tasks.md
```

## Closed findings

- Public regeneration is now `regenerate(old,data)`; the adapter alone
  generates a 64-hex candidate. Before mutation it acquires old+candidate locks
  in binary order, checks candidate absence, and resolves all ordinary
  collisions by releasing both and retrying before staging/old invalidation.
- After old unlink, unexpected EEXIST is a fail-safe
  `REGENERATE_FAILED`; the operation never retries/publishes an unlocked third
  ID. Hard links provide no-clobber old→hash-associated tombstone and
  stage→prevalidated new publication.
- The timeout literal is consistently `LOCK_TIMEOUT` in primitive behavior and
  the closed safe enum.
- Stage and revoked grammar now embed the exact session-ID hash. GC can derive
  and acquire the same `l-<hash>.lock`; malformed/unassociated artifacts remain
  foreign. Lock retirement has an observable association rule.
- ID CSPRNG, eight attempts, NOT_FOUND/INVALID/stale/idempotent destroy,
  category enum/internal-only correlation, trusted scheme, exact HTTP/cookie,
  oldest-first 10,000/100 GC bounds and previous path/durability/crash/restart/
  cleanup/gate contracts otherwise remain coherent and implementable.

## Remaining findings

### R1 — OpenSpec cleanup requirement contradicts mandatory GC

The executable spec requires production GC to delete expired, exact owned
committed/stage/revoked/lock files under the corresponding nonblocking lock and
fsync the directory. Design risks/decisions also retain tombstone and concurrent
GC behavior.

The updated OpenSpec delta still says:

> Production adapter never removes session directories/files except explicit
> normal session destroy through handler.

That forbids every required GC file deletion. It is not equivalent to “never
removes directories” or “never removes root”. Reconcile the package:

- production never removes root, `sessions`, or instance directories;
- production removes committed files through explicit destroy and may remove
  exact expired owned committed/stage/revoked/lock files only through the
  bounded locked GC contract;
- no other repair/cleanup is permitted;
- test attempt-all root deletion remains a separate task-owned seam.

Update the delta scenario/requirement at a new hash and strict-validate. Owner
approval cannot safely choose between contradictory executable and OpenSpec
retention rules.

### R2 — anonymous write collision retry must explicitly reacquire the replacement lock

Section 4 says anonymous `writeCommit` runs “under ID lock”, but on EEXIST it
generates a wholly new ID/stage and retries. It does not explicitly release the
obsolete candidate lock and acquire the replacement candidate's
`l-<sha256>.lock` before checking/writing its paths.

The general rule that every write locks its ID suggests the intended behavior,
but this security-critical collision branch should be as exact as regeneration:
on EEXIST, clean/quarantine the old stage as specified, release old candidate
lock, generate a fresh candidate, acquire its lock, prove target absent, then
create/write a fresh stage; at most eight candidates. No stage/target operation
may occur for the replacement before its lock. Pin collision cleanup and
`ID_COLLISION` after eight attempts.

This is a narrow executable clarification; it prevents an implementation from
interpreting “retry” as publication to a new unlocked ID.

## Feasibility audit

With those two corrections, the remaining protocol is feasible on supported
Linux host/image PHP: exclusive stage creation, hard-link no-clobber,
link/unlink/fflush/fsync/directory-fsync fault mapping, binary lock order,
current-euid metadata checks and response buffering are testable. Regeneration
crash regions preserve the promised old-valid/neither-valid/new-only states;
destroy and stale start outcomes are exact. Hash-associated GC can safely lock
artifacts and oldest-first selection avoids prefix starvation within the 10,000
ceiling.

## Verification

```text
openspec validate define-pilot-session-storage-contract --strict
Change 'define-pilot-session-storage-contract' is valid

git diff --check -- specs/PILOT-SESSION-STORAGE-001.md
exit 0, empty output
```

## Verdict

**CHANGES_REQUIRED.** All prior major low-level findings are closed, but the
OpenSpec retention rule must permit the executable GC, and anonymous collision
retry must explicitly acquire the replacement ID lock. Correct these narrow
points and request a fresh review; Gate 2 remains prohibited.
