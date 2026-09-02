# Premium KTU primary-evidence research — 2026-09-02

## Scope and handling

Read-only investigation of
`/home/antropophag/code/fmonitor-premium-export-report` for the meaning and
calculation of KTU. Primary evidence inspected: application source and tests,
the company order PDF, first-party correspondence and its embedded excerpts,
and the current operational workbook. Personal names, contacts, object
addresses and identifiers are intentionally omitted. No source-repository file
was changed.

This is research evidence, not an executable requirement. Where sources
disagree, the disagreement is preserved rather than resolved by inference.

## Executive finding

The primary sources contain **two different quantities that have both been
called KTU in implementation/discussion**:

1. The normative KTU is a per-worker management assessment for brigade work:
   base `1` plus approved raising/lowering factors `K1..K11`. It distributes an
   already calculated brigade premium pool proportionally among workers.
2. The premium-export refactor derives a worker's contribution from completed
   checklist weight, split equally among the workers attributed to each item.
   Its current `management_coefficient` is always `1`; therefore this is an
   attribution weight, not evidence that normative KTU adjustments were made.

The legacy controller instead counted checklist participation without checking
completion, and displayed a separately normalized value unrelated to the
denominator used for payment. That behavior is code evidence of a defect/oracle,
not a safe business rule.

## Normative formula and field meanings

Company order `docs/Приказ от 20.05.2026 №178.pdf`:

- PDF page 9 (printed page 8), clauses 3.6.1–3.6.4: for brigade work, individual
  KTU is the basis of the worker's premium; base KTU is `1`; the final value is
  `KTU_i = 1 + Σ(K1..K11)`. The producer of works establishes each worker's KTU
  and the head of the installation department agrees it. For individual work,
  KTU is `1`.
- PDF page 8 (printed page 7), clause 3.4.1: the worker premium is
  `(planned installation premium × shaft coefficient × progress coefficient −
  previously paid premium) × deadline coefficient × worker KTU /
  sum of brigade KTU − disciplinary adjustment`.
- PDF page 23 (printed page 22), Appendix 6: the KTU distribution certificate
  carries registration number, worker/profession/personnel number, base KTU,
  raising factor, lowering factor, final KTU and brigade KTU sum. The anonymized
  worked examples are `1 + 0.2 + 0 = 1.2`, `1 + 0 − 0.1 = 0.9`, brigade sum
  `2.1`; an unchanged three-person brigade has `1 + 1 + 1 = 3`.
- PDF page 25 (printed page 24), Appendix 8 defines the permitted factor ranges:

| Factor | Range | Meaning |
|---|---:|---|
| K1 | `0..+0.2` | adjacent/start-up, welding or electrical work |
| K2 | `0..+0.2` | mentoring |
| K3 | `0..+0.1` | initiative |
| K4 | `0..+0.1` | personal contribution to quality improvement |
| K5 | `0..+0.1` | rationalization proposals/innovation |
| K6 | `-0.2..0` | low work quality |
| K7 | `-0.2..0` | late performance |
| K8 | `-0.5..0` | poor labour discipline |
| K9 | `-0.2..0` | disciplinary sanctions |
| K10 | `-0.2..0` | access/site-regime violation |
| K11 | `-0.2..0` | failure to follow management orders |

No inspected normative page says that checklist percentage itself is KTU.
Progress (`Квып`), deadline coefficient (`Ксс`) and KTU are distinct operands
in clause 3.4.1.

## Operational workbook evidence

Primary workbook:
`docs/customer-calculation-current/файл для расчета выплаты монтажникам/Программа_2026_ДЛЯ РАБОТЫ на 31.07.2026 - рабоч 18.08.2026.xlsx`, sheet
`Адресный перечень`.

- Row 3 has five manually populated KTU columns `AT`, `AZ`, `BF`, `BL`, `BR`
  and duplicates them for calculation in `EI:EM`.
