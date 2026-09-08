# Fresh independent Gate 1 rereview — assignment-order original MariaDB setup v10

Date: `2026-09-04`  
Reviewer: separately tasked agent `/root/assignment_dbsetup_rereview3`  
Reviewed commit: `7192aa7cb177b1c7612086f7628c6ff82bd60a4c`  
Scope: revised technical MariaDB setup amendment and the complete executable
specification v10 plus all OpenSpec artifacts of change
`replace-pilot-registration-with-original-upload`; no tests or production
implementation reviewed  
Verdict: **APPROVED**

## Exact reviewed artifacts

```text
62b42d5b957dd628d09c13d6864152401c54998a5607b1c1835cc8a93ab9c3dd  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
0208895b4a605381ece9cc0bba4cee49ac79c1b17ffa1939f62601c05144051f  openspec/changes/replace-pilot-registration-with-original-upload/design.md
2010196d65210243c6bae5bb016db90caf35d23766b8655aaa9917fd72705979  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
a061abc535528436d3caaadd0f34e7618793fd3ae76f5e7ccb16fd40fbbf43b5  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
96370dba446c557a1f44f0f5696190fe2340755941d430e0d0174fd4a62a7673  docs/operations/assignment-order-original-upload-gate2-database-setup-gap-2026-09-04.md
7634690f6e8a9b4bf10e450625330e665d095fd961a8abb7d14c70944e913987  docs/operations/pilot-assignment-order-original-database-setup-gate1-review-2026-09-04.md
c12c117092010735e7fef6ec94bddab5c4df537d580d758abffe2455fba24bc3  docs/operations/pilot-assignment-order-original-database-setup-gate1-rereview-v8-2026-09-04.md
890a67eb0644485acb65a746acf06cbce50e31b48fd40f47f50cb59ba098c0cd  docs/operations/pilot-assignment-order-original-database-setup-gate1-rereview-v9-2026-09-04.md
```

Canonical context and process consulted:

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
```

The append-only constructibility gap and all three prior `CHANGES_REQUESTED`
Gate 1 records were read in full. The reviewer authored none of the reviewed
material.

## Prior finding disposition

V10 closes every remaining v9 blocker. The maintenance-audit manifest now owns
the `retryable tinyint unsigned` column required by its separately stated truth
set. Request accepted/replayed evidence is explicitly the seven existing
columns `root_original_id`, `current_revision_id`, `revision_number`,
`document_date`, `sha256`, `byte_size` and `uploaded_at_utc`; rejection and
conflict require that same complete tuple to be null.

The impossible non-deferrable roots-to-revisions FK has been removed. Initial
acceptance can insert the root carrying its generated current identity, insert
the matching revision which references that root, and commit atomically.
Correction can insert a same-root child revision and CAS-update the root pointer
within the same transaction. The repository owns and validates the same-root
current-leaf invariant; the remaining revision-to-root and previous-revision
FKs are acyclic and implementable without disabling FK enforcement.

The schema oracle is now exhaustive for the reviewed setup slice. It enumerates
all seven tables in manifest order, exact ordered columns, types, nullability,
PK/unique/index column order, the complete FK set and uniform
`ON UPDATE RESTRICT ON DELETE RESTRICT` actions. It separately fixes UUID
grammar for every `request_id`; exact applicability and bounds for every
root/revision/private-content opaque identity; per-table status, reason,
retryable, evidence and count truth sets; the hash, byte-size, revision and
event constraints; and declares that no other CHECKs or FKs exist. Structural
equivalence covers those normalized properties and treats any extra or changed
owned member as conflict.

## Regression review

The staged lifecycle remains correctly ordered: the setup seam receives its
own demonstrated RED, independent Gate 3, minimal GREEN and fresh Gate 5 before
the dependent command matrix; command production remains after its own RED and
test review. Example A consistently uses engineer `31`, and all six published
projection SHA-256 values independently match their adjacent canonical JSON
literals.

`seedExampleA()` remains total, verification-only and DML-only: its literal
actor/role/capability, case/order/composition, workforce, task, checklist and
decoy values fully define repeat versus conflict. Seed and cleanup use one
`SERIALIZABLE` transaction, deterministic binary-order locking, rollback on
conflict, fixed typed exceptions and reverse dependency cleanup only after
byte-for-byte validation. The test may drop only its separately validated
task-owned database, never prefix-derived tables.

Migration and fixture setup remain unavailable to application, HTTP, worker and
evidence-reader runtime paths. The fixture fabricates no original, request,
event, audit or blob fact, and the fresh evidence reader remains read-only and
independent of fixture composition. This preserves the one public product
mutation seam and the no-runtime-DDL boundary.

## Verdict basis

The v10 amendment is coherent, independently testable and physically
implementable. It closes the database-setup constructibility gap without
changing product behavior or weakening delivery sequencing. Task 1.11 may be
completed for this exact reviewed batch; the setup RED may proceed to its own
Gate 2 and independent Gate 3. This approval does not approve any future test
or production implementation.

## Verification evidence

```text
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ independently recompute SHA-256 of the six adjacent canonical JSON literals
388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5  OK
b28f40fe02e9b4ca3981a5edaf5e165f9f95531e38c87bc70a8458444b2ace84  OK
89c2844c0f723aacd7b7982b36d6297df53a3d2f2b44a268212ab43149687f42  OK
272d922aa2cdcad49bd98141062fc752eb4f31690a720b46c2fa7a0e1b0fe799  OK
f8ddea8b5d52fccf7edb63d89ba508ef916dd86fc175336fcea05436801ac548  OK
963ca80eddc50543eb940cf813923bd451d0974585a529e7880107df6982e2ca  OK

$ git diff --check
PASS (before adding this append-only review; no output)
```

Only this new append-only review record was added. No reviewed artifact, test
or production file was edited.
