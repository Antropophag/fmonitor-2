# CHARACTERIZE-PREMIUM-SNAPSHOT-DETERMINISM-001 v0.1

Status: `DRAFT / OWNER_APPROVAL_REQUIRED`. Strictly `PILOT_ONLY` characterization
of `premium-calculation-v1`. Product approves reproducible dated snapshots, but
does not yet approve these coefficients, Kss/KTU norms, evidence-date admission,
reason vocabulary, signed closure meaning or financial release. Those remain
GRILL-001. No HTTP, actor, authorization, DB, snapshot persistence/allocation,
acceptance or payment command is covered.

## Public oracle seam and isolation

Future entry: `php tests/Verification/characterize_premium_snapshot_determinism_001_test.php`.
It SHALL load the unchanged calculator and call only its public static
`calculate(array $operands, array $paymentEvidence, array $exclusions=[])`.
Private helpers/reflection, HTTP wrappers, production fixture builders and copied
production formula constants cannot supply expected results.

The oracle uses only literal in-memory arrays. Before cases it requires 64-bit
PHP (`PHP_INT_SIZE=8`); otherwise `SETUP_FAILURE`. A returned/exception mismatch
is `REGRESSION`. It performs no DB/network/filesystem/session/clock operation,
creates no artifacts and leaves no process. Exact exception messages are
diagnostic only; assertions fix exception class `InvalidArgumentException` and
the rejected input family, not English copy.

## Literal provenance and fact constructors

These exact source arrays are literals, including key order:

| Token | `label` | `locator` | `contentSha256` |
|---|---|---|---|
| REPORT | `Gate 1 report command` | `fixture://premium/report` | `845e91831319e89c4d656bdb80c278ac09a7230d61e5dfd2e1b1fbb436ac8917` |
| PREMIUM | `Gate 1 contract premium` | `fixture://premium/premium` | `870dc23d21836b97b58a7753922edc8512764e83c02586f3d8f14c11f760550b` |
| SHAFT | `Gate 1 shaft coefficient` | `fixture://premium/shaft` | `a6b1880837d5e928dfd222acd61aaee3c9f9d2047776eb2609bb4b059d23ed57` |
| PROGRESS | `Gate 1 confirmed progress` | `fixture://premium/progress` | `b5f90a96e680140d51e2ba945461716e44cc385940aee21f66020e66193647b7` |
| DEADLINE | `Gate 1 contract deadline` | `fixture://premium/deadline` | `dfc8aeb39828e31c4cf8fec553c76b65cf91b5ec8b2b00f397788b9f58bbd80e` |
| COMPLETION | `Gate 1 completion lookup` | `fixture://premium/completion` | `21606b5837ee49171e56cff2afe21083fb4dfe5c15c79b54695c2dc335181c89` |
| CLOSURE | `Gate 1 accepted closure` | `fixture://premium/closure` | `6d4cb937d6d22521566bba561458d0d1952df6df7a80e46ef5dab9014fbc3557` |
| PAYOUT | `Gate 1 payout reconciliation` | `fixture://premium/payout` | `04dc4f3db0e1d5ced709482dc7b86ade6e16a0503c5cab4bc0d9e5ab539a7fc1` |
| EXCLUSION | `Gate 1 reconciliation exclusion` | `fixture://premium/exclusion` | `180a26f2655f92f654a1662d7af03d8ba784a36a43e1541fc89d32324d91a015` |

The verifier may compute these nine fixture hashes with an independent generic
SHA-256 primitive and SHALL first compare each to its Gate 1 precomputed literal
hex recorded in the test. For token `T`, hashed bytes are exactly the lowercase
ASCII/UTF-8 token shown by its locator suffix (`report`, `premium`, `shaft`,
`progress`, `deadline`, `completion`, `closure`, `payout`, `exclusion`), with no
BOM, whitespace, NUL or trailing LF. It MUST perform this equality check and
MUST NOT call production provenance validation.
Every operand fact has exact ordered keys `value,effectiveDate,source`.

## Primary worked example

Exact operands:

- reportDate: value/effective date `2026-08-31`, REPORT;
- premiumCents: `10_000_000`, effective `2026-01-01`, PREMIUM;
- shaftBp: `8000`, effective `2026-01-01`, SHAFT;
- progressBp: `5000`, effective `2026-08-20`, PROGRESS;
- deadlineDate: value `2026-08-21`, effective `2026-01-01`, DEADLINE;
- completionDate: value `null`, effective `2026-08-31`, COMPLETION.

Payments have ordered members `closures,actualPayouts`. Closures contains one row
`amountCents=500000, closedOn=2026-08-15, source=CLOSURE`; actual payouts one row
`amountCents=123456, paidOn=2026-08-25, source=PAYOUT`. Exclusions is empty list.

