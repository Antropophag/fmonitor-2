# Independent planning rereview — PILOT-SESSION-STORAGE-001

Date: 2026-09-02  
Prior review: `pilot-session-storage-planning-review.md`, SHA-256
`1127914219cd37856d2f9e3dcdc395e7ab67d6b55129d5b088eaa709d3189ac1`  
Verdict: **CHANGES_REQUESTED**

## Result

The correction is substantial and closes six of the eight prior findings:

- exact root/instance keys, compatibility default, empty behavior and final
  directory are now fixed;
- the managed-root trust boundary and accepted root/descendant modes are
  implementable with the current Compose volume;
- the same-uid/openat residual boundary is explicit rather than presented as a
  guarantee PHP cannot provide;
- both `RapidPilotLocalAuth` and `RapidPilotUserAccessView` are in scope;
- asset versus unknown non-asset priority, Host/URI precedence, cookie grammar,
  GC and restart observations are now bounded;
- production/test cleanup and concurrent `mkdir` ownership are separated.

Strict validation passes:

```text
Change 'define-pilot-session-storage-contract' is valid
```

Two remaining contradictions still prevent a unique executable Gate 1 draft.

## Remaining gate blockers

### 1. Regeneration promises an impossible multi-file atomic outcome

The design calls regeneration an adapter transaction: write the new ID, commit,
then remove the old ID. The delta simultaneously requires old-ID invalidation
to be atomic with the successful new write and says that on failure the old
session remains valid “or both changes fail atomically”. Plain filesystem
create/rename plus unlink cannot guarantee that outcome across a failure of the
old-file unlink or rollback deletion of the already-renamed new file. A lock
serializes cooperating handlers but does not make two directory entries one
atomic transaction.

Gate 1 must choose an implementable observable policy, for example: commit the
new file atomically, attempt old invalidation under the same lock, and on
invalidation failure publish neither cookie nor redirect, preserve the old
committed session, quarantine the unreferenced new ID for bounded GC, and return
503. Alternatively specify a single authoritative indirection/journal whose
one atomic rename changes the active ID. The spec must state exact crash points,
rollback/orphan handling, lock identity/mode and what `close` reports. Tests
cannot demonstrate the current “both fail atomically” branch without inventing
a storage protocol.

### 2. The “exact” 503 response is still not exact

The contract lists most headers but says only “base CSP without `script-src`”.
That is not a byte-exact expected value. Pin the literal CSP, including directive
order and spacing. Also state whether `Server`, `X-Powered-By`, CORS,
`WWW-Authenticate` and all unspecified application headers are forbidden, and
whether the local PHP-SAPI Host exception from PILOT-HTTP-AUTH applies. Define
the exact response for every method that can consume a session, not only GET
and HEAD: login and logout failures commonly occur on POST, and a POST write/
regeneration/destroy failure currently has no explicitly stated HEAD/body/header
mapping. Task 2.1 cannot produce non-self-authored exact assertions until this
table is complete.

## Feasibility and gate-order notes

- `/home/fmonitor/.local/state/fmonitor2` is the current persistent Compose
  volume target and its compatibility use is feasible: the root may be 0755,
  while `sessions/<instance>` must be current-uid 0700. Gate 2 must inspect the
  actual built-image/volume owner and modes rather than assuming Docker creates
  them correctly.
- Moving unknown `/pilot/assets/...` rejection ahead of LocalAuth is a bounded
  router change and is correctly included in the planned RED; it is not current
  behavior for every unknown asset.
- The proposed custom handler and response buffer can map start/read/write/
  destroy failures before response commit once the regeneration policy above is
  made implementable.
- Gate order remains correct: owner approval follows the corrected executable
  Gate 1 hash; RED and independent test review precede production; independent
  code review follows full verification.

## Reviewed hashes

```text
de0a50bc747bf676d629bb6d2f0cfa526919e09b2382d688d675945535ffea50  openspec/changes/define-pilot-session-storage-contract/proposal.md
4bc6e8d3654d3281140c03308ee30a71b98aafa2600d3b190a7c3becd8900179  openspec/changes/define-pilot-session-storage-contract/design.md
61f815b188c07ee9614f906452b1458d7da6b257ecc368f9a8eefc9442878d0c  openspec/changes/define-pilot-session-storage-contract/tasks.md
5212293c36768f3d33dd4e51f3e10678fe20451119a1fc8b2d860dfd00bb7034  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
d5c776a36a27377972c7b5f897ebcd95bead8154612cfdac8b1fbb98a869f406  rapid-pilot/LocalAuth.php
33b298c2f28a7c9ee493270ded4b7d54c9192cd0a1d529ea4a7daeb5a7697f1a  rapid-pilot/UserAccessView.php
b075db40047c604e5f71f992379e2caeafcf7f945acb80062d9b62b645008727  compose.yaml
```

After the regeneration/crash policy and literal all-method HTTP table are
corrected coherently across design, delta spec and tasks, a narrow fresh
rereview can decide `APPROVED_FOR_GATE1_DRAFT`.
