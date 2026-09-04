# Fresh independent Gate 1 rereview — assignment-order original MariaDB setup v8

Date: `2026-09-04`  
Reviewer: separately tasked agent `/root/assignment_dbsetup_rereview`  
Reviewed commit: `95e6703e4cabb7d147b64f8a73f2b2f106ed679d`  
Scope: revised technical MariaDB setup amendment and the complete executable
specification v8 plus all OpenSpec artifacts of change
`replace-pilot-registration-with-original-upload`; no tests or production
implementation reviewed  
Verdict: **CHANGES_REQUESTED**

## Exact reviewed artifacts

```text
9ee6dde0376c58d406164369bc1bdfe2e38ae4b8f37dd4b2be834a482ca8ab63  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
9385b8ac370c2a2986c7a5c11d7ccf6706dbb6ac29bef98a8c3cf0a67c174fb9  openspec/changes/replace-pilot-registration-with-original-upload/design.md
f542242b21f435fdc700b9b5ec93278b1a424d0906001b84f673398ff13c9f38  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
a061abc535528436d3caaadd0f34e7618793fd3ae76f5e7ccb16fd40fbbf43b5  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
96370dba446c557a1f44f0f5696190fe2340755941d430e0d0174fd4a62a7673  docs/operations/assignment-order-original-upload-gate2-database-setup-gap-2026-09-04.md
7634690f6e8a9b4bf10e450625330e665d095fd961a8abb7d14c70944e913987  docs/operations/pilot-assignment-order-original-database-setup-gate1-review-2026-09-04.md
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

The append-only constructibility gap introduced at commit `ad005ed9` and the
prior `CHANGES_REQUESTED` review committed as
`7c359d79d2ce6a97ecb0982f98bd3602f69a49db` were read in full.

## Findings closed by v8

The amendment now names exactly seven owned logical tables in manifest order
and supplies ordered column/key material, a structural-equivalence rule and
clean/repeat/leading-partial/populated/conflict outcomes. It also supplies the
six literal canonical process projections and their independently recomputed
SHA-256 values; every published digest matches its adjacent UTF-8 JSON literal.

Fixture exceptions are now declared with fixed message/code/previous shape.
Both seed and cleanup are assigned one `SERIALIZABLE` transaction, deterministic
identity locking, rollback-before-commit conflict behavior and a total typed
unavailability outcome. Cleanup validates exact rows before reverse-order
deletion and the separately task-owned database is the only database-level drop
target. Runtime application/HTTP/worker/evidence paths remain forbidden from
calling migration or fixture setup, and the fixture explicitly performs no DDL
and creates no original/request/event/audit/blob facts. These changes close the
prior exception, transaction, bounded-cleanup, runtime-DDL and original-fact
fabrication findings.

## Blocking findings

### 1. Task ordering still makes the required Gate 2 setup unavailable

The executable specification says `Task 3.1 SHALL implement` the migration and
also says that migration is the **only** Gate 2 entry point allowed to create or
reconcile the seven original tables. OpenSpec tasks nevertheless require the
full real-MariaDB RED matrix in task 2.2 and its independent Gate 3 in task 2.3
before task 3.1 implements that entry point.

An absent migration class is a valid narrow RED for the migration behavior, but
the mandated task-2.2 repository/reader/two-worker/fault matrix cannot run on a
real schema until this setup dependency exists. Implementing task 3.1 early
would violate the recorded Gate 2 → Gate 3 → minimal GREEN order; privately
duplicating the migration in the verifier is expressly forbidden. The tasks
must split/reorder the setup seam so its own approved RED and minimal GREEN
precede the dependent real-MariaDB RED matrix, without allowing the product
command implementation to precede its approved tests.

### 2. Example A has two incompatible engineer identities

The normative worked-example common dependencies specify composition
`installers [7001,7002], engineer 901` (section 12, line 242 at the reviewed
commit). The new fixture contract and its canonical projection specify engineer
`31` (lines 995, 1001 and 1015). `seedExampleA()` simultaneously claims to seed
the Example-A prerequisites, so an independent verifier cannot know which
composition is normative. The nearby phrase “section-7 Example A” is also an
invalid reference: Example A is in section 12, while section 7 is initial
acceptance.

This affects composition lookup, the accepted evidence and the supposedly
independent `orderCompositionSha256` oracle. One identity must be selected and
all Example-A literals, references and hashes made coherent.

### 3. The advertised exact constraint/equivalence oracle remains partly symbolic

The seven-table list is concrete, but required constraints are still expressed
as families rather than exact normalized expressions. In particular, request
root/current FKs and audit terminal-request FKs apply “where the referenced row
is required by their status” without enumerating exact status/column/FK pairs;
enum/reason CHECKs refer broadly to values “declared in sections 6, 13 and 16”
instead of listing each table column's allowed values and exact status-to-reason
relation. Section 13 does not itself declare a backed enum. Thus two different
migrations can implement different FK/CHECK sets while both plausibly claim
conformance, and an independent conflict/equivalence test has no single expected
normalized oracle.

Gate 1 must enumerate the exact FK set/actions and per-column CHECK truth sets
(or provide an equally literal canonical manifest representation) before Gate 2
can independently distinguish compatible schema from conflict.

## Verdict basis

V8 materially improves setup ownership and closes several prior findings, but
the dependent RED remains ordered before its sole executable setup dependency,
the fixed Example-A prerequisite is internally contradictory, and the exact
schema-equivalence oracle is not yet total. Therefore task 1.11 cannot be marked
complete and task 2.2 must not resume from this batch.

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

The reviewer authored none of the reviewed artifacts and changed no executable
specification, OpenSpec artifact, test or production file. Only this new
append-only review record was added.
