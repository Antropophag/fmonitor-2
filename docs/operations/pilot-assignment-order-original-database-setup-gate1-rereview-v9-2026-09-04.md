# Fresh independent Gate 1 rereview — assignment-order original MariaDB setup v9

Date: `2026-09-04`  
Reviewer: separately tasked agent `/root/assignment_dbsetup_rereview2`  
Reviewed commit: `158d1c9bc978b286e880fca2e079744931534ca7`  
Scope: revised technical MariaDB setup amendment and the complete executable
specification v9 plus all OpenSpec artifacts of change
`replace-pilot-registration-with-original-upload`; no tests or production
implementation reviewed  
Verdict: **CHANGES_REQUESTED**

## Exact reviewed artifacts

```text
9f7c35cf5818a2f9f3613208f35789eaee66a1fafeae27d5da54ad028f04e7d7  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
01cd2a0bdc11bb56d81fbd6518d3c319e0c2f8ddcc9c9f53168b2468c511ef88  openspec/changes/replace-pilot-registration-with-original-upload/design.md
2010196d65210243c6bae5bb016db90caf35d23766b8655aaa9917fd72705979  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
a061abc535528436d3caaadd0f34e7618793fd3ae76f5e7ccb16fd40fbbf43b5  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
96370dba446c557a1f44f0f5696190fe2340755941d430e0d0174fd4a62a7673  docs/operations/assignment-order-original-upload-gate2-database-setup-gap-2026-09-04.md
7634690f6e8a9b4bf10e450625330e665d095fd961a8abb7d14c70944e913987  docs/operations/pilot-assignment-order-original-database-setup-gate1-review-2026-09-04.md
c12c117092010735e7fef6ec94bddab5c4df537d580d758abffe2455fba24bc3  docs/operations/pilot-assignment-order-original-database-setup-gate1-rereview-v8-2026-09-04.md
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

The append-only constructibility gap and both prior `CHANGES_REQUESTED` Gate 1
records were read in full. The reviewer authored none of the reviewed material.

## Findings closed by v9

The lifecycle now gives database setup its own RED, independent Gate 3,
minimal GREEN and fresh Gate 5 before the dependent real-MariaDB command
matrix. The command implementation remains after its own RED and Gate 3.
This closes the circular ordering finding without weakening the delivery gates.

Example A now consistently uses engineer `31`; the section reference is
corrected to section 12, and all six adjacent canonical projection SHA-256
values remain coherent with their UTF-8 JSON literals. Runtime application,
HTTP, worker and evidence-reader DDL remain prohibited. Fixture setup remains
verification-only, serializable, bounded, byte-validated on cleanup, and
forbidden from fabricating original/request/event/audit/blob facts. These close
the corresponding v7/v8 findings.

## Blocking findings

### 1. The purported exact CHECK oracle references columns that do not exist

The manifest gives `retryable` only to
`fm2_assignment_order_original_maintenance_requests`; the maintenance audit
table has no `retryable` column. Yet the declared **exact** CHECK set applies
`retryable=0|1` truth sets jointly to “maintenance requests/audits”. MariaDB
cannot create that CHECK on the audit table, while omitting it contradicts the
statement that these are the complete checks and that there are no others.

The request truth set also requires “all ten evidence fields” non-null/null,
but the manifest has only seven result evidence columns after status/reason:
`root_original_id`, `current_revision_id`, `revision_number`, `document_date`,
`sha256`, `byte_size`, and `uploaded_at_utc`. An independent test cannot know
which three unnamed fields belong to equivalence, or whether “ten” is an error.

This must be split into exact per-table CHECK expressions over columns that
actually exist, and the request evidence tuple must enumerate its columns
rather than give a contradictory cardinality.

### 2. Initial accepted lineage is not insertable under the mandatory FK set

`roots.current_revision_id` is normative non-null and has a mandatory FK to
`revisions.revision_id`. Every revision in turn has a mandatory FK from
`revisions.root_original_id` to the root. MariaDB does not support deferred
foreign keys. Therefore an initial accepted operation cannot insert either row
first while foreign-key enforcement remains active: inserting the root first
has no revision target, and inserting the revision first has no root target.

The executable contract requires the accepted root/revision/result/event/audit
facts to be committed atomically and does not authorize disabling FK checks.
Gate 1 must choose an implementable exact invariant, for example a nullable
root current pointer during the transaction plus an exact terminal-state
repository invariant, or a different non-cyclic schema. That choice affects
the normative columns/equivalence oracle and cannot be left to Gate 4.

### 3. Several allegedly exact CHECKs remain symbolic, so equivalence is not exhaustive

“Canonical lower UUID” and “all opaque root/revision/content IDs are printable
ASCII within their declared column length without slash, backslash or control”
do not enumerate exact normalized MariaDB expressions or even the complete
table/column applicability map. In particular, `requests.current_revision_id`,
revision `previous_revision_id`, and `private_content_identity` are obvious
candidates, while event IDs and the root current pointer make different
nullable/applicability choices. The specification nevertheless says normalized
CHECK semantics are the equivalence oracle and that there are no other CHECKs.

Likewise the audit truth set says it follows the request success/rejected/
conflict sets, but audit status excludes `replayed` while the referenced request
success set is `accepted|replayed`. The maintenance request and maintenance
audit tables have different columns (`retryable` and `next_cursor` exist only
on requests), yet receive one combined truth set. Distinct implementations can
therefore choose materially different constraints and both plausibly claim
conformance.

Gate 1 must enumerate every CHECK by exact table and column, including the
literal UUID/opaque-ID predicates and separate request/audit maintenance truth
sets. Until then clean/equivalent/conflict RED expectations are not independently
determinable.

## Verdict basis

V9 closes the task sequencing and Example-A identity findings, and retains the
safe fixture/runtime boundaries. It does not yet define a self-consistent,
physically insertable, exhaustive seven-table schema oracle. These are Gate 1
contract defects, not implementation details. Task 1.11 remains open; setup RED
task 2.2 must not proceed from this exact batch.

## Verification evidence

```text
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ independently recompute SHA-256 of the six adjacent canonical JSON literals
388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5
b28f40fe02e9b4ca3981a5edaf5e165f9f95531e38c87bc70a8458444b2ace84
89c2844c0f723aacd7b7982b36d6297df53a3d2f2b44a268212ab43149687f42
272d922aa2cdcad49bd98141062fc752eb4f31690a720b46c2fa7a0e1b0fe799
f8ddea8b5d52fccf7edb63d89ba508ef916dd86fc175336fcea05436801ac548
963ca80eddc50543eb940cf813923bd451d0974585a529e7880107df6982e2ca

$ git diff --check
PASS (before adding this append-only review; no output)
```

Only this append-only review record was added. No reviewed artifact, test or
production file was edited.
