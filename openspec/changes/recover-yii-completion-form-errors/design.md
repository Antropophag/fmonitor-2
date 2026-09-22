# Design

The controller owns only transport presentation. It carries a bounded
completion-form state into the existing ObjectCardController render seam, which
re-reads current authorization and document facts before rendering. Validation
errors map to named fields; domain conflicts map to the submitted form. Access,
session, malformed request and infrastructure failures stay fail-closed.

The completion partial accepts one optional state object keyed by the submitted
action. It escapes retained values, marks the field/error association, opens the
matching correction `details`, and exposes stable `data-*` hooks. No values are
stored in session, URL, another object, or another form.

A small completion-only ES module intercepts those forms. It guards one in-flight
submit per form and consumes only same-object HTML error responses. Unknown
outcomes leave the original DOM untouched and add a local warning. There is no
automatic retry. Successful redirect handling remains browser navigation.

Database/schema, backup/restore, deployment/readiness, imports and domain replay
are inapplicable because no persistence boundary changes. Existing canonical
fixtures and HTTP owner tests prove no rejected facts and real success/history.
Adjacent UI regressions remain selected by the verification planner/CI.
