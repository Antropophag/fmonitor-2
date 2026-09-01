# Pilot generation metadata evidence rereview v3

Reviewer task: fresh independent evidence rereview, 2026-09-01.

Reviewed artifact:
`docs/operations/pilot-generation-metadata-evidence.md`.

Authoritative sources checked:

- all three preceding evidence reviews and their findings;
- `bin/fmonitor2-pilot-demo.php` across start, status, reset and cleanup;
- `rapid-pilot/docker-entrypoint.sh`, `docker-bootstrap.php`, `start.php`, the
  hourly workforce worker and guarded import/reconciliation consumers;
- `Dockerfile`, `compose.yaml`, the Make lifecycle, both README runbooks and the
  session handoff;
- the exact fingerprint, state-root, prefix, manifest and network-endpoint
  derivations.

## Resolved v2 finding

The corrected evidence now separates the documented default topology from the
conditional protocol collision precisely.

- The host checkout realpath is `/home/antropophag/code/fmonitor-2`; applying
  the executable `substr(hash('sha256', $repo), 0, 8)` derivation produces
  `78d99d34`.
- The image copies and executes the repository at `/workspace/fmonitor-2`;
  applying the same derivation produces `9c1c9cba`.
- The host runner prepends the host `HOME`. The pilot image exports
  `HOME=/home/fmonitor`, and Compose mounts the private `pilot-state` volume at
  `/home/fmonitor/.local/state/fmonitor2`; its MariaDB data is in the separate
  `mariadb-data` volume.

Consequently, the documented host and Compose invocations do not share a
fingerprint, generation-1 prefixes or state root merely because their numeric
generation is `1`. The evidence no longer claims otherwise.

It also retains the real latent collision without overstating it. If repository
realpath, HOME/state storage and the logical target DB are deliberately made the
same—for example by running the standalone CLI inside the pilot image through
its local DB proxy—both protocols derive the same root and prefixes. Their
overlapping root manifests are then not sufficient isolation: Compose startup
accepts the standalone manifest subset without checking its sentinel, while the
standalone lifecycle requires its private owner/ready files and table-comment
markers and cleanup can unlink the shared root manifest. Requiring either
topology-level proof of disjointness or an explicit contour discriminator is
therefore supported by the code rather than by the non-colliding defaults.

## Other evidence checks

1. **Runbook ownership remains explicitly unresolved.** Root `README.md` calls
   `php bin/fmonitor2-pilot-demo.php` the production pilot and documents its
   `status`, `reset` and `cleanup` verbs; `rapid-pilot/README.md` documents the
   Compose `make up/down/reset` contour. The evidence records that contradiction
   and requires an owner choice plus corresponding runbook correction. It does
   not claim that an unapproved OpenSpec package has already selected the
   release contour.

2. **The lifecycle distinction is faithful.** Standalone generations own
   private `owner.json` and `ready.json`, two table-comment ownership markers,
   pre-activation HTTP smoke and a root active manifest. Compose hard-codes
   generation `1`, rotates a 64-hex nonce on every bootstrap, upserts the DB
   sentinel before further independently committing schema/data mutations and
   publishes its richer manifest last. `make down` preserves both volumes and
   `make reset` removes both. The evidence correctly avoids treating rename as
   filesystem crash durability.

3. **Consumer and concurrency gaps are accurate.** `start.php` trusts only the
   generation/prefix subset and derives its artifact root; the hourly worker
   trusts only `processPrefix`. The reconciliation guard checks generation,
   fingerprint, nonce and live server hostname and repeats that check under row
   lock for apply. Concurrent Compose bootstraps share singleton key `1` and
   `active.json.new`, so no invocation-consistent winner is guaranteed.

4. **Networking is not collapsed into literal endpoint equality.** The pilot
   reaches MariaDB through `127.0.0.1:23306` and its local socat proxy, whereas
   the workforce service uses `mariadb:3306`. The proposed invariant correctly
   concerns the logical database and live server identity after each consumer
   connects. Only the HTTP process owns listener/manifest port reconciliation;
   the non-HTTP worker does not bind that port.

## Non-blocking planning caution

The separation implications name Compose's `make reset` as the proposed sole
destructive whole-environment seam. Because release-contour selection is still
`NEEDS_GRILL`, OpenSpec must present that as a conditional/recommended Compose
outcome, not as an already approved repository-wide lifecycle. If the owner
selects the standalone contour instead, its materially different `reset` and
`cleanup` meanings must be specified rather than silently renamed to the Make
contract.

## Verification

- `git diff --check`: PASS.
- `make architecture-check`: PASS (6 rules).

## Verdict

**READY_FOR_OPENSPEC**

The v2 collision-math defect is corrected, both current lifecycles and the
runbook ambiguity are visible, and the networking invariant respects the real
per-container endpoints. This evidence is sufficient for a planning-only
OpenSpec package that keeps release-contour selection explicit. The verdict
does not approve either contour, RED, implementation, destructive reset,
fixture semantics or product behavior.
