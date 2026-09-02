# OTIZ snapshot acceptance — independent Gate 1 review

- Reviewer: Codex subagent `/root/otiz_gate1_review_0901c`
- Date: 2026-09-01
- Independence: fresh read-only reviewer; did not author or edit the reviewed
  executable specification, OpenSpec artifacts or production source
- Reviewed specification:
  `specs/CHARACTERIZE-OTIZ-SNAPSHOT-ACCEPTANCE-001.md` v0.1
- Scope: project constitution and product context, delivery process, pilot
  contracts, behavior-evidence note, complete OpenSpec planning package, and
  the current `rapid-pilot/router.php`, `LocalAuth.php` and `Otiz.php` source
  anchors

## Findings

1. **Gate-blocking — the literal child fixture is not exact or internally
   coherent.** The object table has ten monetary/basis-point columns after the
   two progress columns (`premium_cents` through `undistributed_cents`), while
   the spec assigns the inclusive range `101..111`, which has eleven values,
   “in schema order”. The evidence row also leaves `source_label` and
   `source_locator` as unspecified “literal labels/locators”. Consequently two
   independent RED authors can seed different fixtures while claiming
   conformance, and the promised full typed fingerprints do not have one
   independently determined expected value. Enumerate every object column and
   value and give exact evidence label/locator values (and exact null/value
   defaults for each issue variant).

2. **Gate-blocking — the real LocalAuth fixture is incomplete.** The public seam
   requires login through `/pilot/login`, but the spec names actors without
   fixing the prerequisite auth/RBAC rows, password credential/password, login
   exchange stages and exact method/form members used to obtain each cookie and
   server-issued CSRF. This is material because `LocalAuth` performs a CSRF
   checked staged email/password flow and reads several prefixed support tables
   before OTIZ admission. Define the minimal literal prerequisite catalogue and
   exact login exchanges, including how `8802` is demonstrably denied and how
   independent `8801`/`8803` sessions are established, without borrowing a
   production bootstrap helper as the expectation oracle.

3. **Gate-blocking — true concurrent transaction overlap is asserted but not
   observably required.** A parent start barrier, two HTTP connections and two
   previously observed server PIDs prove multi-worker capacity, but do not prove
   that both acceptance requests overlap while the winner holds the snapshot
   row lock; one request may finish before the other reaches the route. Require
   an implementation-independent observation/barrier that demonstrates both
   contenders are simultaneously in flight and that the follower is blocked on
   the acceptance serialization boundary before the winner is released. Also
   specify how the parent-known request nonce is correlated with method, exact
   path and actual serving PID: none of the reviewed production source emits
   that tuple, and the acceptance form is required to contain only `csrfToken`.
   Instrumentation must remain verification-only and must not weaken the real
   router/LocalAuth seam.

4. **Gate-blocking — intentional failure cleanup does not cover failure during
   cleanup.** The spec says cleanup faults are separately reported and cannot
   replace the primary `SETUP_FAILURE` or `REGRESSION`, but the two required
   probes only induce setup and assertion failures. Add an observable cleanup-
   fault probe (or an equivalent deterministic contract) that proves the
   primary classification is retained, remaining owned resources are still
   best-effort cleaned/reaped, and ambient decoys survive. Without this, the
   classification rule cannot drive a sensitive RED.

The remaining contract is well bounded and source-aligned. It is explicitly
`PILOT_ONLY`; preserves GRILL-001 for target authority, financial evidence,
separation of duties and payment consequences; records the actual
auth → `otiz.manage` → constructor DDL → CSRF → `FOR UPDATE` order; names all
twelve constructor-owned tables and the conditional `unique_reversal` branch;
fixes exact acceptance/rejection HTTP outcomes; requires only the three accepted
snapshot fields plus one append-only event; preserves child rows; treats live
Moscow timestamps independently; covers missing/immutable/blocker/warning/
resolved/replay cases; uses winner-neutral outcomes; isolates port/cookie/
session/SQL/process ownership with GC disabled and ambient decoys; and keeps RED
and implementation outside the current planning/Gate 1 work.

## Verification evidence

- `openspec status --change characterize-otiz-snapshot-acceptance --json` — all
  four planning artifacts reported `done`.
- `openspec validate characterize-otiz-snapshot-acceptance --strict` — passed.
- `git diff --check` — run after writing this review.
- `make architecture-check` — run after writing this review.

## Verdict

CHANGES_REQUESTED
