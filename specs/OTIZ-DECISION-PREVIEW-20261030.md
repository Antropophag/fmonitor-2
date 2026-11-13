# OTIZ-DECISION-PREVIEW-20261030

Bounded correction of established payout-decision preview. Owner screenshot2026-10-29 23:55 shows wrong employee editor after preview; owner requests only participating objects. No financial rules/schema/persistence changes.

- Numeric and opaque string employee IDs retain exact editor identity through preview/error recovery. Never choose the first editor as fallback. Keep submitted decision, reason, operation ID and expected revision in the selected form; only one matching editor opens.
- Decision preview contains exactly the objects in saved calculation where selected employee participates. Keep all recipients on those objects so consequences to co-workers remain visible. Exclude unrelated objects and recipients. Preview totals cover only displayed objects; main calculation totals/data remain saved whole calculation.
- Preview context is explicitly carried in server HTML, resolved via existing editor identity map (not interpolated selector guessing). Deduction preview retains its exact object context and prior semantics.
- Browsing/preview does not save decisions, rewrite calculation revision or apply any payout. Validations and explicit save commands remain unchanged.
- Verify realYii auth/browser with numeric selected second recipient, shared objects + unrelated object, second different employee, posted-value recovery, desktop/narrow, and unchanged durable revisions. Retain OTIZ HTTP and filters/chooser browser checks.
