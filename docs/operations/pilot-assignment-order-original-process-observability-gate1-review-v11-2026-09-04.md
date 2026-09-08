# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v11 process observability amendment — independent Gate 1 review

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/assignment_projection_gate1`
- Reviewed commit: `b98dfc7a4767090f2690c6fd039dbaf58f59a622`
- Prior approved v10 Gate 1 record: `docs/operations/pilot-assignment-order-original-database-setup-gate1-rereview-v10-2026-09-04.md`
- Gate 3 finding record: commit `2cc93762c91d1137717ad75bb77d50c122d2bd58`
- Projection gap record: commit `93ad98d`
- Scope: checklist/decoy row-to-projection amendment only
- Verdict: **CHANGES_REQUESTED**

The reviewer did not author or edit the reviewed executable specification,
OpenSpec artifacts, tests, support oracle or production code. This append-only
review record is the only artifact added.

## Blocking findings

### G1-v11-1 — opening state is mapped to the wrong public process value

The new rule makes checklist availability `available` only when
`fm2_installation_cases.process_state='opened'` and all three opening fields are
non-null. The established public opening contract persists and exposes
`processState = working`: `specs/OPEN-INSTALLATION-001.md` sections 4–6 and
`specs/PERSISTENCE-OPEN-001.md` sections 4–5 name that literal, and current
public checklist admission likewise requires `working`. No reviewed artifact
defines `opened` as a stored process-state value.

Consequently a genuinely opened canonical case with all opening fields present
would still hash as `blocked_until_opening`. That is inconsistent with the
product rule that the checklist becomes available after explicit opening and
would make the no-mutation oracle report the wrong state.

Required correction: use the approved stored opening state (`working`) or cite
an owner-approved state-contract change. Retain the requirement that all
opening fields are non-null and that original/order/task facts are not digest
inputs.

### G1-v11-2 — two normative checklist projection definitions conflict

The new section 15 paragraph defines exactly one checklist identity derived
solely from the target `fm2_installation_cases` row. Existing normative section
16 still says `checklistSha256` is the key-sorted projection for exact
`(caseId, orderId)`, covers *every* checklist identity, is ordered by binary
checklist identity, and includes an empty projection. The delta specification
also says “including empty projection”, while the new row mapping does not say
what happens when the exact target case row is absent (empty items versus the
already specified evidence-read failure).

A test or reader cannot independently determine whether `orderId` participates,
whether one or multiple identities are emitted, or when `{"items":[]}` is the
required result. This is precisely the ambiguity Gate 1 must close before the
Gate 3 projection test is repaired.

Required correction: replace the superseded section-16 wording with one
coherent canonical rule. Explicitly state the behavior for a missing target
case and whether an empty checklist projection is reachable; state the exact
JSON member order. If `orderId` is deliberately ignored for this digest, say so
there as well as in the new paragraph.

## Confirmed properties

- The new Example-A checklist JSON hashes exactly to
  `ccb8260ee585db0d0cec53f376d71a66869cf1eca3f287e5a5d7a4e91a04d546`.
- The decoy JSON for case `9999`, legacy identity `99999`, marker
  `fixture-decoy-v1`, null opening fields, fixed timestamps and lock version 1
  hashes exactly to the retained
  `963ca80eddc50543eb940cf813923bd451d0974585a529e7880107df6982e2ca`.
- `decoySha256` is unambiguously derived from every non-target prefixed case
  row, uses numeric case-ID ordering, exact `{caseId,marker}` member order, and
  defines the zero-row projection as `{"items":[]}`.
- Original acceptance cannot alter either proposed input set: both read only
  `fm2_installation_cases`, while this slice may mutate only private original
  evidence persistence and may not mutate case/opening facts.
- The evidence reader remains a fresh-connection, read-only public factory
  product. It receives no fixture callback, performs no DDL/DML, does not use
  `information_schema` or the command repository, and retains total fixed
  read/close failure behavior.
- Fixture ownership remains DML-only, serializable, exact-identity locked and
  reverse-cleaned only after byte validation; it creates no original fact.
- `openspec validate replace-pilot-registration-with-original-upload --strict`
  passes, but structural validity does not resolve the normative conflicts
  above.

Task 1.12 and the resumed setup RED correction MUST NOT advance from these exact
v11 bytes. A corrected executable/OpenSpec batch requires a fresh independent
Gate 1 review.

## Verification evidence

```text
$ sha256(canonical checklist JSON)
ccb8260ee585db0d0cec53f376d71a66869cf1eca3f287e5a5d7a4e91a04d546

$ sha256(canonical decoy JSON)
963ca80eddc50543eb940cf813923bd451d0974585a529e7880107df6982e2ca

$ sha256('{"items":[]}')
eef46741adfc3a9f76294d3b78f37a45f113092ac9d44ee77c7a038a88ff09a1

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check
PASS (before adding this append-only review; no output)
```

## Exact reviewed SHA-256 inputs

```text
4b843074746bbb9c81a2faa7754f7f3c8abbb1c0360b05dd573034f8f6a5d00b  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
f4851a33f5bf56c6797c2586791798d8f16d5cfa347162fe84a5b0915cd93a9d  openspec/changes/replace-pilot-registration-with-original-upload/design.md
b298fde9e7c126622bd17beafd0b990824fdb2974cd22f6045ff4e35efe60984  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
322199995a2b9a1753ac9e93c8bdd90a530da6e706394f3c18a387c38e4056e1  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
d821da023aeeb6228928e8c2dfec446a23850102abe6938f9f07f4f808a6b487  docs/operations/pilot-assignment-order-original-database-setup-gate1-rereview-v10-2026-09-04.md
cc65deb0130a24e9f019b0605b78a6707a2671a75f686899dc16ecad8bbe5192  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v1.md
24c37e0e0ff55eba8040f6c07f6b73eefbf9602e347586808b4c55c81c657e51  docs/operations/assignment-order-original-database-setup-projection-observability-gap-2026-09-04.md
72d4252762912d5c26dfb35ec0008f4d74a2e9e477ec89a601cbf8ee077ef400  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
35afcdd180441a2bf3631c6715dac225135df5bdc25011f31abfe319ef5c69ee  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
```

The review-record path is metadata because a self-hash is circular.
