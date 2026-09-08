# Assignment-order composition selection v0.3 — P0/P1 resolution audit

Date: 2026-09-05. Auditor: `/root/selection_contract_reconciliation`.
Scope: bounded read-only planning reconciliation of the current selection draft,
its latest readiness review, current schema/public APIs, projection readers and
OpenSpec package. No executable-spec, test or production artifact was edited.
This record is technical advice, not Gate 1 approval, product approval, RED,
implementation permission or a review verdict.

Inspected repository HEAD: `4db6c004d444b129c56b33879e7f9cc896dc3762`.
Unrelated untracked records already existed and were not read or changed.

## Conclusion

All P0/P1 findings can be closed without asking the owner to repeat the already
approved workflow: select at least one installer and exactly one control
engineer before upload; template generation is optional; upload confirms the
selected composition; upload does not apply composition or open work; history
is append-only; sequential orders act only forwards.

The minimal coherent resolution is an additive **selection ledger**, separate
from `fm2_assignment_orders` / `fm2_order_installers`. A pending selection gets
the stable numeric `assignmentOrderId` and immutable composition identity
required by original upload, but it does not get a fabricated `order_date`,
`valid_from` or `valid_to`. The approved original composition reader receives
an additive branch for this ledger. Effective directory/inspection readers
continue to read only the existing effective-order source until the separate
`apply-assignment-order-original-to-composition` slice changes applicability.

Using `selectionDate` as `fm2_assignment_orders.order_date` is not a valid
candidate: product truth says the order date is the date printed in the
original, while selection happens before that fact is supplied. Making the
existing temporal fields nullable is larger and leaves every current order
consumer responsible for distinguishing pending snapshots. Writing a pending
row into the current order tables also reproduces the proven `MAX(version)`
projection failure. A separate ledger resolves both P0s with the fewest changed
owners and no semantic placeholder.

## Exact minimal P0 disposition: date, identity and original handoff

Add a migration-owned selection family (literal migration number must be chosen
at the then-current frontier):

- `fm2_assignment_order_selections`: `assignment_order_id BIGINT UNSIGNED`
  primary key (generated from one database sequence/allocator shared by the
  selection repository), `installation_case_id`, `order_version`,
  `selection_revision`, `mode`, `previous_selection_order_id`,
  `replaces_selection_order_id`, `composition_identity`, `composition_sha256`,
  `control_engineer_user_id` and its immutable FIO/position snapshot,
  `selection_date`, `selected_at_utc`, `selected_by_user_id`; unique
  `(installation_case_id,order_version)`, unique
  `(installation_case_id,selection_revision)`, unique composition identity.
- `fm2_assignment_order_selection_installers`: selection order ID + installer
  tab ID primary key and the existing immutable workforce snapshot columns. It
  has no assignment `valid_from`, `valid_to` or `change_action`, because the
  selection is not an assignment interval.
- `fm2_assignment_order_selection_requests`: canonical request ID primary key,
  operation fingerprint, normalized command fields, terminal status/reason and
  every successful result field needed for byte-stable replay.
- `fm2_assignment_order_selection_events`: one append-only success event per
  accepted request. `fm2_assignment_order_selection_audits`: one terminal
  accepted/rejected/conflict attempt record per request according to the policy
  below. These names may be consolidated only if the executable spec preserves
  the same independently observable facts and constraints.

`selectionDate` remains the Moscow date of `selectedAt`; it is eligibility and
audit context only. It is never copied into `fm2_assignment_orders.order_date`
and never represents original `documentDate` or optional-template date.

Extend `AssignmentOrderCompositionReader::find(caseId, assignmentOrderId)` with
one deterministic ownership rule: resolve exactly one source by ID. A selected
ID reads the selection header and all selection members without temporal
assignment predicates. An existing order ID retains the current order reader
behavior. Both sources produce the already approved compact composition JSON
and hash. Duplicate ownership of one numeric ID, malformed snapshots or a
source error yields `UNAVAILABLE` (malformed found data may remain
`FOUND` + invalid snapshot only if the unchanged original command continues to
map it deterministically to `INVALID_COMPOSITION`). Absence from both yields
`NOT_FOUND`. The migration/repository must prevent ID collision rather than
using lookup precedence as conflict resolution.

