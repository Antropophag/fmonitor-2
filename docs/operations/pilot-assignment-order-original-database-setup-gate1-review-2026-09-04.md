# Fresh independent Gate 1 review — assignment-order original MariaDB setup v7

Date: `2026-09-04`  
Reviewer: separately tasked agent `/root/assignment_dbsetup_gate1`  
Reviewed commit: `1d815da62e7ab66ac2a20a966af9a91a3545da8f`  
Scope: technical MariaDB setup constructibility amendment only, with regression
review of the complete executable specification v7 and all OpenSpec artifacts
of change `replace-pilot-registration-with-original-upload`; no tests or
production implementation reviewed  
Verdict: **CHANGES_REQUESTED**

## Exact reviewed artifacts

```text
1e025cc909ba7c7179b7646af36bde0275a969d8324ba342afe3ad1c21179716  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
fd7cb1aea4dc2b9fdfed87e1ff562d7393d3827f115f4433dfa3048f9e0268a9  openspec/changes/replace-pilot-registration-with-original-upload/design.md
f542242b21f435fdc700b9b5ec93278b1a424d0906001b84f673398ff13c9f38  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
8f60190b4e7d23b384a656a44b74397d99c1e835be131ca45fd356c838a18d3c  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
96370dba446c557a1f44f0f5696190fe2340755941d430e0d0174fd4a62a7673  docs/operations/assignment-order-original-upload-gate2-database-setup-gap-2026-09-04.md
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

Prior Gate 1 records and owner approval through checklist-evidence task 1.10
were also read. Their already approved command, evidence-reader,
worker-safe-log and `checklistSha256` decisions remain unaffected by this
verdict.

## What the amendment resolves

The amendment chooses the correct ownership direction. It names one public
version-1 migration entry point, explicitly forbids application, HTTP, worker
and evidence-reader runtime DDL, and separates a verification-only prerequisite
fixture from original/request/event/audit/blob facts. The fixture accepts no SQL
or callback, the evidence reader stays independently fresh and read-only, and
the intended repeat/conflict outcomes are bounded enums. These choices preserve
the single product mutation seam and do not fabricate an accepted original.

## Blocking findings

### 1. The version-1 migration is named but its independently testable contract is deferred

The specification explicitly says exact table, column, index and constraint
definitions remain the task-3.1 migration contract. It does not name the owned
logical tables or define structural equivalence. Consequently Gate 2 cannot
independently determine the expected `affectedTables()` list, construct a
compatible partial schema, construct one non-equivalent table for the conflict
case, or prove that all required uniqueness/CAS/foreign-key invariants exist.

This is circular with the mandated ordering: tasks 2.2 and 2.3 precede task 3.1,
but the only allowed real-MariaDB setup is promised to be implemented by task
3.1 and its normative schema is also deferred there. A missing class can be an
initial RED for the migration seam; it cannot make the required real
repository/reader/worker matrix runnable or distinguish missing product
behavior from broken setup. Gate 1 must fix the exact version-1 logical schema
and equivalence/reconciliation oracle before that RED is authored. Task 3.1 may
then implement, rather than decide, that approved contract.

### 2. `seedExampleA()` does not specify the full fixed prerequisite values

The fixture names actor/case/order/composition identities, two installer IDs,
one engineer ID and the composition hash, but delegates the case, opening,
tasks, checklist and decoy rows to “fixed ... projections used by section 16”.
Section 16 defines only output shapes and digest algorithms; it gives no literal
fixture rows or expected digest values. The contract likewise omits the exact
active role assignment/user-directory values, order/case linkage and state,
composition member snapshots/order, process-task/checklist identities and
availability values, and unrelated decoy identities/values.

Therefore an independent test cannot calculate before/after
`caseSha256`/`openingSha256`/`tasksSha256`/`checklistSha256`/`decoySha256`, prove
that only the intended identities were inserted, or decide whether an occupied
prerequisite is an exact idempotent repeat or a conflict. Those literals (or an
equally exact typed fixture DTO/result oracle) must be normative rather than
chosen by the production fixture implementation.

### 3. Fixture failure and cleanup are not public, total contracts

`AssignmentOrderOriginalVerificationFixtureConflict` is referenced but never
declared. Its fixed message/code/previous shape, behavior for unavailable DB,
transaction/rollback guarantee, and outcome for a conflict discovered after a
prior prerequisite check are unspecified. “Before DML” is also not an
implementable atomic guarantee under concurrent occupation without a declared
locking/transaction rule.

The amendment delegates removal to “existing task-owned test cleanup”, while
the named migration is the only Gate 2 entry point allowed to create or
reconcile owned original tables and exposes no cleanup seam. Section 13 requires
removing an owned prefix but supplies neither exact bounded drop protocol nor a
public owner for it. The RED author would again need private table knowledge or
unbounded prefix-derived DDL. Define deterministic, validated cleanup ownership
and exact fixture failure/rollback semantics.

## Verdict basis

The amendment protects runtime boundaries and avoids original-fact fabrication,
but it does not close the constructibility gap recorded at `ad005ed9`. The
missing schema, complete fixture oracle, total conflict semantics and cleanup
protocol are acceptance-affecting details, not implementation choices. Task
2.2 cannot resume honestly on this exact batch, and task 1.11 must remain open
until an amended exact-hash batch receives a fresh independent Gate 1 review.

## Verification evidence

```text
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ rg -n "AssignmentOrderOriginalVerificationFixtureConflict" specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md openspec/changes/replace-pilot-registration-with-original-upload
specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md:915:AssignmentOrderOriginalVerificationFixtureConflict before DML
openspec/.../spec.md:207:fixture ... throws fixed AssignmentOrderOriginalVerificationFixtureConflict

$ git diff --check
PASS (before adding this append-only review; no output)
```

The reviewer authored none of the reviewed artifacts and changed no executable
specification, OpenSpec artifact, test or production file. Only this new
append-only review record was added.