- The brigade pool in `EH4` is `EF4-EG4` (amount accrued net of prior payment,
  less deadline penalty in the workbook's decomposition).
- Per-worker formulas are proportional: for worker 1,
  `EN4 = IF(EI4>0, EH4/SUM(EI4:EM4)*EI4, 0)`; workers 2–5 use the analogous
  KTU cells.
- The inspected populated KTU cells are predominantly literal `1`; some rows
  contain multiple worker KTU values. This proves operational manual inputs and
  proportional distribution, but does not prove that every literal was
  approved under Appendix 6 or which K1–K11 evidence supported it.

The accompanying first-party message dated 2026-08-24 identifies this workbook
as the file currently used for payment calculation and explains that changing
the report date changes lateness and the result. It does not redefine KTU.
Provenance: `docs/файл для расчета выплаты монтажникам.msg`, message body and
the attached workbook above.

## Correspondence evidence

First-party correspondence chain
`docs/Формат выгрузки из FMonitor для Службы управления персоналом.msg`, message
dated 2026-07-01 within the chain, numbered point 2:

- the exported KTU values were described as implausibly large;
- the sender asked where KTU should be entered;
- the sender stated that FKR/producers of works make that assessment because
  they directly assess who worked and how.

The same message chain includes an embedded image excerpt, attachment
`image009.jpg`, reproducing the clause 3.4.1 proportional KTU formula and its
operand definitions. This supports the PDF reading independently of the
repository's later discovery notes.

This correspondence is evidence of expected ownership and a known defect, not
an approved data-entry interface or event lifecycle.

## Source-code semantics

### Current refactored calculator

`application/libraries/premium_export/PremiumCalculator.php`:

- lines 21–25 reduce events to the latest completed/cancelled state per
  checklist item;
- lines 32–51 add only completed item weight and split each item's
  `share_basis_points` equally among unique attributed personnel numbers;
- lines 76–82 calculate the object pool from planned premium, shaft coefficient,
  accumulated progress and prior payment, then apply the deadline coefficient;
- lines 192–219 allocate the post-deadline pool proportionally to contribution,
  using largest remainder with personnel-number tie-break for exact kopecks;
- allocation output names `management_coefficient = 1`,
  `effective_weight = contribution`, and `share = contribution / total`.

`tests/premium_export/PremiumCalculatorTest.php` provides anonymized executable
examples:

- lines 23–49: one completed 15% item attributed to one worker gives 1500 basis
  points of contribution and that worker receives the whole current pool;
- lines 51–65: one 10% item attributed to two workers gives 500 basis points to
  each and an equal split;
- lines 105–124: largest-remainder distribution is deterministic by personnel
  number when equal thirds do not divide exactly.

This calculator is consistent with the already accepted FMonitor 2 decision
that a multi-worker checklist item is split equally, but it does not implement
normative K1–K11 assessment.

### Legacy controller

`application/controllers/Integration.php`:

- lines 1625–1633 make `ktu_all` effectively the count of checklist value rows
  having at least one installer (`1/count × count`), without a completion test;
- lines 2130–2146 give a worker `1/count_installers` for every checklist value
  in which that worker appears, also without a completion test;
- lines 1684–1693 and corresponding branches distribute the current pool as
  `pool / ktu_all × worker_value`;
- lines 2084–2116 display `worker_value / COUNT(all fm_install_checklist rows)`
  while the money uses `worker_value/ktu_all`.

Therefore the displayed legacy “KTU” and the allocation share use different
denominators, and incomplete items can affect both allocation weights. This
cannot be promoted as the target formula.

## Cross-check with FMonitor 2 truth

- `PRODUCT.md:44` requires a reproducible dated calculation using workforce
  status, deadlines, crew history, KTU, progress and prior payments. Treating
  progress-derived attribution and KTU as separately versioned inputs best fits
  that wording; collapsing them loses an operand named by product truth.
- `docs/operations/completion-otiz-owner-decisions-2026-09-02.md:68–90` says
  OTiZ does not manually enter KTU, KTU is calculated from checklist completion,
  multi-worker item contribution is split equally, and the remaining formula
  must be recovered and separately confirmed before executable Gate 1.
- Primary normative and correspondence evidence instead assigns KTU assessment
  to the producer of works, with approval and K1–K11 adjustments. This is a
  direct semantic tension. A coherent interpretation is possible—checklists
  calculate **factual contribution**, then normative management KTU modifies
  that contribution—but the order's printed payment formula applies KTU to the
  brigade pool directly and does not describe checklist contribution as another
  multiplier. That interpretation is therefore a design hypothesis, not a
  recovered requirement.
- `CONTEXT.md:152` requires the calculation snapshot to bind versions of facts
  and rules. Any future KTU contract needs dated/versioned KTU evidence or an
  explicit owner-approved rule replacing it; a mutable current value is
  insufficient.

## Ambiguities requiring owner/domain confirmation

1. Does FMonitor 2 use normative KTU `1 + ΣK1..K11`, checklist-derived factual
   contribution, or a composed formula containing both?
2. If both are used, is allocation weight
   `checklist_contribution × management_KTU`, or is checklist contribution used
   only to determine progress while the pool is split solely by normative KTU?
3. Does the first release intentionally fix management KTU to `1`, and if so,
   is that an explicitly approved temporary rule rather than inferred absence
   of K1–K11 UI?
4. Which append-only fact records each K1–K11 value, assessment period,
   producer, agreement/approval, reason and source document?
5. What happens when the brigade KTU sum is zero or a worker's final KTU becomes
   negative? Appendix 8 ranges permit a mathematical minimum below zero if all
   lowering factors are combined, but the inspected order pages state no clamp.
6. Is KTU attached to the whole object/reporting period as Appendix 6 suggests,
   or can it vary by checklist item/date? The primary sources inspected do not
   define per-item KTU.
7. How are rounding and remainder assigned under the normative formula? The
   order gives a ratio but no kopeck-allocation rule; largest remainder is a
   refactor implementation decision supported by tests, not by the inspected
   normative pages.

Until these are resolved, safe characterization may preserve source-labelled
operands and calculations, but a target premium/KTU executable specification
must not claim that either legacy controller behavior or the refactor's
`management_coefficient = 1` is the final approved KTU rule.

## Subsequent owner decision, 2026-09-02

После ознакомления с назначением K1–K11 владелец решил заложить их только как
будущие показатели. В первом test-user release они не вводятся и фактически не
участвуют в расчёте; management KTU равен `1`. Текущий расчёт использует
подтверждённый checklist-derived contribution, включая ранее утверждённое
равное деление multi-worker item. Любое будущее включение K1–K11 требует
отдельной версии правил и Gate 1, поэтому нормативная формула выше сохраняется
как provenance/future model, а не current executable requirement.