The original roots/requests currently have no FK to `fm2_assignment_orders`, so
this handoff needs no invented order row. Optional rendering must later read the
same selection snapshot and persist only artifact/template facts; it must not
materialize effective intervals. The downstream apply slice consumes an
accepted original plus its selection identity and creates the forward-only
effective order/interval facts. That slice, not selection, owns document-date
applicability and interval boundaries.

## Exact minimal P0 disposition: latest versus effective sources

Define one typed `SelectionStateReader::findForUpdate(caseId)` repository port
whose atomic/CAS implementation returns:

- latest selection: greatest `selection_revision`, with order ID, order version,
  composition identity/hash and whether an original root exists for that exact
  assignment order ID;
- latest pending selection: the latest selection only when no original root
  exists for that exact ID;
- latest accepted selection: greatest selection revision having an original
  root, for command conflict decisions only;
- effective order: an optional opaque identity read from the current effective
  projection owner, never inferred as `MAX(selection_revision)`.

Unique case/revision constraints plus a case-scoped row lock or equivalent CAS
make ties impossible. `expectedSelectionRevision` compares only with the latest
selection revision (zero for no selection). Exact mode rules remain those in
v0.3: NEW_ORDER conflicts with `PENDING_SELECTION_EXISTS` when latest is
pending, and may create a new prospective selection when latest is accepted;
REPLACE_PENDING requires and replaces latest pending, and conflicts when that
identity is accepted. Replacement adds a new selection/order identity and
revision and retains a link to the replaced identity.

Public effective projections must not query the selection ledger. For the RED
matrix, the public seam consists of the existing installer-directory result
(assignment list plus assigned/free summary) and inspection case/installer
result before and after accepted A + pending B. Those results must be identical.
This is an interim preservation contract, not approval of current
`status='registered'` as the future applicability rule. The later apply slice
must introduce one explicit effective-order projection/read model and move both
readers to it together. An isolated `MAX(registered)` repair is insufficient.

## Exact minimal P1 disposition: typed result and deterministic ports

Mirror the existing original API style. Declare backed lowercase enums:

- status: `selected`, `replayed`, `rejected`, `conflict`, `failed`;
- reasons: the v0.3 symbolic list with lowercase backing strings;
- common dependency lookup: `found`, `not_found`, `unavailable`;
- authorization: `allowed`, `denied`, `unavailable`;
- commit: `committed`, `conflict`, `rolled_back`, `outcome_unknown`.

`AssignmentOrderCompositionResult` must expose typed accessors for status,
reason, retryable, request ID, case ID, assignment-order ID/version,
selection revision, composition identity/hash, selection date and selected-at.
Successful values are non-null; all success identity values survive replay;
non-success domain fields are null. If HTTP needs arrays, define a separate
serializer with exact lower-case values and the exact key order already listed
in v0.3. Do not make an untyped `toArray()` the domain contract.

Minimum constructible ports are: authorizer; terminal-request lookup; object/
case reader; selection-state reader; workforce batch reader preserving sorted
missing/employment outcomes; engineer reader; clock returning one UTC instant;
ID allocator; atomic repository commit; post-unknown terminal lookup; audit
observation reader used by acceptance tests. Each lookup exposes typed
found/not-found/unavailable outcomes. Renderer and template storage are absent.

The result/status table in v0.3 otherwise remains usable. `NO_CHANGES` is a
terminal rejected attempt: store it in request/audit with no selection event or
domain mutation. Retryable `FAILED` results are not terminal-cached. After an
unknown commit, same-request lookup has exactly three routes: matching terminal
success -> `replayed`; proven absent -> `failed/persistence_failure`; unavailable
-> `failed/persistence_outcome_unknown`. A found nonmatching fingerprint is a
request conflict and must disclose no stored selection fields.

## Exact minimal P1 disposition: replay fingerprint

Normalize installers as unique ascending integers before fingerprinting. The
accepted intent JSON has exact UTF-8 compact encoding and key order:

