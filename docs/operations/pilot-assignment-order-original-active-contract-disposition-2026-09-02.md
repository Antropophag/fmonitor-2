# Active contract disposition — assignment-order original, 2026-09-02

Owner source: `pilot-assignment-order-original-owner-decision-2026-09-02.md`.
Target command spec: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001`.

## Amended target truth

- `PRODUCT.md`, `CONTEXT.md`, `docs/fmonitor-2-pilot-spec.md` and
  `docs/fmonitor-2-pilot-data-model.md` define signed PDF original, separate
  document/upload dates, no manual number and separate opening.
- OpenSpec `replace-pilot-registration-with-original-upload` owns only private
  original command/persistence.

## Explicit legacy predecessor

- `docs/installation-process-interface.md`;
- `docs/operations/pilot-behavior-inventory.md` registration/opening branches;
- `specs/REGISTRATION-CONFIRM-001.md`;
- `specs/PERSISTENCE-REGISTRATION-001.md`;
- `specs/OPEN-INSTALLATION-001.md`;
- `specs/PERSISTENCE-OPEN-001.md`;
- the registration branch of `specs/PROCESS-COMMAND-AUTHORIZATION-001.md`.

These files retain evidence of implemented behavior. Their notices prevent use
as target acceptance contracts. Existing production/tests remain unchanged.

## Blocked active E2E/UI contract

- `specs/PILOT-E2E-FLOW-001.md` is legacy characterization until command,
  `expose-assignment-order-original-http`, composition and opening slices land.
- `openspec/changes/pilot-e2e-combined-pdf/.../spec.md` retains generated PDF
  assertions, but prepared/registered card language is predecessor-only and
  requires fresh Gate 1 amendment in the HTTP slice.
- Active RBAC fixture changes that exercise registration/opening retain their
  approved historical assertions; they cannot claim target original workflow
  GREEN and require fresh approval if their contract changes.

## Immutable downstream characterization

Inspection/checklist/completion specs and tests using `registered crew` describe
the crew fixture/source observed by already implemented behavior. They are not
edited or reinterpreted as a target registration-number requirement. Future
`apply-assignment-order-original-to-composition` must provide a compatible crew
query before those characterizations are migrated.

## Future owners

- `expose-assignment-order-original-http`: HTTP upload/read/download and local
  RBAC;
- `apply-assignment-order-original-to-composition`: effective sequential crew;
- `open-installation-from-assignment-order-original`: target opening gate.

No historical review/evidence record is changed. No existing test is weakened
or counted as approval of the new command.
