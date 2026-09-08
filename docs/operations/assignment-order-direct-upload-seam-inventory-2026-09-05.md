# Assignment-order direct-upload seam inventory — 2026-09-05

## Scope and disposition

Independent read-only inventory of production assignment-order creation seams at
commit `b8f892e4888890ed795dde52b935f2d92ff683be` (`spec: draft original
upload and read HTTP contract`). This record is operational evidence only. It
does not approve or advance any development-process gate.

Question: can a public production command persist the user-selected installer
identities and control-engineer identity for a new assignment order without
generating a PDF template?

Answer: **no**.

## Production path found

The sole public production domain command that creates an assignment order is
`InstallationProcess::prepareAssignmentOrder(...)` in
`app/InstallationProcess/InstallationProcess.php:17`. Its public input contains
the selected installer IDs and control-engineer user ID. After validating those
identities, the same invocation unconditionally calls
`renderAssignmentOrder(...)` at line 266. Only after successful rendering does
it construct the order, assignments, artifacts and event and call
`replaceInstallationObjectProcessAtRevision(...)` at line 361. A renderer
failure returns `ASSIGNMENT_ORDER_RENDER_FAILED` and never reaches persistence.

`MariaDbInstallationProcessEnvironment` is the production persistence adapter.
`replaceInstallationObjectProcessAtRevision(...)` dispatches a creation to the
private `persistPreparation(...)` method at lines 61–62. That method inserts the
order at line 86, installer identities at line 89, and every already-rendered
artifact at line 90. It is not a separately callable public application command
and receives the combined prepared projection, including artifacts.

The public portal coordinator calls that coupled command in exactly two creation
flows:

- `app/PilotHttp/PilotE2ECoordinator.php:139`: ordinary template preparation,
  followed by redirect to the generated order PDF;
- `app/PilotHttp/PilotE2ECoordinator.php:164`: multipart `intent=template` or
  `intent=upload`; both intents first invoke the same renderer-coupled prepare
  command. `intent=upload` then calls the private legacy
  `registerPreparedOriginal(...)` at line 165.

The coordinator's direct-upload recovery branches at lines 159 and 170 can
upload against an **already prepared** order and verify that submitted installer
IDs equal its stored composition. They do not create or persist a newly chosen
composition. The private registration methods at lines 173 and 185 write a
signed-original artifact/status/event by SQL; they do not create assignment
orders or installer/control-engineer identity.

The approved original-upload application command
`submitAssignmentOrderOriginal(...)` likewise cannot fill the gap. Its contract
requires an existing positive `assignmentOrderId`, reads composition from the
exact existing order, and explicitly does not apply a new composition. The
production runtime exposes that command in
`app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php:26,61`; its
composition reader only selects the pre-existing order and installers.

## Other assignment-order creation occurrences and exclusions

- `app/demo/index.php:26` directly inserts a separate demo-schema
  `fm2_assignment_orders` row and `fm2_assignments`. It is a demo/private SQL
  surface, not the production application seam or pilot production schema.
- `app/InstallationProcess/MariaDbAssignmentOrderOriginalVerificationFixture.php`
  seeds an order and composition with literal SQL. Its class name and contract
  identify it as verification fixture composition, not a public product seam.
- `tests/InstallationProcess/**` occurrences are executable tests or test setup.
- `rapid-pilot/verify-*.php` occurrences are verifier/fixture SQL. They create
  isolated tables or seed supporting state and are not runtime product commands.
- Read-only production queries of `fm2_assignment_orders` in object cards,
  queues, inspection evidence and original composition readers do not create an
  order.

No additional production call to `prepareAssignmentOrder(...)`, public creator,
or production `INSERT` owner for the canonical assignment-order table was found
outside the paths above.

## Precise gap

A direct-upload-first HTTP flow needs an approved public application command
that atomically validates and persists a new assignment-order identity and its
chosen installer/control-engineer composition **without** rendering or
persisting template artifacts, then returns the exact `assignmentOrderId` needed
by `submitAssignmentOrderOriginal(...)`. At the inspected commit that command
does not exist. Reusing `prepareAssignmentOrder(...)` still generates the PDF;
calling the environment's private persistence or writing SQL from HTTP would
create a second mutation owner and violate the repository constitution.

Therefore an HTTP-only change cannot truthfully deliver first-time direct upload
without either hidden template generation or a new separately gated production
application seam. This finding is independent of route/admission-contract
review.

## Exact evidence identities

All hashes below are SHA-256 of bytes at the inspected commit:

| File | SHA-256 |
| --- | --- |
| `specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md` | `4cae80e141ad4caf758e792d0ae5a8383c32f6b9d6a779d6eace6122ec71b255` |
| `app/InstallationProcess/InstallationProcess.php` | `ba8a2d73ce3c96c0da7eb947727c823592118d74c7215505ea34b3bba64fc7a4` |
| `app/InstallationProcess/MariaDbInstallationProcessEnvironment.php` | `5cd931da1ff1bcd356ba2177a3edd0bb56b6aeee1d3f4accb477bd79cbc4a26a` |
| `app/PilotHttp/PilotE2ECoordinator.php` | `f6491662738821743976e06086bcb988269c78a4b3d87b9899df4f65575b30b0` |
| `app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php` | `4c893c34377546ded04fc094bf5cbfd8dd5647655416ec25a8e6e28c65ef114d` |

Inventory commands used `git grep`/`git show` against the exact commit rather
than relying on later worktree state. Required product/process sources read were
`AGENTS.md`, `PRODUCT.md`, `CONTEXT.md`, `docs/fmonitor-2-pilot-spec.md`,
`docs/fmonitor-2-pilot-data-model.md`, `docs/development-process.md`, and the
original-upload executable specification above.
