# Manual-pilot completion seam ownership review — 2026-09-07

- Verdict: **APPROVED**
- Scope: focused independent ownership review for the owner-authorized manual pilot.
- Reviewer: `/root/manual_application`; did not author the reviewed completion source or wiring.
- Registration approved: only
  `app/InstallationProcess/MariaDbInstallationCompletion.php::record` in the
  architecture public-seam baseline.
- This is not Gate 5, production-readiness, full verification, or approval to
  regenerate unrelated architecture debt.

## Evidence reviewed

Reviewed source/test SHA-256:

```text
3604b70b3eb205a71a1370b614dba318eea8720bcceae6449eb279f0efc12ebc  app/InstallationProcess/MariaDbInstallationCompletion.php
238d01da3287c4dbaecab39a371c9e1656b6ca00a05095c666a8be1b2c3d827b  rapid-pilot/CompletionFlow.php
1bc246a8d483f364c53b484f2bea211b85fe39a0205846272e0e640972692732  tests/InstallationProcess/installation_completion_manual_pilot_test.php
```

- `docs/operations/current-delivery-goal.md` requires a usable manual pilot while
  retaining one explicit application owner, server-side roles, and append-only history.
- `docs/operations/installation-completion-owner-decision.md` and
  `docs/operations/completion-otiz-owner-decisions-2026-09-02.md` require separate
  PTO/declaration capabilities, mandatory declaration, 85% checklist prerequisite,
  and append-only corrections with a reason.
- `app/InstallationProcess/MariaDbInstallationCompletion.php` owns the transaction,
  authorization, working-case lock, checklist threshold, PTO-before-declaration rule,
  immutable root insert, correction chain insert, and effective read projection.
- `rapid-pilot/CompletionFlow.php` parses HTTP input and calls the owner’s `record()`
  or `correct()` methods. It does not insert, update, or delete completion facts or
  corrections. Its completion SQL outside that call is read-only presentation and
  queue projection.
- Repository search found the only production inserts into
  `fm2_pilot_completion_facts` and `fm2_pilot_completion_fact_corrections` in
  `MariaDbInstallationCompletion`. Direct inserts elsewhere are bounded verification
  fixtures or migration/import tooling, not an alternative HTTP business writer.
- `php tests/InstallationProcess/installation_completion_manual_pilot_test.php`
  passed. It exercises separate permissions, PTO then declaration, immutable roots,
  two sequential corrections, latest effective projection, retained history, and a
  mandatory correction reason.

## Ownership assessment

One application owner governs the four mutating pilot operations. `record()` selects
the exact PTO or declaration permission and appends the root fact. `correct()` selects
the corresponding correction permission and appends a predecessor-linked version;
it never updates or deletes the root. Both methods use the same case lock, transaction,
completion storage, validation policy, and read projection. They therefore form one
cohesive completion application boundary rather than competing writers.

The architecture checker detects `record` as the new command-like public seam. Adding
the exact entry `app/InstallationProcess/MariaDbInstallationCompletion.php::record`
records its deliberate ownership. The approval does not cover another seam, SQL/DDL
finding, hotspot, or wholesale baseline rewrite. The companion `correct()` operation
already belongs to the same reviewed owner and must not be moved into HTTP or registered
as unrelated debt merely to silence a future detector change.

## Focused limitations

The adapter exposes concrete methods rather than a finalized application interface,
and the manual test is not a complete concurrency or failure matrix. Those are deferred
production-integration concerns under the current delivery goal. They do not create a
second writer or invalidate the narrow manual-pilot ownership conclusion.

**Focused verdict:** APPROVED to register only
`app/InstallationProcess/MariaDbInstallationCompletion.php::record`. No other baseline
change is approved by this review.
