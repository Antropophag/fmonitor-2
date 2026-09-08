# Selection typed result and replay candidate

Date: 2026-09-05. DRAFT technical appendix for
ASSIGNMENT-ORDER-COMPOSITION-SELECT-001. Not Gate1 approval. This resolves the
typed serialization/fingerprint ambiguity for the next coherent specification;
storage/ports and owner REPLACE_PENDING policy must be reconciled before RED.

## Public typed result

Namespace FMonitor2\AssignmentOrderComposition. Status enum backing strings:
selected, replayed, rejected, conflict, failed. Symbolic case names are their
uppercase equivalents. Reason enum backing strings are lowercase equivalents of
the exact v0.3 reason table; no additional undocumented reason is permitted.
Result public methods have these exact types:

```php
public function status(): AssignmentOrderCompositionStatus;
public function reasonCode(): ?AssignmentOrderCompositionReason;
public function retryable(): bool;
public function requestId(): string;
public function caseId(): ?int;
public function assignmentOrderId(): ?int;
public function assignmentOrderVersion(): ?int;
public function selectionRevision(): ?int;
public function compositionIdentity(): ?string;
public function compositionSha256(): ?string;
public function selectionDate(): ?string;
public function selectedAt(): ?string;
public function toArray(): array;
```

`toArray()` is a serialization view of these typed accessors, not the sole
domain interface. Exact key order is the method order above excluding toArray;
enums serialize to their lowercase backing strings. Every key is present,
including nulls. selected/replayed have null reasonCode, retryable=false and all
success fields non-null. rejected/conflict have retryable=false and every field
after requestId null. failed has retryable=true and the same null success fields.
The public result implementation must prevent impossible combinations.

The representation of generated ID exhaustion is intentionally not invented by
this appendix; its exact failure disposition belongs to the allocator contract
and must be added coherently before the final reason enum is approved.

## Canonical intent and independent example

Shape validation runs first and rejects duplicate/nonpositive installer IDs.
Valid installer lists are sorted numerically before identity comparison. The
normalized tuple is exactly these ordered fields:
actorUserId, controlEngineerUserId, expectedSelectionRevision,
installationObjectId, installerTabIds, mode. Mode uses lowercase backing string.
All IDs/revisions are JSON integers, engineer may be null only for the business
CONTROL_ENGINEER_REQUIRED case, installers may be empty only for
INSTALLER_REQUIRED. No generated/case ID, clock, catalog snapshot, permission,
requestId, original/template date or transport data enters the tuple.

Compact UTF-8 JSON uses no escaped slash or Unicode, no pretty printing and no
final LF. Operation fingerprint is lowercase SHA256 over those exact bytes.
The independently fixed example is:

```json
{"actorUserId":18,"controlEngineerUserId":73,"expectedSelectionRevision":0,"installationObjectId":4512,"installerTabIds":[7001],"mode":"new_order"}
```

SHA256:
`62ee3be1c62ff977fc0e508a4743d85b8f5bc83386b3b8b0c26f542d321aff6c`.
Python standard json/hashlib independently calculated this literal; no
production result was used. This fingerprint is distinct from composition hash.

## Lookup/replay and terminal outcomes

Authorization must precede any confidential request lookup on every invocation.
A permitted request lookup returns found/not_found/unavailable. Found includes
both normalized tuple and fingerprint plus its typed terminal result. Match
requires byte-equivalent canonical tuple AND digest; digest alone is not enough.
Different mode, expected revision, object, actor, engineer or installer set for
the same requestId gives conflict/request_id_conflict with no success identity
fields. Tuple match accepted result returns replayed with exact stored success
fields and no new event/selection/request/audit. A stored business rejection or
conflict returns its original status/reason without new terminal facts.

Technical failure never becomes a terminal cached business result. On uncertain
commit, use one fresh independent connection to look up the same request:
matching accepted result→replayed; matching rejected/conflict→stored terminal
result; proven absent→failed/persistence_failure; unavailable→
failed/persistence_outcome_unknown; different tuple→request_id_conflict.
No generated replacement request identity or blind second mutation is allowed.
Caller may retry the same original requestId. A fresh lookup cannot claim absent
from connection/query/decoding failure. Exact deterministic ports and observer
construction remain mandatory in the final combined Gate1 contract.

Safe denial auditing before confidential lookup must not overwrite or retrieve
an accepted request belonging to a now-revoked caller. Its storage uniqueness,
retention and failure handling remain an explicit audit-contract dependency;
this appendix does not silently insert a denied terminal row over an accepted
one. Required acceptance includes authorized success→revoke→same-request denial
with unchanged accepted result and no confidential disclosure→reauthorize→replay.

## Already resolved role identity

No new owner question is needed to identify Head of FKR. Existing
`completion-otiz-owner-decisions-2026-09-02.md` item11 preserves the technical
role code on display rename; approved original contract explicitly identifies
that code as manager. LocalRoleCatalog currently still displays the old generic
name; that does not authorize grants by display name or changing custom roles.
The exact selection authorization adapter and additive grants remain separately
specified technical work. This identity evidence does not itself grant selection
or change the original-command authority model.