`actorUserId,controlEngineerUserId,expectedSelectionRevision,installationObjectId,installerTabIds,mode`.

Values are the positive/nonnegative canonical command values and lowercase
mode. `requestId`, resolved case ID, clock values, generated IDs, catalog
snapshots and authority facts are excluded. Fingerprint is SHA-256 of those
exact JSON bytes. The request row stores request ID, fingerprint and the six
normalized fields, so collision handling does not depend only on a digest.
Authorization remains before lookup. A found row replays only when all stored
normalized fields and fingerprint match; otherwise `REQUEST_ID_CONFLICT`.

## Exact minimal P1 disposition: authority and grants

The actors are inherited owner-approved behavior: employee FKR and Head of FKR.
The exact permission string and fail-closed adapter are engineering mappings,
not a new product choice. Use `assignment_order.composition.select` and do not
derive it from prepare, upload, correction, read or administrator permissions.

The current runtime has two alternative identity modes, not one combined grant
chain. In autonomous local-role mode require active pilot user, active assigned
builtin role and exact local permission. Add the permission only to builtin
`fkr_operator` and the existing stable technical code that represents Head of
FKR (currently `manager`; its displayed name/description must be reconciled to
the canonical “Руководитель ФКР” wording). Seed by stable role code; do not
match display names and do not grant custom roles. In legacy identity mode
require active legacy user/role plus exact row in
`fm2_process_user_capabilities`; extend its CHECK catalogue additively. The
authorizer presents one typed allowed/denied/unavailable outcome regardless of
mode. Missing user/role/grant/capability is denied; query/schema failure is
unavailable and maps to retryable dependency failure. Replay is reauthorized.

Requiring both local permission and process-capability rows simultaneously in
local-role mode would be a new hybrid authority architecture absent from the
current public adapters. It is unnecessary for the approved actor outcome. If
the team instead wants that hybrid as a general policy, it is an architecture
choice needing its own coherent migration/administration contract, not an
implicit clause in this slice.

## Exact audit candidate

On accepted commit, atomically persist selection/header/members, terminal
request result, one event `assignment_order_composition_selected`, and one
accepted audit. Event fields: event ID, request ID, case ID, assignment-order
ID/version, selection revision, previous selection order ID, replaced selection
order ID, composition SHA-256, occurred-at UTC and actor user ID. No filenames,
bytes, paths or original/template dates appear.

Rejected/conflict terminal requests persist the normalized request record and
one safe audit with request ID, actor ID, mode, object ID, status, reason and
attempted-at UTC; they persist no member identities/snapshots or success event.
Authorization denial may persist only the same safe fields after an audit clock
is available and must never disclose or retrieve a prior confidential result.
Dependency/technical failures go to the approved safe diagnostic observer and
are not terminal-cached. Audit persistence failure cannot be reported as a
business rejection or successful selection.

## Inherited behavior versus genuinely new choices

Inherited and therefore not appropriate to re-ask: the two FKR actors; selection
before upload; minimum one installer and exactly one engineer; optional
template; composition confirmation on upload; distinct selection/template/
original/upload times; no application/opening/checklist change; append-only
history; no correction of accepted composition through selection; forward-only
sequential orders; authorization before confidential replay.

Engineering decisions already constrained enough to specify: separate pending
ledger; exact typed APIs/ports; canonical fingerprint; CAS and uniqueness;
lowercase enum encoding; stable role-code seed; no display-name/custom/admin
grant; safe audit payload; effective projections ignoring pending selections.

One genuinely new product choice remains only if v0.3 keeps REPLACE_PENDING as
a user-visible action: whether an FKR user may revise an unsigned selection and
whether that revision is visible as user-facing history. Optional template and
append-only correction principles support the proposed behavior, but the owner
evidence explicitly defines correction for uploaded originals, not for a saved
unsigned selection. The minimal route avoiding a new owner decision is to omit
REPLACE_PENDING from the first executable slice: allow initial NEW_ORDER, replay
and stale conflict; require the user to complete/cancel through a separately
approved lifecycle before choosing another composition. If replacement is
required now, request one narrow owner decision approving append-only
pre-original replacement and its visible history; do not re-ask any other item.