Independent integer work:

- comparison date `2026-08-31`; late days `10`; Kss `10000-100×10=9000`;
- fund `intdiv(10_000_000×8000,10000)=8_000_000`;
- progress amount `intdiv(8_000_000×5000,10000)=4_000_000`;
- accrued `intdiv(4_000_000×9000,10000)=3_600_000`;
- closed `500_000`; pool `3_100_000`; remaining fund `7_500_000`;
- actual paid `123_456`; discrepancy `-3_476_544`; distributable `3_100_000`.

Result SHALL have version `premium-calculation-v1`, unchanged operand/payment
arrays, empty exclusions, progress `5000`, Kss `9000`, exact amounts above,
reasons exactly `['DEADLINE_PENALTY','PAYOUT_DISCREPANCY']`, and five trace rows
in exact order `fund,progress,deadline,accrued,pool` with current literal formula
strings and independently computed result fields. Two calls return strictly
equivalent typed nested arrays. All values/formula strings/reasons are
`PILOT_ONLY`. Exact `formulaTrace` is the following ordered list; keys in each
row appear in the shown order:

1. `step=fund`, `formula=premiumCents * shaftBp / 10000`,
   `resultCents=8000000`;
2. `step=progress`, `formula=fundCents * progressBp / 10000`,
   `resultCents=4000000`;
3. `step=deadline`, `formula=max(0, 10000 - 100 * daysLate)`, `daysLate=10`,
   `resultBp=9000`;
4. `step=accrued`, `formula=progressAmountCents * kssBp / 10000`,
   `resultCents=3600000`;
5. `step=pool`, `formula=max(0, accruedCents - closedBeforeCents)`,
   `resultCents=3100000`.

## Date/comparison and arithmetic matrix

Each case changes only stated literals, keeps valid provenance/payments empty,
and fixes exact result independently:

| Case | report | deadline | completion | late days | Kss |
|---|---|---|---|---:|---:|
| before deadline | `2026-08-20` | `2026-08-21` | null | 0 | 10000 |
| on deadline | `2026-08-21` | `2026-08-21` | null | 0 | 10000 |
| completion before report | `2026-08-31` | `2026-08-21` | `2026-08-26` | 5 | 9500 |
| completion on report | `2026-08-31` | `2026-08-21` | `2026-08-31` | 10 | 9000 |
| completion after report | `2026-08-31` | `2026-08-21` | `2026-09-01` | 10 | 9000 |
| Kss floor | `2027-01-01` | `2026-08-21` | null | 133 | 0 |

Zero operands `(premium,shaft,progress)=(0,0,0)` retain primary late dates, yield
all formula amounts zero, Kss `9000`, and reasons exactly
`['DEADLINE_PENALTY','NO_NEW_AMOUNT']`. Maximum accepted operands
`premium=1_000_000_000_000, shaft=20000, progress=10000` with same-day deadline,
no payments yield fund/progress/accrued/pool/remaining/distributable all
`2_000_000_000_000`, within signed 64-bit. A truncation case
`premium=101,shaft=3333,progress=3333`, same-day dates, yields fund `33`, progress
amount `10`, accrued/pool/remaining/distributable `10/10/33/10`.

## Payment evidence matrix

- Empty actual-payout list: `actualPayoutCents=0`, discrepancy SQL-equivalent
  result value `null`, no `PAYOUT_DISCREPANCY`.
- One actual payout `3_599_999` on primary inputs: fund/progress/accrued/closure/
  pool unchanged; actual total `3_599_999`, discrepancy `-1`, reason present.
- One actual payout `3_600_000`: discrepancy `0`, no discrepancy reason.
- Primary closure changed to `3_600_000`: pool/distributable `0`, remaining
  `4_400_000`, reasons ordered `DEADLINE_PENALTY,NO_NEW_AMOUNT,PAYOUT_DISCREPANCY`.
- Closure `8_500_000`: pool/distributable/remaining all `0`; same ordered reasons.
- Signed closures `+600_000,-100_000` net to primary `500_000` and reproduce its
  entitlement amounts. This is `PILOT_ONLY`, not target reversal semantics.
- Closure rows netting `-1`, or payout rows netting `-1`, throw
  `InvalidArgumentException` and return no partial result.
- Missing either `closures` or `actualPayouts`, associative instead of list
  payment rows, scalar row, non-int amount, abs amount above `1e12`, invalid date
  or provenance each throw `InvalidArgumentException`.

## Exclusions and reason ordering

