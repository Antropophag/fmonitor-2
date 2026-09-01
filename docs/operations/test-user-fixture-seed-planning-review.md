# Fresh independent planning review — TEST-USER fictional fixture seed

- Review date: `2026-09-02`
- Reviewer: fresh independent agent `fixture_seed_planning_review_20260902p`
- Reviewed change: `openspec/changes/seed-test-user-fixtures`
- Reviewed artifacts: proposal, delta specification, design and tasks (4/4)
- Verdict: `CHANGES_REQUESTED`

## Scope and evidence

I reviewed the four planning artifacts against `PRODUCT.md`, `CONTEXT.md`, the
pilot behavior and data-model specifications, `docs/development-process.md`,
the runtime-DDL migration plan, the approved Compose-only release-contour
decision and rereview, and the approved synthetic/native data and reset
decision and rereview. I also cross-checked the existing generation-metadata,
identity/access, workforce and process contracts. This is a planning review;
it approves no executable Gate 1, RED, test, implementation or destructive
operation.

## Findings that are already sound

1. **Release contour and owner decision are preserved.** The change exposes
   only Compose `make up` as the TEST-USER startup seam, keeps ordinary restart
   state-preserving, and leaves standalone CLI as a separate synthetic harness.
   It does not infer production readiness, legacy cutover or a second contour.

2. **The data scope is appropriately fictional and narrow at planning level.**
   The plan requires versioned `.invalid` identities, dedicated ID ranges, a
   minimal role set, two eligible workforce candidates, one dismissed
   rejection candidate and only enough fictional installation inputs for one
   clean journey plus distinguishable rejection states. It explicitly excludes
   real people, production identifiers/documents, external source reads,
   completed inspections, completion, premium decisions and payments. Exact
   literals, row counts, capabilities and independently calculated hashes are
   correctly deferred to the executable Gate 1 rather than invented here.

3. **Migration, integration and domain seams are mostly separated correctly.**
   Canonical migrations remain row-empty schema owners; identity/RBAC,
   workforce and fictional object-source inputs are setup/integration facts;
   any process history must cross approved `InstallationProcess` commands.
   Direct process-table INSERT, runtime/HTTP seed, automatic repair and new
   domain logic in `rapid-pilot` are prohibited. The planned architecture
   ratchets make these boundaries machine-checkable.

4. **Restart, reset and failure behavior follow the approved policy.** Fresh
   creation is seed-once under the generation lock; ready restart is
   validation-only and preserves user-created facts, counters and artifacts;
   drift fails closed without repair. Reset remains an explicit operator seam,
   requires exact environment/resource ownership, preserves foreign decoys on
   ambiguity and recreates a new generation only on a later `make up`.

5. **Concurrency and partial failure are visible.** Preflight precedes writes,
   only one creator owns the generation lock, publication follows full semantic
   validation, and a mid-seed failure remains incomplete for diagnosis or
   explicit reset rather than being silently merged. Tasks require independent
   RED/test review, minimal GREEN, restart/reset/no-source verification,
   architecture/full regression and a fresh code review in the mandated order.

## Blocking findings

1. **Fixture receipt ownership contradicts the generation-metadata capability
   boundary.** The fixture design says the durable seed receipt “belongs to
   generation metadata” and that the ready manifest includes the seed
   fingerprint. The delta specification likewise makes readiness depend on
   that receipt. However:

   - this change declares `Modified Capabilities: Нет`;
   - `separate-pilot-generation-metadata` explicitly says fixture semantics are
     outside that slice and its mode value is not approval of fixture content;
   - the fixture design leaves the exact receipt format symbolic until the
     generation owner lands.

   The plan therefore cannot yet tell whether it consumes a deliberately opaque
   prerequisite-receipt extension point or changes the already planned
   generation manifest/readiness contract. Before Gate 1 drafting, revise all
   four artifacts coherently to choose one exact ownership model. Recommended:
   generation metadata owns only an opaque, versioned prerequisite-receipt
   envelope/registry and atomic publication; the fixture setup owner owns seed
   version, semantic fingerprint and validation. State that the extension point
   must first land in `separate-pilot-generation-metadata`; if no such opaque
   extension point lands, declare and plan the generation capability delta
   explicitly instead of claiming no modified capability.

2. **`app/PilotEnvironment` is assigned incompatible module boundaries.** The
   generation-metadata design defines its `app/PilotEnvironment` owner with
   domain/application seams unavailable. The fixture design assigns the same
   named setup module access to the public `InstallationProcess` seam and narrow
   process persistence adapters. That makes dependency direction ambiguous and
   could turn the generation owner into a mixed environment/domain bootstrap.

   Before Gate 1 drafting, name a distinct fixture-seed application/setup
   component and define its one-way orchestration boundary. Recommended:
   `PilotEnvironment` owns generation lock, validated identity and publication;
   a separate fixture initializer is invoked as a prerequisite and may use
   approved setup ports plus public `InstallationProcess` commands, but never
   private process persistence adapters for domain facts. If “narrow persistence
   adapters” are retained, enumerate that they are limited to non-domain
   identity/workforce/object-source setup facts and cannot bypass application
   commands.

3. **The predecessor catalogue is not exact enough to prevent premature Gate
   1.** Proposal/design/tasks say “necessary/required operational schemas” and
   “generation owner” without naming the exact landed changes and runner order.
   The runtime plan contains both release-critical families and optional/post-
   release families; this wording permits incompatible interpretations of what
   must land before the fixture manifest can be executable.

   Replace the generic predecessor wording consistently with an explicit
   catalogue (change/spec identifiers and required runner versions), including
   at minimum workforce v5, identity/access, every schema actually read or
   mutated by login and the prepare→register→open journey, and the generation
   metadata extension selected in finding 1. Explicitly exclude optional
   premium, migrated-evidence, quarantine and legacy-active cutover families
   unless the literal Gate 1 scenario actually consumes them. Task 1.1 must
   require a fresh catalogue verification after those predecessors land and
   before owner approval.

## Gate 1 details required after correction

These are not additional OpenSpec blockers if the three findings above are
fixed, but the executable specification must make them literal and observable:

- exact fictional rows, IDs, roles/capabilities, object states and minimality
  justification for every row;
- exact canonical manifest serialization and independently derived semantic
  hashes, explicitly excluding salted credential hashes and volatile audit
  timestamps;
- stable redacted setup result/rejection codes for unknown version, foreign,
  partial, conflict, concurrent loser, credential absence and reset ambiguity;
- exact seed transaction-group order, allowed setup tables, public domain
  commands, and the crash point expected state after every group;
- exact receipt/envelope fields, publication ordering, idempotency comparison
  and what “required fixture identities” means without comparing mutable domain
  state to its initial value;
- exact restart preservation inventory for DB facts, AUTO_INCREMENT counters,
  filesystem artifacts, credentials and ambient decoys;
- exact reset resource allow-list/ownership proof and explicit proof that no
  HTTP, worker, migration or production invocation can reach it;
- audit actor/source, timestamp clock, result and redaction rules, including
  failures, without leaking bootstrap credentials or source decoys.

## Verdict

`CHANGES_REQUESTED`

The intended slice is aligned with the product and owner decisions, and its
seed-once/restart/reset behavior is a viable route to a fictional TEST-USER
contour. It is not yet safe to route to Gate 1 because receipt ownership,
module dependency direction and the exact predecessor frontier remain
contradictory or underspecified across authoritative planning. After correcting
all four artifacts, assign a fresh independent planning rereviewer. RED and
implementation remain prohibited until an exact executable
`TEST-USER-FIXTURE-SEED-001` is separately owner-approved and Gate 2/3 complete.
