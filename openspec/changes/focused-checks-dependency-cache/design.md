## Context

Issue #180 is a verification-environment correction. In the current recipe,
stage-local `COMPOSER_LOCK_SHA256` and `EXECUTABLE_SOURCE` precede every common
dependency `RUN`, although they are consumed only by the final `LABEL`.

## Goals / Non-Goals

Goals: preserve dependency cache on source-only changes; keep exact image/source/
lock identity; prove cold, source-only and lock-change behavior through the
existing recipe.

Non-goals: application code, dependency upgrades, CI/planner/FAST/Gate policy,
new image framework, runner/telemetry redesign, or changes to compact-result
schema and consumers.

## Decisions

1. Declare both metadata-only `ARG` values immediately before `LABEL`. Global
   declarations remain available for build-argument parsing; only stage-local
   use controls cache invalidation in `common`.
2. Keep `COPY` of canonical lockfiles before their dependency installers. A lock
   byte change therefore still invalidates the matching layer.
3. Add one static/order guard to the existing focused profile regression. Real
   A/B evidence uses the actual Dockerfile and controlled disposable contexts;
   it is bounded and not multiplied across all three targets because they share
   `common`.
4. Measure public-route wall time externally. The internal duration continues to
   measure command execution only.

## Risks / Trade-offs

- BuildKit output formats vary: assertions use cache status for named recipe
  steps, not timing thresholds.
- A lock-byte fixture can invalidate more downstream layers than the installer;
  the contract only requires the corresponding layer not to remain cached.
- The first cold build is environment-dependent; no percentage claim is derived
  from one workstation.

## Integration impact

No schema, persistence, authorization, backup/restore, deployment or product
flow changes apply. Governance/integration/browser inherit the same corrected
`common` stage; one common-stage proof plus existing profile regression covers
the frontier.
