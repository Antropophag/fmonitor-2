# Recover Yii completion forms after rejected saves

## Why

Existing completion POST failures discard the object-card context and render a
plain-text page. Before pilot operation, users must be able to correct the same
PTO/declaration form without relying on browser Back.

## What changes

- Render field and form-level completion errors inside the same object card,
  retaining only the submitted form values.
- Open and focus the rejected correction form.
- Add a completion-only progressive-enhancement asset for duplicate-submit and
  unconfirmed-result handling.
- Keep no-JS POST, status semantics and domain ownership intact.

## What does not change

Documentary closure commands, persistence, ordering, progress, history, schema,
global navigation/ViewSupport/styles, feedback/build identity (#172), object
details, status SQL (#236), checklist/photo/offline, construction control, OTIZ
and readiness are out of scope.
