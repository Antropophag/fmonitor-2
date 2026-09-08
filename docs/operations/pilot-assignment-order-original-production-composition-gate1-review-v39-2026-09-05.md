# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v39 production composition — independent Gate 1 review

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_composition_gate1`
- Reviewed commit: `3f7e156f6e9aa8c1d1797ede0fce615a3d0f778c`
- Gap authority base: `b1d1b80`
- Scope: production composition derivation amendment and coherence with the
  active OpenSpec change and previously approved setup/initial tests
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the specification, OpenSpec artifacts, tests,
production implementation, prior approvals or gap record. This append-only
review is the only authored artifact.

## Independently verified properties

The amendment makes the intended semantic source materially clearer and does
not change product workflow, roles, authority, HTTP contracts, composition
application or opening behavior. It correctly forbids caller, legacy-slot and
fixed-hash fallback; requires a read-only production composition reader; fixes
identity form `composition-<assignmentOrderId>-v<versionNo>` with unpadded
decimal components; fixes compact JSON key order
`caseId,compositionIdentity,engineerUserId,installers,orderId`; requires JSON
integer IDs and unique installer IDs in numeric ascending order; and propagates
the same identity/digest into the accepted fingerprint, immutable root and
evidence reader.

The Example A preimage is exactly 115 UTF-8/ASCII bytes:

```text
{"caseId":4512,"compositionIdentity":"composition-81-v1","engineerUserId":31,"installers":[7001,7002],"orderId":81}
```

Two independent local calculations (`shasum -a 256` over the literal bytes and
PHP `hash('sha256', $literal)`) both produce:

```text
388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5
```

The existing result matrix remains compatible: missing/mismatched order maps
to `REJECTED/ORDER_NOT_FOUND`, invalid composition maps to
`REJECTED/INVALID_COMPOSITION`, and dependency unavailability maps to
`FAILED/PERSISTENCE_FAILURE`. These outcomes occur at step 6 before clock,
stream, storage or domain mutation. Historical append-only evidence correctly
retains its old `111...` observations.

## Blocking findings

### G1-39-1 — exact production row inputs remain underspecified

Section 8 says only “the exact `fm2_assignment_orders` row” and “its
`fm2_order_installers` rows”. It does not normatively bind canonical fields to
the exact prerequisite columns that the production adapter must read. In
particular it does not state that:

- the order row is selected by `id=assignmentOrderId` and must have
  `installation_case_id=installationCaseId`;
- `version_no` supplies the identity version and must be a positive integer;
- `control_engineer_user_id` supplies `engineerUserId` and must be a positive
  integer;
- installer membership comes only from rows whose
  `assignment_order_id=assignmentOrderId`, using `installer_tab_id`, with the
  exact validity rule for each member.

“Invalid member” is also not an exact rule: Gate 2 cannot independently decide
whether zero, negative, non-integer/coercible DB values, or rows differing only
in interval/action state are invalid or excluded. The existing schema makes
some physical types likely, but Gate 1 must identify the precise semantic input
columns and row inclusion/validity rule rather than leave the production query
to implementation choice. Amend the executable spec and matching delta/design
with these exact mappings and keep the existing typed error/no-mutation rules.

### G1-39-2 — the changed oracle has not reopened its active Gate 2/3 lineage

The active public-seam test
`tests/InstallationProcess/assignment_order_original_upload_001_test.php`
still asserts the former sixty-four-`1` composition digest in protected root
evidence. Its independent Gate 3 v2 approval reviewed that former expectation.
Nevertheless OpenSpec tasks 2.1 and 2.3 remain checked, while task 4.1 merely
adds the future full matrix. V39 changes a normative expected value consumed by
the already approved initial test; this is not a production-only amendment and
cannot inherit the prior test approval.

The active lifecycle must explicitly require a fresh Gate 2 correction of the
affected initial/public-seam expectation (and every current non-historical test
oracle that embeds `111...`) plus a fresh independent Gate 3 review before any
Gate 4 implementation uses v39. Historical review and RED evidence records
remain unchanged. Update the OpenSpec task state/text so the checked boxes do
not claim the stale Gate 2/3 approval remains authoritative.

## Verdict and next boundary

Gate 1 v39 is **CHANGES_REQUESTED**. Do not check task 1.27 and do not resume
command Gate 2/4 from this batch. A minimal correction may name the exact
source columns and member validity/inclusion rule, synchronize the delta/design,
and reopen/record the affected Gate 2 plus fresh independent Gate 3 work. The
corrected exact batch requires a new separately tasked Gate 1 rereview.

## Exact reviewed hashes

```text
09a4987c3d8c7abe5652afa771abbbde9802d258718b37543e26864f408b2d2a  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
9680374f366e5bb2e509324b2d22a90aa1471e36e8efd5c03f2c5e5877fe9f32  openspec/changes/replace-pilot-registration-with-original-upload/design.md
93b080495a8225bea18c4c33724f3a3ad35af0c9d35304d8a4a977c923043591  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
42feef23ad590adfc52531675945e00760c87e0a4f6a900725366780d0c9cb5f  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
```

Relevant active-test and prior-approval hashes:

```text
0579c317f073fb303ec6bbe89c11b9f42643989546501ac4f35b2702f3559469  tests/InstallationProcess/assignment_order_original_upload_001_test.php
d8fa4511ab63b95d9032d39fe1895e878fead250e68c97befa81038f2261e609  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-initial-v2.md
c439aabf1c1f9ec57b945290661d50900c567b738e34f10cdfd80852ce8c36d2  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
3a10c860721ccaf4fd91b5d6c5016c51757a3d3889b3cf0126244b2e7dc3f1ee  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v23.md
```

This record intentionally omits its own circular hash.
