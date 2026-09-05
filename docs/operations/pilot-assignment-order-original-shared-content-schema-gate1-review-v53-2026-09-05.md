# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v53 shared content schema — independent Gate 1 review

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_shared_content_gate1`
- Reviewed commit: `0e061ce2ff2cb96cf84349a8d30e852e4cff015b`
- Contradiction record: `379f8bd5f909c373b9b9f24ca0ef8d8162e185a0`
- Scope: v53 executable-spec/OpenSpec schema-v2 amendment only; uncommitted
  command production was neither inspected as a reviewed artifact nor changed
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests, setup implementation, command implementation or prior evidence. This
append-only review is the only authored artifact.

## Findings

### 1. The literal migration algorithm never performs the required v1-to-v2 schema ALTER

The amendment correctly changes the v2 manifest from
`UNIQUE(private_content_identity)` to non-unique
`INDEX(private_content_identity)`, requires preservation of populated rows and
permits immutable revisions to share the exact content-addressed identity. That
resolves the product/data relationship identified by the contradiction record.

However, the normative `Migration order is` paragraph still has only these
schema phases: inspect all seven tables, return every conflict, create a missing
leading suffix, then require the full exact seven-table schema. Its only ALTER
is the later V4-to-V5 capability publication. It never classifies an exact v1
revisions table as an allowed predecessor, never executes the stated one-ALTER
unique-to-non-unique transition, and never revalidates that transition before
capability publication.

Under the simultaneously normative equivalence rule, an existing v1 revisions
table is not exact v2 because its key kind differs. It must therefore either be
reported as a non-equivalent-table `CONFLICT` before DDL, or fail the later
"full exact" v2 reread. An implementation that inserts a schema ALTER between
those steps would be inventing an unlisted ordering and failure/recovery
contract. Consequently the populated-v1 acceptance statement is not
executable at the confirmed public migration seam.

Amend the literal order to classify each owned table as missing, exact v2,
exact historical v1 where permitted, or conflict; return all real conflicts
with zero DDL; perform the schema-v2 ALTER at one named position before the
full-v2 reread and before capability publication; freshly revalidate it; and
define observer/failure/retry behavior for an ALTER whose MariaDB outcome is
unknown after implicit commit. Preserve the existing invariant that V5 cannot
be exposed with an incomplete/non-exact v2 schema.

### 2. Leading-partial v1 recovery and `affectedTables()` are not defined

The contract says a compatible partial deployment may contain a leading subset
of the seven ordered complete tables. A historical v1 interruption can
therefore already contain roots plus the v1 revisions table and be populated.
The new sentence instead says `Clean/leading-partial creates v2 directly`,
which is impossible for that existing revisions table, while only an `exact
populated v1` upgrade is described. It is unclear whether "exact" means the
full seven-table family, only the revisions table, or every present member of a
leading subset.

Publish the exact matrix for at least: clean; leading partial before revisions;
leading partial containing an exact empty or populated v1 revisions table;
full empty/populated v1; exact v2 repeat; and mixed/drift states. For every
accepted case, define the schema result and exact ordered `affectedTables()`:
created v2 tables in manifest order, the reconciled revisions table exactly
once at its manifest position, then `fm2_process_user_capabilities` only after
fresh durable V5 proof. For every conflict, retain binary-sorted conflict output
and zero DDL.

This is also needed to make tasks 2.5/3.3 independently testable instead of
letting a test author select one of several plausible partial-state policies.

### 3. The key-name/equivalence contract is insufficient to construct the DROP safely

Structural equivalence is defined by key kind and ordered columns, while safe
generated constraint names are implementation details. The v53 amendment says
to drop "that unique key" but does not say whether an otherwise equivalent v1
index with a noncanonical physical name is accepted, nor how its discovered
identifier is validated/quoted for DDL. MariaDB requires the physical index
name in `DROP INDEX`; column equivalence alone is not an executable DROP target.

Specify either the one exact accepted v1 physical key name, making a differently
named key conflict before DDL, or an exact safe-name discovery/escaping rule
that selects the sole equivalent unique index. Also define the physical name
or safe construction rule for the replacement non-unique index and make clear
whether v2 equivalence compares or ignores that name. This is a technical
constructibility rule, not a product-behavior change.

## Confirmed coherent properties

Subject to the findings above, the direction is additive and does not change
pilot workflow, roles, authorization, the public command DTO/result, original
lineage, or opening/composition ownership. The v2 manifest changes only the
key kind on `private_content_identity`; columns, checks, foreign keys and all
other indexes remain unchanged. No row DELETE/UPDATE or historical fact rewrite
is authorized. Same-content corrections retain byte-identical content identity,
and reference lookup is correctly existential across revisions. The OpenSpec
task sequencing correctly reopens schema RED/Gate 3, minimal schema GREEN and
fresh Gate 5 before command implementation resumes.

## Verification evidence

```text
$ git rev-parse HEAD
0e061ce2ff2cb96cf84349a8d30e852e4cff015b

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check 0e061ce2ff2cb96cf84349a8d30e852e4cff015b^ 0e061ce2ff2cb96cf84349a8d30e852e4cff015b
PASS (no output)
```

## Exact reviewed hashes

```text
2570d7608db8f88d523d2f59ffa67bdeda1418705537738ad69e3098ca7791d4  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
c74e7d9b972a10a0c9eb1d646440eeb444755f19c183c6fb3d8e2e3b2ea46c29  openspec/changes/replace-pilot-registration-with-original-upload/design.md
33f646c50f96e01e4b7939c5a0af8a114fd9836f8a779dcd08cc416ea75a2ffc  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
1fe383c2effeab4a7655eabeac457102b319a2efe32ff0616d6bb72786d8e970  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
fed6635f3403a11e045aa3e1f450e98dae38c50a130695b42561ea57834c7194  docs/operations/assignment-order-original-private-content-identity-schema-contradiction-2026-09-05.md
```

This record intentionally omits its own circular hash.
