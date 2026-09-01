# Pilot generation metadata separation evidence

This discovery records the current local-pilot generation sentinel, its
filesystem manifest and reset lifecycle. It is setup/harness evidence only. It
does not approve production schema ownership, fixture content, destructive
bootstrap, migration cutover or any product/domain behavior.

## Two distinct current generation contours

The repository has two independent owners that share a state-root naming idea
but not a metadata protocol:

- the deployed Compose contour described below uses
  `rapid-pilot/docker-bootstrap.php`, one DB sentinel and root `active.json`;
- standalone `bin/fmonitor2-pilot-demo.php` is a synthetic fixture harness. It
  allocates increasing generation directories with private `owner.json`, writes
  `ready.json` after provisioning fixed fixtures and canonical v1-v4, identifies
  DB ownership through table comments containing fingerprint/generation/nonce,
  smoke-tests HTTP, then publishes root `active.json`. It exposes
  start/status/reset/cleanup verbs and ownership-checked table cleanup.

The standalone harness does not use `fm2_pilot_generation_sentinel`, and the
Compose contour does not consume its owner/ready manifests or DB markers. A
future separation change must either consolidate them deliberately or delimit
one exact release contour. It must not claim a repository-wide single owner
while leaving both unchanged, and must not introduce an accidental third shared
lifecycle. Repository documentation is contradictory: root `README.md` calls
the standalone CLI “the production pilot locally”, while `rapid-pilot/README.md`
documents Compose/native-only operation. Worktree evidence therefore does not
choose the TEST-USER release contour; owner selection is required.

The default host and Compose topologies are physically different today. The
documented host checkout realpath hashes to `78d99d34`; the image WORKDIR
`/workspace/fmonitor-2` hashes to `9c1c9cba`. They also use different HOME/state
storage, while Compose owns named volumes. Therefore their default state roots
and prefixes do not collide merely because they describe generation `1`.

Protocol aliasing remains possible only when the contours are explicitly
co-located: for example the standalone CLI is run inside the image, or HOME,
repository realpath/state storage and target DB are deliberately shared. Under
all of those prerequisites they derive the same fingerprint, state root and
generation-1 prefixes and publish incompatible root `active.json` shapes.
Compose can then meet standalone-created tables and overwrite its manifest;
standalone can interpret the Compose manifest but find no owner/ready markers.
An approved split MUST either prove topology-level disjointness for every
supported invocation or add an explicit contour discriminator to state roots
and DB prefixes; it must not describe the default host/Compose topology as
already colliding.

## Current ownership boundary

`rapid-pilot/docker-entrypoint.sh` invokes `docker-bootstrap.php` on every pilot
container start before `start.php`. The bootstrap derives an eight-hex
repository-path fingerprint, hard-codes generation `1`, and derives:

- process prefix `fm2d_<fingerprint>_g1_`;
- legacy prefix `fm2l_<fingerprint>_g1_`;
- filesystem state root
  `$HOME/.local/state/fmonitor2/pilot-demo/<fingerprint>`;
- generation artifact root `generations/1/artifacts`;
- a fresh random 64-hex `manifestNonce` on every invocation.

It owns both sides of one setup identity:

1. a DB row in `<processPrefix>fm2_pilot_generation_sentinel`;
2. filesystem `active.json`, published through `active.json.new` plus rename.

The sentinel basename is 29 bytes. It does not change the already discovered
full-catalogue production prefix ceiling of 27 bytes, and it does not justify
placing this setup table in the production migration catalogue.

For the Compose contour, `make down` preserves both named volumes. `make reset` is the explicit
destructive operator seam and executes Compose `down --volumes`, removing the
whole MariaDB and pilot-state volumes. It does not selectively drop a generation
table. No repository command increments the hard-coded generation or garbage
collects an older generation namespace.

## Exact DB manifest

The only source DDL requests InnoDB and `DEFAULT CHARSET=utf8mb4` without an
explicit collation. An isolated populated observation on MariaDB
`11.4.7-MariaDB-ubu2404`, with database default `utf8mb4_unicode_ci`, resolved
the table to `utf8mb4_uca1400_ai_ci`.

Ordered columns, all NOT NULL with no semantic defaults:

1. `singleton_id TINYINT UNSIGNED`;
2. `generation INT UNSIGNED`;
3. `fingerprint CHAR(8)`;
4. `manifest_nonce CHAR(64)`.

The only index is the visible ascending BTREE primary key on `singleton_id`.
There is no AUTO_INCREMENT, CHECK or foreign key. A literal singleton row was
read back unchanged. The private observation table and disposable DB volume
were then removed.

Bootstrap writes key `1` with `INSERT ... ON DUPLICATE KEY UPDATE`, replacing
all three identity values. The schema does not constrain `singleton_id=1`,
positive generation, hex formats or the existence/uniqueness of exactly one
row; these are current application assumptions, not DB constraints.

## Filesystem manifest and consumers

After DB/bootstrap mutations complete, `docker-bootstrap.php` writes JSON with:

- `fingerprint`, `generation`, `processPrefix`, `legacyPrefix`;
- HTTP `port`, `state=ready`, and `mode` (`native-only` or `test-fixtures`);
- `manifestNonce`;
- DB endpoint host/port/name and DB server hostname identity.

The temporary file is written with `LOCK_EX` and renamed, so readers do not see
partial JSON on the same filesystem. There is no file or directory `fsync`, so
the code proves atomic visibility, not crash-durable publication. File/directory
modes are not explicitly hardened: directories use `0755`; the manifest
inherits the process umask and contains no DB password, but the nonce is an
apply guard and should remain private setup metadata.