Primary inputs plus list exclusion
`code=UNPROVEN_INSTALLER,effectiveDate=2026-08-20,source=EXCLUSION` preserves
accrued `3_600_000`, set distributable `0`, normalize to exact numeric list with
only `code,effectiveDate,source`, and reasons become exactly
`DEADLINE_PENALTY,PAYOUT_DISCREPANCY,CALCULATION_EXCLUDED`.

The same value under associative input key `case-a` yields the same normalized
numeric exclusions list. Extra exclusion field `ignored=literal` is omitted from
normalized output. Exact rejected replacements are: element string `scalar`;
blank code `''`; short code `AB`; lowercase code `abc`; long code of exactly 81
ASCII `A` bytes; effective date `2026-02-30`; and the exact source mutations
below. Each throws `InvalidArgumentException`.

A complete all-reasons case starts from primary operands, changes closures to
the sole row `amountCents=3600000,closedOn=2026-08-15,source=CLOSURE`, changes
actual payouts to sole row `amountCents=1,paidOn=2026-08-25,source=PAYOUT`, and
adds the valid list exclusion above. It yields fund `8000000`, accrued `3600000`,
closed `3600000`, pool/distributable `0`, remaining `4400000`, actual paid `1`,
discrepancy `-3599999`, Kss `9000`, and all four reasons exactly in order:
`DEADLINE_PENALTY,NO_NEW_AMOUNT,PAYOUT_DISCREPANCY,CALCULATION_EXCLUDED`.

## Operand validation and permissive contrasts

For each of six operands, the named one-axis cases are: remove the operand key;
replace its fact with string `scalar`; remove only `value`; change only
`effectiveDate` to `2026-02-30`; change only source `label` to three ASCII spaces;
change only source `locator` to three ASCII spaces; or change only
`contentSha256` to 64 uppercase `A`, 63 lowercase `a`, or 64 lowercase `g`.
Each throws `InvalidArgumentException`.
Required
date values reject separately impossible `2026-02-30` and non-`Y-m-d`
`31-08-2026`; completion accepts only null or exact
date. For each numeric operand, `value_numeric_string` is the decimal-string
form of its primary value (`10000000`, `8000`, `5000`), `value_float` is the
same numeric value as PHP float, `value_negative=-1`, and `value_above_max` is
respectively `1000000000001`, `20001`, `10001`; each rejects. Blank source
label/locator and uppercase,
short or non-hex hash reject; surrounding whitespace in nonblank label/locator
is accepted and echoed unchanged (`PILOT_ONLY`).

Payment rejection one-axis cases start from primary: replace `closures` with
associative collection `['row-a'=><the valid closure row>]`; replace its row with
string `scalar`; change only `amountCents` to string `500000`; change it to
integer `1000000000001`; change only `closedOn` to `2026-02-30`; or apply each
exact blank-label/blank-locator/uppercase-hash/short-hash/nonhex-hash source
mutation above. Equivalent actual-payout cases use string `123456`, integer
`1000000000001`, and `paidOn`. Exclusion source rejection uses those same five
exact label/locator/hash mutations.
Every named case throws and transcript names its family/member.

The exact future-date contrast starts from primary and changes only closure
`closedOn` from `2026-08-15` to `2026-09-30`, after report date. Current result
still includes `500000`: all primary amounts/reasons remain identical and echoed
payment date is `2026-09-30`. A second call changes only the valid exclusion
`effectiveDate` to `2026-09-30`; it is accepted, normalized unchanged, preserves
accrued `3600000`, sets distributable `0` and adds `CALCULATION_EXCLUDED` after
the two primary reasons. These are syntax-only `PILOT_ONLY` observations.

The accepted surrounding-whitespace contrast changes only PREMIUM source label
to exact bytes ` Gate 1 contract premium ` (one leading/trailing ASCII space);
it is accepted and echoed byte-identically with primary numeric result.

The exact extras contrast appends, in order: premium fact key
`note=literal-extra` after `source`; PREMIUM source key
`kind=literal-source-extra` after `contentSha256`; closure row key
`reference=literal-closure-extra` after `source`; and payment top-level key
`batch=literal-payment-extra` after `actualPayouts`. All four appear unchanged in
their echoed paths and primary numeric result is unchanged. The exact key-order
contrast rebuilds only the top-level operands order as
`progressBp,reportDate,premiumCents,shaftBp,deadlineDate,completionDate`; echoed
`operandEvidence` has that order and result remains primary. Exclusions alone are
normalized. The verifier SHALL prove ordinary post-call caller mutation follows
PHP copy-on-write: after calculating primary, caller changes its local
`operands['premiumCents']['value']` from `10000000` to `1` and local closure
`amountCents` from `500000` to `2`; already returned
`operandEvidence.premiumCents.value` remains `10000000`, returned payment row
remains `500000`, and all returned amounts remain primary values.
Reference-containing arrays are outside this minimal oracle.

