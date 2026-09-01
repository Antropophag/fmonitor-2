# Pilot generation metadata evidence rereview

Reviewer task: fresh independent evidence rereview, 2026-09-01.

Reviewed artifact:
`docs/operations/pilot-generation-metadata-evidence.md`.

Authoritative sources checked:

- the prior evidence review and the planning review that exposed the second
  lifecycle;
- `rapid-pilot/docker-entrypoint.sh`, `docker-bootstrap.php`, `start.php`,
  workers/import adapters, Compose and Make lifecycle seams;
- the complete `bin/fmonitor2-pilot-demo.php` start/status/reset/cleanup
  lifecycle;
- root and rapid-pilot README runbooks, operations status, runtime DDL plan and
  current OpenSpec planning package.

## Corrected strengths

The correction closes most of the missed-contour gap. It now accurately records
the standalone runner's per-generation `owner.json`/`ready.json`, table-comment
markers, pre-activation HTTP smoke and root `active.json`; it also correctly
separates those from the Compose sentinel/nonce protocol. The Compose DDL,
manifest fields, restart mutation, reset-volume behavior, consumer-validation
gaps, distinct container transport endpoints and fixed-versus-configured HTTP
port analysis remain faithful to the executable sources.

## Blocking findings

1. **The evidence presents the chosen future contour as current repository
   truth.** It says current TEST-USER deployment/runbook intent uses Compose and
   classifies `bin/fmonitor2-pilot-demo.php` as compatibility evidence. The root
   `README.md` currently labels that exact command **Run the production pilot
   locally**, describes its user journey, and calls its `status`, `reset` and
   `cleanup` verbs. `docs/fmonitor-2-session-handoff.md` also names its status
   command. The separate `rapid-pilot/README.md` documents Compose. Thus the
   current repository has two conflicting operator stories; the corrected
   OpenSpec package *proposes* that TEST-USER documentation become Compose-only,
   but an unapproved planning artifact cannot retroactively prove this is the
   current runbook. Evidence must state the ambiguity, then separately state
   that the proposed plan chooses Compose and must update/retire or relabel the
   root standalone runbook before claiming one TEST-USER contour.

2. **The contours are not merely independent protocols; they have a latent
   shared-namespace collision that the evidence does not disclose.** Both
   derive the same repository fingerprint, state-root suffix and
   `fm2d_<fingerprint>_g<n>_` / `fm2l_<fingerprint>_g<n>_` prefixes, and both
   publish the same-shaped root `active.json`. For generation 1 the prefixes are
   identical. Compose normally isolates filesystem state in its named volume,
   but the protocol itself has no contour discriminator. If the same HOME/state
   root and DB are configured or mounted, `rapid-pilot/start.php` accepts the
   standalone active manifest's generation/prefix subset without checking the
   sentinel, while standalone validation interprets the same root manifest via
   its owner/ready files and table comments. The standalone `cleanup` also
   unconditionally unlinks root `active.json` after its ownership-filtered table
   loop. Saying only that neither side consumes the other's private metadata
   leaves the critical aliasing hazard invisible and makes “must not introduce
   an accidental third shared lifecycle” too weak. The evidence and planning
   boundary need an explicit isolation invariant (distinct state root and DB
   namespace/contour discriminator), plus a collision scenario proving one
   contour cannot validate, overwrite or clean the other's metadata/data.

These are evidence-boundary corrections, not authorization to consolidate the
runner, change product fixtures, start RED or implement setup logic.

## Verification

- `git diff --check`: PASS.
- `make architecture-check`: PASS (6 rules).

## Verdict

**CHANGES_REQUESTED**

The second lifecycle is now visible, but the evidence still converts a proposed
Compose-only choice into a current fact and omits the exact namespace aliasing
between the two protocols. Correct both points, then assign a different fresh
reviewer before OpenSpec planning can rely on this evidence.