The manifest always publishes port `8092`, while `start.php` independently uses
`FMONITOR_DEMO_PORT` (default `8092`) for the actual listener and trusted host.
With an override, these two sources can disagree; current startup does not
validate their equality.

`start.php` trusts the filesystem generation/prefixes to choose DB tables and
artifact storage but does not compare the sentinel. Many import/profile scripts
read some manifest fields with uneven validation. The strongest consumer is
`WorkforceCatalogReconciliationCandidate::assertGeneration`: it compares DB
generation/fingerprint/nonce and live `@@hostname` against the manifest, and
rechecks them under `FOR UPDATE` inside its apply transaction. Object-detail
import also uses this guard. In contrast, the optional hourly workforce-sync
worker discovers one manifest and trusts only its process prefix before writing
sync runs, observations, catalogue and metadata; it never validates the
sentinel/server identity. The dedicated native-only verifier validates mode and
data composition but does not read the sentinel.

The workforce reconciliation DB verifier characterizes matching identity,
nonce mismatch, copied-server identity mismatch and transactional zero-write
failure. It creates a simplified sentinel schema rather than proving the exact
setup manifest or publication lifecycle.

## Lifecycle hazards

Current bootstrap is not an atomic or state-preserving generation transition:

- every ordinary container restart generates a new nonce and overwrites the DB
  sentinel even though generation remains `1`;
- before publishing the corresponding manifest it creates/repairs multiple
  schemas, seeds identity, explicitly drops canonical capability data and
  legacy fixture tables, bootstraps OTIZ/scheduling, and may import fixtures;
- MariaDB DDL and those data mutations commit independently;
- failure after sentinel update but before manifest rename leaves an older
  valid JSON manifest paired with a newer DB nonce, so guarded consumers fail
  closed, while unguarded consumers may continue using the old prefixes;
- failure before sentinel update can leave other partial bootstrap mutations
  under the same generation identity;
- a restart is therefore neither a pure repeat nor an explicit reset, despite
  stable prefix/generation values;
- simultaneous bootstrap processes can race on the same singleton row and
  shared `.new` manifest pathname; the final DB row and renamed file are not
  guaranteed to come from the same invocation.

The two explicit `DROP TABLE` statements are not caused by generation change:
they run on every bootstrap. Destructive identity rebuild and other runtime DDL
also occur in the same procedure. Moving the sentinel alone cannot make this
bootstrap state-preserving; those debts remain owned by their existing schema
slices and the explicit reset workflow.

## Separation implications

A planning-only separation change should:

1. classify generation identity as disposable local setup metadata, outside
   production migrations and domain persistence;
2. keep `make reset` as the only documented destructive whole-environment seam,
   never copy its volume deletion or bootstrap drops into a migration;
3. make ordinary restart a read/verify operation that preserves the existing
   DB row, manifest, process data, artifacts and nonce;
4. create a generation only through one explicit setup seam with a unique
   verifier-owned namespace and a single publication protocol;
5. fail closed when DB row, manifest, server identity, prefixes, mode or state
   disagree, and when configured/listener/manifest ports disagree, before
   starting HTTP/workers or importing data;
6. serialize competing creators and prove that readers observe either the old
   complete identity or the new complete identity, never a mixed pair;
7. define recovery for failures before DB identity, between DB identity and
   manifest publication, and after publication without deleting ambient data;
8. validate exact DB schema and exact manifest field/types/formats, including
   one singleton row and path/prefix/fingerprint consistency;
9. use private file permissions, unique temporary publication files and cleanup
   limited to creator-owned incomplete artifacts;
10. migrate manifest consumers, explicitly including state-changing hourly
    workforce sync, toward one validation seam while preserving the
    transactional `FOR UPDATE` recheck for state-changing reconciliation;
11. keep fixture seeding, source import, canonical migrations, identity
    bootstrap, OTIZ/scheduling setup and artifact retention as separately
    ordered operations with their own gates;
12. verify clean create, state-preserving restart, mismatches, copied server,
    concurrent creators, crash boundaries, explicit reset, ambient decoys and
    cleanup on success/failure with normalized transcripts.
13. require an explicit owner choice of release contour, update the conflicting
    runbooks, and prove isolation across every supported topology; if co-location
    remains supported, add a contour discriminator to state roots, manifests and
    DB prefixes before either can operate there.

Compose networking prevents literal transport-endpoint equality from being an
identity invariant: the pilot container reaches the DB through its local socat
proxy (`127.0.0.1:23306`), while the workforce container connects directly to
the Compose service (`mariadb:3306`). Consumers can instead prove the same
logical database name and live server identity after connecting through their
own configured endpoint. Likewise, only HTTP startup needs to reconcile the
listener port; non-HTTP workers do not own or bind it.

## Classification and blockers

Corrected evidence discovery is ready for fresh independent review. The slice
is setup-only, but exact release-contour ownership is `NEEDS_GRILL` because the
two authoritative runbooks disagree. Implementation sequencing also depends on
the release-critical canonical schema/bootstrap separation work, because an
ordinary restart cannot become state-preserving while the same script still
runs destructive rebuild/drop and request-owned DDL.

GRILL-004 still controls whether the first test contour is synthetic/native or
sanitised legacy and its exact reset policy. This evidence does not choose
fixture content. It supports the narrower recommendation that ordinary restart
preserve state and explicit operator reset be destructive.
