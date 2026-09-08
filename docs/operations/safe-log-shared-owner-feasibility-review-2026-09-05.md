# Safe-log shared opened-file owner — bounded feasibility review

- Review date: `2026-09-06` (`2026-09-05` operational record name retained by task)
- Reviewer: separately tasked independent agent `/root/registry_engine_gate1`
- Repository HEAD observed: `0696d88c2d5e85ee24c450ff7f52e6164f3bdae4`
- Scope: read-only defensive API design assessment; no executable-spec, code or test review verdict
- Feasibility verdict: **FEASIBLE WITH EXPLICIT GATE CONTRACTS**

## Answer

A public shared validated opened-file owner/capability can satisfy
`G5-SAFELOG-2` without weakening the owner-approved requirement and without
using either automatically rejected mechanism. It is materially different from
native metadata interception and from an observer inserted into the
validation/open interval: the proposed production object opens the configured
file normally, obtains actual descriptor metadata through `fstat`, validates
that metadata, and retains the same opaque opened capability used for append.
No interval modification, timing race, permission transition, privilege change,
loader/interposer or substituted syscall result is needed.

This is a feasibility finding, not Gate 1 approval. The current executable spec
still contains the pending observer/`chmod` candidate at lines 986–1045, and the
current production source still has no shared opened-file capability. Those
bytes must not be treated as approved merely because a safer design is feasible.

## Why the design fits the existing requirement

The owner resolution requires a pre-existing canonical non-symlink regular file
owned by the effective user with exact mode `0600`, validation before database
or private-root access, no create/repair, retained append diagnostics, and one
fixed redacted construction failure. The current executable candidate further
states that the descriptor actually retained must have matching device/inode
and independently valid `fstat` type, UID and mode
(`specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md:954–984`).

The capability can preserve that outcome if one production acquisition
operation owns the complete sequence:

1. validate the unchanged configured pathname/canonical/non-following contract;
2. open the exact path for append without creating or truncating it;
3. call real `fstat` on that opened handle;
4. require descriptor identity to match the final pathname observation and
   require regular type, current effective UID and exact `0600` from the
   descriptor metadata;
5. close on every mismatch or construction failure;
6. return an opaque owner only after all checks pass.

Its constructor must be private. It must expose neither the raw stream nor a
method that lets a caller replace/adopt arbitrary metadata or a different
handle. The safe logger must accept only that validated capability (or the
capability itself may implement the existing safe-log observer) and must append
through the retained handle it owns. It must not reopen by pathname. Explicit,
idempotent close plus fallback destruction must retain the existing lifetime and
attempt-always cleanup expectations.

`ProductionAssignmentOrderOriginalFactory::create` must acquire this capability
from the existing `AssignmentOrderOriginalProductionConfig::safeLogFile` before
the existing private-root validation and before construction/use of database
adapters. The config field, trusted source, fixed exception mapping and ordering
therefore remain unchanged. The current factory ordering is visible at
`app/AssignmentOrderOriginal/AssignmentOrderOriginalFileStorage.php:36`; the new
owner would replace its separate canonical-path plus logger-open sequence, not
add another configuration source or production selector.

## Safe verification allocation across the mandatory gates

Gate 2 can demonstrate a missing public capability seam without manipulating a
live validation/open interval. Direct public-owner tests may use:

- one ordinary task-owned file created initially as a regular current-EUID file
  with disallowed mode, requiring fixed rejection, unchanged bytes/metadata and
  no retained owner;
- one separate ordinary task-owned file created initially valid, requiring
  successful opaque ownership, append through the public safe-log behavior,
  exact prior-byte preservation, and deterministic explicit-close behavior;
- literal metadata values passed only to a pure attribute predicate/validator,
  independently covering regular type, effective UID and exact `0600` positive
  and one-axis negative cases.

Existing public factory regressions must additionally prove that a valid
configured file still constructs before database/private-root use, each stable
invalid pathname/file case returns the existing exact redacted production
configuration exception, and the factory/logger no longer has an alternate
path-opening route. No observer, hook, child timing protocol, permission change
or descriptor inventory is necessary for this candidate.

There is one unavoidable evidence boundary: with a stable ordinary file, a
black-box test cannot distinguish actual descriptor `fstat` from a faulty
implementation that repeats pathname `lstat`, because both observations return
the same attributes. The pure validator tests prove the attribute policy, and
the real-file tests prove public acquisition/ownership/close behavior, but they
do not alone prove the metadata source.

