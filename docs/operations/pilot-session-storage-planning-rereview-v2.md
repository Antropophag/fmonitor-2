# Independent planning rereview v2 — PILOT-SESSION-STORAGE-001

Date: 2026-09-02  
Prior rereview: `pilot-session-storage-planning-rereview.md`, SHA-256
`9c5b15bd8f6a9f2168ab10b2c11918b27dcea9991bbb5329b8e435ef4ca53888`  
Verdict: **APPROVED_FOR_GATE1_DRAFT**

## Verdict basis

The final correction closes both findings from the prior rereview and remains
coherent with the six findings already closed there. This verdict approves the
planning package to produce the executable Gate 1 specification; it is not
owner approval, test approval or implementation approval.

Strict validation passes:

```text
Change 'define-pilot-session-storage-contract' is valid
```

## Eight-finding closure audit

1. **Configuration/default/root/instance — closed.** Exact keys, absent versus
   present-empty behavior, compatibility root, instance grammar/default and
   final directory derivation are deterministic.
2. **Trusted root/modes — closed.** Trust starts at the managed root rather than
   impossible ownership of `/` ancestors. Existing root modes and exact 0700
   descendant modes are enumerated; existing metadata is never repaired.
3. **TOCTOU feasibility — closed.** The contract relies on current-uid 0700,
   operation-adjacent identity checks and explicitly excludes same-uid malicious
   swaps/openat strength. It no longer promises descriptor authority that native
   PHP path reopening cannot provide.
4. **Primitive/commit/failure mapping — closed for Gate 1 drafting.** One custom
   handler owns typed operations; response headers/body remain buffered through
   explicit commit; implicit shutdown write is forbidden. Regeneration now has
   an implementable same-directory state machine: staged data is fsynced, old
   addressability is atomically removed by rename to a non-session tombstone,
   then staged data is atomically renamed to the new ID. The three crash regions
   have exact observable outcomes. A failed tombstone unlink cannot restore the
   old ID and is correctly separated from authentication success.
5. **Both owners — closed.** LocalAuth and UserAccessView must share the adapter,
   and the architecture ratchet forbids alternate native primitives/paths.
6. **Routes and exact failure response — closed.** Known and unknown asset
   priority, outer Host/URI priority and predecessor unknown non-asset behavior
   are distinguished. GET/POST share a literal status/body/header contract;
   HEAD parity is exact; forbidden and unspecified application headers plus the
   narrow local-SAPI Host exception are explicit.
7. **Cookie/Host/GC/restart — closed.** Name/port grammar, trusted HTTPS Secure
   policy, lifetime, path, HttpOnly, SameSite, strict ID grammar, regeneration,
   CSRF, return-to, GC lifetime and persistent-volume restart observation are
   fixed without redefining authentication semantics.
8. **Cleanup/concurrency — closed.** Production destroy/GC ownership is separate
   from task-owned attempt-all cleanup; concurrent first creation accepts EEXIST
   only after full revalidation.

## Feasibility and Gate 1 requirements

- Current Compose compatibility is implementable with the mounted
  `/home/fmonitor/.local/state/fmonitor2` root: Gate 2 must observe the actual
  current-uid ownership and accepted root mode, then prove exact 0700 descendants.
- The executable Gate 1 draft must now pin the remaining low-level literals
  promised by tasks: stage/tombstone/lock filename grammars, file and lock modes,
  lock scope/order, fsync/rename error mapping, GC age and bounded batch policy,
  and every primitive/crash expected result. These are elaboration of the
  approved state machine, not unresolved product semantics.
- Gate 2 must make each crash boundary red-capable without using production
  special cases, and must prove that tombstones/staged files are non-addressable
  through the incoming session-ID grammar.
- Owner approval of the exact independently reviewed Gate 1 hash is still
  required before RED. Independent test review remains before production and
  independent code review remains after full verification.

## Reviewed hashes

```text
4d926e4cdf39675bb1ae404142b1c1b5db5af8e8f35e6364b6e9ae671b432a04  openspec/changes/define-pilot-session-storage-contract/proposal.md
a3b7abf872ac5d2f8e78956629e3feee46176b7c247b44056d34110aeeba6356  openspec/changes/define-pilot-session-storage-contract/design.md
13071d8611f1fee0c32410d7573891628a8ada3286ea731b6cab24893318bb8e  openspec/changes/define-pilot-session-storage-contract/tasks.md
c0de7be4c07b49a0291a3dee05e55b288c98858ec640cfcb1edcff6ef8dfb8f7  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
```

No artifact, code, test or prior review was edited during this rereview.
