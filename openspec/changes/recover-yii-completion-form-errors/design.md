# Design

The controller owns only transport presentation. It carries a bounded
completion-form state into the existing ObjectCardController render seam, which
re-reads current authorization and document facts before rendering. Validation
errors map to named fields. A domain conflict remains form-local only when the
refreshed state still exposes that command; otherwise it becomes an accessible
completion-section alert and the unavailable form is not recreated. Access and
session loss remain separate fail-closed responses rather than same-card field
errors; malformed requests and infrastructure failures also stay fail-closed.

The completion partial accepts one optional state object keyed by the submitted
action. For an available command it escapes retained values, marks the
field/error association, opens the matching correction `details`, and exposes
stable `data-*` hooks. For an unavailable command it renders only the section
alert from refreshed facts. No values are stored in session, URL, another object,
or another form.

A small completion-only ES module intercepts those forms. It guards one in-flight
submit per form and consumes only same-object HTML error responses. Unknown
outcomes leave the original DOM untouched and add a local warning. There is no
automatic retry. Successful redirect handling remains browser navigation.

Database/schema, backup/restore, deployment/readiness, imports and domain replay
are inapplicable because no persistence boundary changes. Existing canonical
fixtures and HTTP owner tests prove no rejected facts and real success/history.
Adjacent UI regressions remain selected by the verification planner/CI.