This does not make the approach infeasible under the repository process. Gate 5
must carry the structural half of the proof on the exact implementation bytes:

- the private acquisition path calls `fstat` on the newly opened retained
  handle;
- descriptor device/inode/type/UID/mode feed the approved validator;
- no pathname metadata is substituted for those descriptor fields;
- no public raw-handle or arbitrary-adoption escape exists;
- the logger/factory can receive only the validated capability and cannot reopen
  the configured path;
- all failure branches close the opened handle before the fixed redacted error;
- database and private-root construction remain downstream of successful
  capability acquisition.

Gate 3 should reject a test claiming that the stable invalid-mode case alone
proves the use of `fstat`; it proves rejection policy and public ownership only.
Gate 5 should reject implementation approval if the structural proof above is
absent, even when all public tests pass. Together, the behavioral and structural
evidence covers the requirement without recreating the rejected disagreement
fixture.

## Required planning disposition

A future technical amendment would need to replace, rather than retain beside
it, the pending observer declaration and interval-`chmod` fixture in the current
spec. Keeping both would preserve a contradictory mandatory mechanism and would
still encounter the recorded automatic rejection. The new amendment should name
the public owner/capability seam, exact acquisition result/failure, ownership
transfer, append and close behavior, factory integration order, fixed exception
mapping, and the behavioral-versus-structural proof split above.

That amendment requires fresh independent Gate 1 review, demonstrated RED at the
new public seam, independent Gate 3, minimal implementation, and independent
Gate 5. Existing production factory regressions remain required. No existing
safe-log Gate verdict approves this replacement automatically, and no owner
reconfirmation is needed unless the amendment changes the already approved
file policy or visible failure outcome.

## Source findings

- Current `AssignmentOrderOriginalFileSafeLog` performs pathname validation,
  captures a final `lstat`, opens with `ab`, and checks only device/inode from
  `fstat`; it does not validate opened-descriptor type, UID or mode
  (`app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php:124`).
- Current production factory first canonicalizes the configured file, then asks
  the logger to validate/open it, and only afterward validates the private root
  (`app/AssignmentOrderOriginal/AssignmentOrderOriginalFileStorage.php:36`).
- The owner-approved contract and pending descriptor clarification already
  require all target attributes on the retained descriptor
  (`specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md:954–984`).
- The remaining spec text mandates the rejected observer/interval permission
  transition (`specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md:986–1045`); it is
  historical current candidate text, not authority to retry it.
- Both automatic-rejection records explicitly leave `G5-SAFELOG-2` open and
  prohibit retry/evasion. This assessment invokes neither rejected mechanism.

## Exact reviewed hashes

```text
f879c3a7e9ddb199dc62234d3d35fa2f8470f360b54825dab5f0d33113076062  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
4c893c34377546ded04fc094bf5cbfd8dd5647655416ec25a8e6e28c65ef114d  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
7f90d23d59ba193ed3fa5f917625e0da0d58176e2ae76c3e6edca73b4efc926f  app/AssignmentOrderOriginal/AssignmentOrderOriginalFileStorage.php
a41098f4ebd041d33d528f708369f1f416c6889b25e5bff342ead52dcb8fc64f  docs/operations/safe-log-native-verification-automatic-rejection-2026-09-05.md
7b25691025431a048faa49f6175b8c81c296d360aa72d8af9be0135b32dc0289  docs/operations/safe-log-observer-planning-automatic-rejection-2026-09-05.md
55981517f0350cbd7be254a32dc171784a820f02d0cefb7b77b3def9503ee27c  docs/operations/assignment-order-original-safe-log-safe-alternative-gate1-review-2026-09-05.md
3da95e342c0f49d96cfd2d91aca12ba6eecdba3fb75777333fab04e0b1ae5ec6  docs/operations/assignment-order-original-production-safe-log-owner-resolution-2026-09-05.md
a5a4393c39740310dfe99850503b67e78a09753dbd626170866fd2f631e33478  docs/operations/autonomous-restart-handoff-2026-09-05-1842Z.md
```

No OS/file fixture, database, test, external tool, native interception,
observer, permission transition, privilege action or syscall metadata
substitution was executed. This record does not authorize implementation or
RED and does not close `G5-SAFELOG-2`.
