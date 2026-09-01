# Pilot generation metadata evidence review

Reviewer task: fresh independent evidence review, 2026-09-01.

Reviewed artifact:
`docs/operations/pilot-generation-metadata-evidence.md`.

Authoritative sources checked:

- `rapid-pilot/docker-entrypoint.sh`, `docker-bootstrap.php`, and `start.php`;
- `compose.yaml` and the `up`, `down`, `reset`, and test-environment targets in
  `Makefile`;
- `WorkforceCatalogReconciliationCandidate.php`, its DB verifier,
  object-detail and workforce-reconciliation entry points;
- direct manifest readers/importers/profilers under `rapid-pilot/`;
- `verify-native-only-generation.php`, runtime DDL inventory, operations
  status, product context, and the delivery process.

## Findings

No blocking evidence error was found.

1. **Ownership and lifecycle are accurate.** The entrypoint runs bootstrap
   before the HTTP launcher on each pilot-container start. Bootstrap derives
   the eight-hex repository fingerprint, hard-codes generation `1`, constructs
   the two stated prefixes and artifact path, generates a fresh 32-byte random
   nonce, upserts singleton key `1`, and only after the remaining bootstrap
   work writes `active.json.new` and renames it. The sentinel basename is 29
   bytes, so the evidence correctly keeps it outside the 27-byte full-catalogue
   production-prefix conclusion.

2. **The DB manifest is transcribed faithfully.** The source has exactly the
   four stated unsigned/integer and fixed-character columns, all `NOT NULL`, a
   sole primary BTREE index, no auto-increment, foreign key, check, or secondary
   index, and no DB enforcement of the singleton/format assumptions. The
   recorded MariaDB 11.4.7 observation is consistent with the repository's
   disposable DB reset, which explicitly creates the database as
   `utf8mb4_unicode_ci`; MariaDB resolves an unqualified `utf8mb4` table
   collation independently to `utf8mb4_uca1400_ai_ci`. Planning must preserve
   the distinction between database default and emitted table default rather
   than infer a canonical production collation from this setup observation.

3. **Reset semantics are accurate.** Compose declares separate named DB and
   state volumes. `make down` invokes `docker compose down` without volume
   deletion, while `make reset` invokes `down --volumes`. No repository-owned
   generation increment or selective old-generation garbage collector was
   found.

4. **Consumer strength and gaps are represented accurately.** `start.php`
   consumes only generation and the two prefixes, then derives artifact storage
   from them; it does not read the sentinel or validate manifest fingerprint,
   nonce, state, mode, endpoint, server identity, or the manifest `port`.
   Other readers validate uneven subsets. The reconciliation candidate is the
   strongest existing seam: it validates endpoint shape separately and compares
   generation, fingerprint, nonce, and live `@@hostname`; apply rechecks those
   values under `FOR UPDATE`. Object-detail import uses the same guard. The
   native-only verifier checks mode/composition but not sentinel identity.

5. **The failure and race analysis follows from executable ordering.** The
   nonce/sentinel is replaced before identity rebuild, destructive drops,
   OTIZ/scheduling setup, optional fixture import, and manifest publication.
   Those operations include independently committing DDL. A failure can
   therefore leave old-file/new-DB or partially mutated same-generation state.
   Concurrent invocations share both singleton key `1` and `active.json.new`,
   so serialization and winner consistency are not provided by the current
   implementation.

6. **The proposed boundary is appropriately narrow.** The evidence treats the
   family as disposable local setup metadata, keeps destructive reset outside
   production migrations, does not claim fixture or domain semantics, and
   correctly leaves state-preserving restart dependent on earlier canonical
   schema/bootstrap separation. This agrees with the runtime plan, where
   `separate-pilot-generation-metadata` is ordered after the production schema
   ownership backlog.

## Non-blocking planning cautions

- The manifest's `port` is a literal `8092`, while `start.php` uses
  `FMONITOR_DEMO_PORT` and does not compare it. The future exact manifest
  contract should decide whether port is authoritative identity or remove it;
  merely validating its type would preserve two sources of truth.
- Rename provides same-filesystem atomic visibility but not crash durability:
  there is no file or directory `fsync`. OpenSpec should not translate the
  current publication description into a durable-after-power-loss guarantee.
- The existing workforce-sync worker is an important unguarded state-changing
  consumer: it reads only `processPrefix` and writes catalog/history tables.
  It belongs explicitly in the planned migration to one validation seam.

## Verdict

**READY_FOR_OPENSPEC**

The artifact is sufficiently exact for a planning-only setup-metadata
separation change. The cautions above are requirements-shaping details for that
plan, not corrections to the evidence's current-state claims. They do not
authorize RED, implementation, destructive reset, or production migration of
the sentinel.
