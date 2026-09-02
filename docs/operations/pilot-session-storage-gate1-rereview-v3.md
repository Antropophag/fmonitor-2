# PILOT-SESSION-STORAGE-001 — independent Gate 1 rereview v3

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **READY_FOR_OWNER_APPROVAL**

The reviewer did not author or edit the executable specification, OpenSpec
artifacts, production or tests. This verdict supersedes the prior
`CHANGES_REQUIRED` reviews for the exact coherent package below. It permits an
explicit owner decision; Gate 2 remains prohibited until that decision is
recorded.

## Reviewed hashes

```text
2afa029374583b18ed06d6eb37f8c9e3857b3366ac5e516f1eb3b07de8ba8ad0  specs/PILOT-SESSION-STORAGE-001.md
4d926e4cdf39675bb1ae404142b1c1b5db5af8e8f35e6364b6e9ae671b432a04  openspec proposal.md
0424de9777518653303a6ca2af96a833ae5bc58c9a9b0552772233c14d8887ef  openspec design.md
d79ae0f4b77121d81fac7246bc1c71bed5e3242930da09e15cb5428217b37c15  openspec delta spec.md
13071d8611f1fee0c32410d7573891628a8ada3286ea731b6cab24893318bb8e  openspec tasks.md
aeada56c9582db6fdcce3fa7a377f2067a366191190cfc166ed03afd77a62484  pilot-session-storage-gate1-review.md
42395104e440b6f0a3fe22bffd38654c0b75691419cf2a8e76458f74d2cab91f  pilot-session-storage-gate1-rereview.md
d8c1bdb48e36ad3f0bdb1a8be76e24f2a0395ce19a48beddb57a85a0f829276e  pilot-session-storage-gate1-rereview-v2.md
d5c776a36a27377972c7b5f897ebcd95bead8154612cfdac8b1fbb98a869f406  rapid-pilot/LocalAuth.php
33b298c2f28a7c9ee493270ded4b7d54c9192cd0a1d529ea4a7daeb5a7697f1a  rapid-pilot/UserAccessView.php
b075db40047c604e5f71f992379e2caeafcf7f945acb80062d9b62b645008727  compose.yaml
```

## Final finding closure

### Anonymous collision retry is lock-correct

On anonymous publication EEXIST, the adapter removes/revalidates only its own
stage, releases/closes the obsolete candidate lock, generates a wholly new ID,
acquires and revalidates that ID's hash lock, proves the new committed target
absent, and only then creates a new stage. The branch has at most eight complete
candidate attempts and never operates on a replacement ID before its lock or
overwrites another session.

Regeneration remains stronger: `regenerate(old,data)` generates internally,
acquires old+candidate locks in binary order, resolves all normal candidate
collisions before mutation, and treats unexpected post-old-unlink EEXIST as
`REGENERATE_FAILED` without retrying an unlocked third ID. Both anonymous and
regeneration candidate ownership are now consistent with the global “every
write/regenerate locks its ID” rule.

### Production cleanup and GC are coherent

The OpenSpec delta now matches executable §§5/8/9:

- production never removes managed root, sessions or instance directories;
- explicit normal destroy may remove the owned session through its tombstone
  protocol;
- exact age-bounded, metadata-revalidated, corresponding-lock-held GC may remove
  expired committed/stage/revoked/lock files;
- no broad cleanup, chmod/chown repair or foreign deletion is permitted;
- test attempt-all task-root cleanup and Compose operator reset/retention remain
  separate seams.

There is no remaining package contradiction over file versus directory
retention.

## Complete feasibility audit

- Exact config keys/default/empty handling, instance grammar and path derivation
  are deterministic; request/Host/user/cookie values cannot select storage path.
- Root/descendant owner/type/mode and EEXIST revalidation are implementable for
  current Compose and task-owned host roots. Same-uid/openat limits are honestly
  outside the guarantee.
- File and ID grammars are exact. Adapter-owned IDs use 32 random bytes/64 hex;
  stage/tombstone tokens use independent 16 bytes. Eight-attempt entropy/
  collision outcomes are closed and no caller supplies a new ID.
- Start null/valid-missing/invalid, regeneration stale old and idempotent absent
  destroy have exact typed semantics compatible with strict-mode login/logout.
- Hash lock files, 2-second monotonic nonblocking acquisition, binary multi-ID
  order and the single `LOCK_TIMEOUT` literal are coherent.
- The safe unavailable enum is closed. Each failure has an internal-only fresh
  12-hex correlation and exact once redacted log; public response intentionally
  has no correlation header.
- Existing-ID atomic rename and new-ID hard-link no-clobber publication use
  same-directory mode0600 stages with complete write/fflush/fsync/identity and
  directory-fsync checks. No valid target is overwritten.
- Regeneration old→hash-associated tombstone/unlink→prevalidated new hard-link
  has exact old-valid/neither-valid/new-only crash regions and cannot produce
  dual-valid IDs. Tombstone cleanup never resurrects old. Destroy uses the same
  no-clobber invalidation principle.
- LocalAuth/UserAccessView buffer response through explicit commit/close. Every
  typed failure maps to the literal GET/POST/HEAD 503, headers/body/no-cookie/
  no-redirect contract without partial output or secret diagnostics.
- Cookie name/port/lifetime/Path/HttpOnly/SameSite and strict ID/CSRF/return-to
  semantics remain. Secure derives only from exact outer-trusted
  `FMONITOR_TRUSTED_REQUEST_SCHEME`; raw forwarded/client headers are ignored.
- Host/URI and known/unknown static asset priority occur before storage. Both
  consumers share one owner; native session primitives/hardcoded alternate roots
  are architecture violations.
- Stage/revoked names embed session hash, enabling corresponding lock acquisition.
  GC has exact 10,000 overflow, oldest/binary 100-candidate selection, 604800
  age, metadata/lock revalidation and safe lock-file retirement. Unknown/newer/
  locked/wrong files remain unchanged and binary-prefix starvation is avoided.
- Distinct instances are disjoint; Compose stop/start with same volume/root/
  instance preserves valid committed bytes within lifetime. Production/test/
  operator cleanup ownership is separate.
- Gate 2 fault inventory covers all primitives/crash regions, both consumers,
  exact HTTP/cookie/routes, unprivileged host/image and real Compose restart.
  Gates 1–5 remain ordered and no task is prematurely complete.

## Verification

```text
openspec validate define-pilot-session-storage-contract --strict
Change 'define-pilot-session-storage-contract' is valid

git diff --check -- <reviewed executable spec and OpenSpec package>
exit 0, empty output
```

## Owner approval boundary

The owner may approve exact executable-spec SHA-256:

```text
2afa029374583b18ed06d6eb37f8c9e3857b3366ac5e516f1eb3b07de8ba8ad0  specs/PILOT-SESSION-STORAGE-001.md
```

Any normative change requires a new hash and fresh independent Gate 1 review.
Verdict: **READY_FOR_OWNER_APPROVAL**.