Likewise, mapping stable technical role code `manager` to “Руководитель ФКР” is
an engineering reconciliation only if repository evidence confirms it is the
preserved Head-of-FKR code. If that identity cannot be proved, ask which stable
role ID/code represents the already approved actor; do not ask whether the actor
may select.

## Required next Gate 1 amendment set

Amend the executable spec and all four OpenSpec artifacts coherently with: the
selection-ledger manifest; alternate original-reader source contract; exact
latest/pending/accepted/effective definitions; typed enums/results/ports;
fingerprint bytes; authority modes and migration; accepted/safe audit facts;
unknown-outcome routes; and the chosen disposition of pre-original replacement.
Then obtain a fresh independent Gate 1 review at exact hashes before RED. This
audit itself does not advance a gate.

## Exact evidence hashes

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
375f7b21d0a8035bb6e5d2284b914386d5352dc4d674f0e56eb6ba0bae8b4f99  docs/operations/autonomous-restart-handoff-2026-09-05-1309Z.md
2b30da75a41dc894d43d85a53e8f06979775787f447eaf1b3f82b9675fbe84b9  specs/ASSIGNMENT-ORDER-COMPOSITION-SELECT-001.md
ff83bdd3519de5cd6e40b289378fefc3c3f436aa6052c440f42881cc3226bdba  docs/operations/selection-command-contract-v03-readiness-review-2026-09-05.md
39a0d3454c63f64a54a8bbe8a8f8abd172f2f7576a236319894ab25cf81f4a4d  docs/operations/pilot-assignment-order-original-owner-decision-2026-09-02.md
495b951825e01fba1334e673de224059e9d6b8a73b9689db037a7cb51f26cd16  openspec/changes/select-assignment-order-composition-without-template/proposal.md
40fe549e883c68589a6abf64e19e9f7f741f30404e45e9d972cb5178fe9313b1  openspec/changes/select-assignment-order-composition-without-template/design.md
261b7d8f9e6ee501cd5d9efb48126ac3446a5d8e79fc35d07bae8d12694fac1a  openspec/changes/select-assignment-order-composition-without-template/tasks.md
2670122db8a36f35d4ef4932c3498f5c7e9b310f6a103e4ddbb11534f59d97d7  openspec/changes/select-assignment-order-composition-without-template/specs/pilot/assignment-order-composition-selection/spec.md
5a91a1ce9f4facac9732dd085e8238f419d019056c5ac1dd170c06f36d031660  app/InstallationProcess/ProductionProcessSchemaMigration.php
4c893c34377546ded04fc094bf5cbfd8dd5647655416ec25a8e6e28c65ef114d  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
232c56f7009ee4416a35d3942dc2ee46053dc383fb0c11e769f418337f9a158d  app/AssignmentOrderOriginal/MariaDbAssignmentOrderOriginalEvidence.php
34b4a20be0005e4236ee3cdb04ca6fd41537ca3608ecef41ac5e9d91f151ddf5  app/InstallationProcess/AssignmentOrderOriginalDefinitionSchemaMigration.php
d1dd2e1a4041ec6380beaba4e2b017646ee404832a76252565751439de56ce69  app/PilotHttp/MariaDbInstallerDirectoryReader.php
6d096c0737ad9197375f92019a3d1103e0941c219648b742f3fe385a5045096c  app/InspectionEvidence/MariaDbInspectionCaseDirectory.php
be0e8dede13a68086bbcbc42bf8944c8b5cca721b40039641c3b16572d774768  app/InstallationProcess/MariaDbProcessUserDirectory.php
00abc0aa5ca3efb1be142318cb4bfa6147df07250e603c172f7dec0ae43daa36  app/PilotHttp/MariaDbIdentityBootstrapApplication.php
16e1ac3b7314a773a87aab2515ac0dd8f69db66119558f759af4cdd50870ea6f  app/RapidPilot/LocalRoleCatalog.php
6d71394ac29e2872f9853425cb09b11125dad67ec4ec434214acf7bdbf64ed2a  app/PilotHttp/MariaDbProcessCapabilityReader.php
```
