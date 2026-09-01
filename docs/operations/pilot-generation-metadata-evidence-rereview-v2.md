# Pilot generation metadata evidence rereview v2

Reviewer task: fresh independent evidence rereview, 2026-09-01.

Reviewed artifact:
`docs/operations/pilot-generation-metadata-evidence.md`.

Authoritative sources checked:

- both preceding evidence reviews and their findings;
- the complete `bin/fmonitor2-pilot-demo.php` start/status/reset/cleanup
  lifecycle;
- `rapid-pilot/docker-entrypoint.sh`, `docker-bootstrap.php`, `start.php`, the
  manifest consumers and generation guard;
- `Dockerfile`, `compose.yaml`, the Make lifecycle, and both README runbooks;
- the current state-root, manifest, table-comment and prefix derivations.

## Resolved prior findings

The evidence now correctly treats the release contour as an unresolved owner
choice rather than current repository truth. It quotes the real conflict: the
root README presents `bin/fmonitor2-pilot-demo.php` as the production pilot,
while the rapid-pilot README presents the Compose/native-only lifecycle.

It also exposes the protocol-level aliasing risk that the previous version
missed. If the two programs execute with the same repository realpath, HOME and
database, both derive the same generation-1 prefixes and root manifest path;
`start.php` can accept the standalone manifest subset, and standalone cleanup
can unlink the shared root manifest. Requiring an explicit contour discriminator
or retirement before release remains a sound planning requirement.

The remainder is faithful: standalone owns per-generation owner/ready files and
two table comments; Compose owns a singleton sentinel plus a richer root
manifest; bootstrap rotates the nonce and performs independently committing
DDL/destructive work on ordinary restart; reset removes both named volumes; the
HTTP and worker paths reach one logical DB through different transport
endpoints; manifest consumers validate uneven subsets.

## Blocking finding

1. **The corrected evidence turns a conditional alias into the normal current
   topology.** It states that “for the same checkout” both contours derive the
   same fingerprint, state root and prefixes. Both implementations actually
   hash the absolute `realpath` of their own repository root and prepend their
   own `HOME`. Under the documented commands the standalone host runner hashes
   `/home/antropophag/code/fmonitor-2` (`78d99d34` in this checkout) and stores
   state below the host HOME, while the Compose image hashes
   `/workspace/fmonitor-2` (`9c1c9cba`) and stores state in the named volume
   mounted below `/home/fmonitor`. Their normal generation-1 prefixes and state
   roots therefore differ. Sharing the MariaDB database alone does not create
   the stated table collision.

   The aliasing hazard is real only after co-location makes the repository
   realpath, HOME/state storage and logical DB coincide—for example, executing
   the standalone CLI inside the built pilot image at `/workspace/fmonitor-2`
   against its DB—or after a future routing change erases those incidental
   differences. The evidence must distinguish that conditional protocol
   collision from the current documented host-versus-container topology. It
   may still require deliberate, non-incidental contour isolation, but cannot
   use an automatically shared current prefix/state root as its proof.

This is material because the requested discovery explicitly establishes the
collision math and current ownership boundary. The present wording would make
planning and a future executable test assert a collision that the documented
operator paths do not reproduce.

## Verification

- `git diff --check`: PASS.
- `make architecture-check`: PASS (6 rules).

## Verdict

**CHANGES_REQUESTED**

Correct the current-topology statement and give the collision scenario its
exact co-location preconditions. Then assign another fresh reviewer before
OpenSpec planning relies on this evidence. This verdict does not authorize RED,
implementation, runbook selection or changes to either lifecycle.
