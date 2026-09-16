# CANONICAL-LIVE-ADMISSION-CONTEXT-001 — canonical live admission context

Source oracle: existing verification planner, immutable role package and external
active-binding contract. Public seams: `python3 tools/delivery/harness.py prepare`,
`record-review`, `state`, `wait --once` and `prepare-merge`.

## R1 — canonical context

After `prepare`, every fresh `state`, `wait --once` and `prepare-merge` invocation
SHALL expose an identical `admission_context` for unchanged source. It SHALL contain
the complete exact verification plan and its SHA-256, the plan-selected commands as
`selected_obligations`, task/input/base/source/candidate binding, current policy
digest, and a projection of exactly `required_reviews` selected by that plan.

The context SHALL be reconstructible solely from the external harness active-binding
and referenced package/plan. Conversation or session transcript is not an input.

## R2 — structured review result

`record-review` SHALL accept only a planner-required `gate3` or `final` result from
an explicitly named reviewer and author. It SHALL atomically retain the structured
result in the existing external active-binding contract, outside the checkout, bound
to change/input, reviewer role, exact source/candidate, plan digest and policy digest.
It SHALL NOT edit candidate bytes. Markdown and agent prose SHALL NOT be parsed.

The result schema SHALL retain `gate`, `verdict`, `reviewer`, `author`, `reviewer_role`,
`recorded_at` and the exact composite `binding`. An approval with absent identity or
reviewer equal to author SHALL be rejected rather than fabricated.

## R3 — freshness and isolation

For every required review the projection SHALL report `MISSING`, `CURRENT`, or
`STALE`. Only one unambiguous exact-bound result may be `CURRENT`; missing,
conflicting, foreign-task/candidate/source/plan/policy or source-invalidated evidence
MUST NOT appear as current `APPROVED`. Historical results remain retained.

## R4 — actual planner review contract

The harness SHALL project exactly the plan's `required_reviews`. FAST with
`["final"]` SHALL have no synthetic Gate 3. STANDARD/CRITICAL SHALL project their
actual planner-selected reviews. The context may map current results into the
existing native admission observation, but this slice SHALL NOT change
`admission.evaluate()` semantics.

## Executable cases

- A: prepare creates exact plan and fresh state restores it.
- B: current Gate approval appears as structured current `APPROVED`.
- C: source-invalidating change makes that approval `STALE`.
- D: absent review remains `MISSING`, never fabricated.
- E: FAST projects only its real final review.
- F: STANDARD/CRITICAL projects reviews required by policy.
- G: state, wait and prepare-merge expose identical context.
- H: a fresh process restores context without conversation history.
- I: foreign task/candidate review is not accepted.
- J: recording a verdict does not change candidate/source hash.

## Non-goals

No T07a admission semantics, new evidence/review subsystem, prose parsing,
candidate-committed verdict, Quality Graph publisher change, T07b+, #153B/C or
product code.
