# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v13 capability migration recovery — independent Gate 1 review

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_capmigration_gate1`
- Reviewed commit: `d8c23ae377380bc0783acbec92f7a1bad5c5f053`
- Triggering Gate 5 review: `c40c0101f46cbbee0187ea48f569999c3a0c49f1`
- Constructibility gap: `811ee93eca9f57d92ec3ab2c0d203fac3fcc2f5d`
- Scope: technical capability-migration recovery amendment and complete current
  executable specification/OpenSpec change; no tests or production approval
- Verdict: **CHANGES_REQUESTED**

The reviewer did not author the reviewed specification, OpenSpec artifacts,
tests, support oracle or production implementation. This append-only review is
the only authored artifact.

## Blocking findings

### G1-v13-1 — no deterministic failure point between table creations

The triggering Gate 5 finding requires proof of recovery from an
implicit-commit failure between original-table creations as well as proof of
the boundary between complete schema and capability publication. The recorded
Gate 1 gap correspondingly asks for a deterministic verification mechanism
that can stop after a specified manifest member and immediately before
capability publication.

V13 exposes only
`AFTER_SCHEMA_REVALIDATED_BEFORE_CAPABILITIES`. That phase proves the second
boundary, but it cannot induce a failure after one or more CREATEs while a
leading suffix is still missing. An existing trailing conflict must be found
by the mandatory preflight and cause zero DDL; permissions, resource failure
or a race cannot be deterministically tied to a named CREATE without depending
on private SQL/names/algorithm. Therefore Gate 2 still cannot independently
prove that a reported create failure leaves only an exact leading partial,
keeps V4, and converges losslessly on retry.

Add a verification-only named phase carrying an independently observable
manifest position (or an equivalently exact public setup mechanism) after each
successful CREATE and before the next one. Keep it behind the verification
factory only, with the production static seam binding an inert observer and no
runtime selector.

### G1-v13-2 — final ALTER ambiguous outcome is absent from the recovery states

V13 says any ALTER failure throws the fixed unavailable exception and that a
capability ALTER failure may leave full schema with V4. MariaDB DDL plus a lost
connection/response can also make the client observe an exception after the
server committed exact V5. This state is safe because all seven tables were
already revalidated, and the next retry can classify exact V5 as unchanged,
but the normative failure/recovery text and OpenSpec design currently describe
the recoverable failure states only as leading partial/full-schema plus V4.

Specify the definite-pre-execution failure and ambiguous-execution outcome:
after an unavailable final ALTER the durable state may be full exact schema
with exact V4 **or exact V5**, never incomplete schema with V5; retry must
reinspect and converge from either. A deterministic verification phase around
the final ALTER must be sufficient to prove both the pre-publication failure
and ambiguous-success recovery without exposing production fault selection.

### G1-v13-3 — fixed exception message is not an executable exact value

The new text requires
`AssignmentOrderOriginalSchemaMigrationUnavailable` with “message basename,
code 0, previous null”, but neither the class declaration nor any other v13
section defines the literal message. “Message basename” has no referent and
allows independently different Gate 2 expectations. State the exact fixed
message including punctuation/case. Keep redaction, code `0` and `previous`
`null` explicit.

### G1-v13-4 — capability conflict membership is over-broad/ambiguous

The pre-amendment result rule says an incompatible original table returns only
all conflicting owned original logical names. The amendment says “Conflict
`affectedTables()` contains every conflicting original logical table plus
`fm2_process_user_capabilities`”. Read literally, even a conflict caused only
by an original table reports the healthy capability table as affected. It is
unclear whether the capability name is unconditional or appears only when the
capability CHECK classifier itself conflicts.

Define the union exactly: all and only incompatible original logical tables,
plus `fm2_process_user_capabilities` iff its classifier is conflict (or state
the deliberately unconditional alternative). Then binary-sort the complete
result. Also define the capability-enum candidate predicate observably: the
current words distinguish the engineer-position CHECK by intent but do not say
which referenced columns/operators make one arbitrary CHECK a candidate. Gate
2 otherwise cannot independently classify zero/one/multiple candidates without
copying a future implementation heuristic.

## Findings closed by v13

- Exact accepted capability value sets V4 and V5 are now stated, including
  upload-only, correct-only, unexpected subsets/supersets and duplicates as
  conflicts.
- The required nominal order is schema inspection, zero-DDL conflict return,
  leading-suffix creation, full seven-table revalidation, observer, capability
  publication last, then exact V5 reread.
- The migration observer is verification-only; the static production seam uses
  an inert observer, and runtime/env/request/CLI/global selection is forbidden.
- No product workflow, role, authority, upload behavior, composition or opening
  contract is changed by the amendment.

These improvements do not make the create-failure and final-publication
recovery matrix independently executable yet.

## Fixture Gate 5 findings remain separate Gate 2 work

V13 does not and must not claim to close `G5-SETUP-1`: complete fixture-row
preflight, non-case drift, partial multi-row occupancy, byte-validated cleanup
and all-or-nothing sensitivity remain reopened Gate 2 work followed by a fresh
independent Gate 3. This Gate 1 verdict does not approve the current tests or
production implementation and does not advance tasks 2.2, 2.3, 3.1 or 3.2.

## Verification

```text
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check
PASS (before this review record; no output)
```

OpenSpec structural validity does not resolve the observable ambiguities above.

## Exact reviewed hashes

```text
7bb540f85716f99592940e616e39269964cc5c2611ea2454486ad1c9f5a24085  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
0f804241eb473d0cb8ba2a5fe7acf41fe2fbecbfca8617fa1d52fed7bf5c54d9  openspec/changes/replace-pilot-registration-with-original-upload/design.md
a005be8716f867168842b944f4756fa52107ee723e72518f8fd66f1503da1fcf  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
b76861057ce8aef5c80581265f0d077cd888c01a07936c408b0392c99c693722  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
4780c6f754712f054930e975fe392707ef0d70795147750234c0e335ff6f7725  docs/operations/assignment-order-original-setup-capability-publication-gate1-gap-2026-09-05.md
2e6f2e7d4cbe955acfe278cf54efd3b3eb293bc26a63052555e14f1e953f5107  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v1.md
```

The review record omits its own circular hash.