The source permits an unbounded number of individually bounded payment rows.
On 64-bit PHP at least 9 223 373 positive `1e12` rows are required to cross
`PHP_INT_MAX`; the oracle MUST NOT allocate/execute that resource-amplifying
case. Absence of count/aggregate guard is a documented source risk requiring a
separate target bound/error contract.

## Transcript, canonical integration and Done

Two separate successful runs emit exactly these LF-terminated lines in order:

```
PREMIUM_DETERMINISM version=premium-calculation-v1 replay=stable hashes=9
PREMIUM_DETERMINISM primary fund=8000000 accrued=3600000 closed=500000 pool=3100000 remaining=7500000 distributable=3100000 kss=9000 reasons=DEADLINE_PENALTY,PAYOUT_DISCREPANCY trace=5
PREMIUM_DETERMINISM dates before=10000 on=10000 completion_before=9500 completion_on=9000 completion_after=9000 floor=0
PREMIUM_DETERMINISM boundaries zero=ok maximum=ok truncation=33,10,10
PREMIUM_DETERMINISM payments empty=ok payout_minus_one=ok payout_equal=ok pool_floor=ok remaining_floor=ok signed=ok rejected=ok
PREMIUM_DETERMINISM exclusions list=ok associative=ok normalized=ok all_reasons=ok rejected=ok
PREMIUM_DETERMINISM validation operands=ok payments=ok provenance=ok dates=ok ranges=ok
PREMIUM_DETERMINISM contrasts future_closure=included future_exclusion=accepted extras=echoed cow=preserved overflow=source_risk
PREMIUM_SNAPSHOT_DETERMINISM_OK
```

There are no random/live values to normalize. On preflight failure the sole
stdout line is `SETUP_FAILURE premium_snapshot_determinism php_int_size=<N>` and
exit is nonzero. On assertion failure the final stdout line is
`REGRESSION premium_snapshot_determinism case=<CASE_ID> outcome=<OUTCOME>` and
exit is nonzero; both substitutions come from the finite catalogues below and
exclude exception English/message/stack and multiline values.
Previously completed milestone lines may precede regression, but success marker
MUST be absent. Stderr diagnostics are non-normative.

`CASE_ID` belongs to this finite exact catalogue grammar (each brace expands to
one listed literal; only displayed combinations exist):

```
hash.{report,premium,shaft,progress,deadline,completion,closure,payout,exclusion}
primary.{result,replay,trace,reasons}
date.{before,on,completion_before,completion_on,completion_after,floor}
boundary.{zero,maximum,truncation}
payment.{empty,payout_minus_one,payout_equal,pool_floor,remaining_floor,signed,negative_closure,negative_payout,missing_closures,missing_payouts}
payment.{closures,actualPayouts}.{non_list,scalar_row,amount_type,amount_above_max,date,label,locator,hash_upper,hash_short,hash_nonhex}
exclusion.{list,associative,extra_normalized,all_reasons,element,code_blank,code_short,code_lower,code_long,date,label,locator,hash_upper,hash_short,hash_nonhex}
operand.{reportDate,premiumCents,shaftBp,progressBp,deadlineDate,completionDate}.{missing,scalar,no_value,effective_date,label,locator,hash_upper,hash_short,hash_nonhex}
operand.{reportDate,deadlineDate,completionDate}.{value_date_impossible,value_date_format}
operand.{premiumCents,shaftBp,progressBp}.{value_numeric_string,value_float,value_negative,value_above_max}
contrast.{surrounding_whitespace,future_closure,future_exclusion,extras,key_order,cow}
canonical.{transcript,registration,regression}
```

`OUTCOME` is exactly one of `return_mismatch`,
`unexpected_invalid_argument`, `unexpected_throwable`, `missing_result`.
`return_mismatch` means a call returned but its typed result differed, including
when rejection was expected. `unexpected_invalid_argument` means a successful
return was expected but `InvalidArgumentException` occurred.
`unexpected_throwable` means any other Throwable occurred. `missing_result`
means the bounded call completed without a return value or Throwable, detected
by the harness result sentinel remaining unchanged. Diagnostic exception
class/message and value diff go only to stderr. Thus stdout is stable and
contains no implementation exception copy, hashes or unbounded values.

After owner approval only: demonstrated RED, fresh independent test review,
minimal GREEN, expanded reviewed RED/GREEN, regression plus
`git diff --check`/`make architecture-check`, and fresh independent code review.
The verifier runs exactly once in canonical characterization; regression makes
that stage and aggregate red. Done requires no calculator/API/schema/domain
change and PB-08 still records target formula/admission/release as NEEDS_GRILL.
