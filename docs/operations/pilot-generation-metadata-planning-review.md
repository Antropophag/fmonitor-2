# Pilot generation metadata planning review

Reviewer task: fresh independent planning review, 2026-09-01.

Reviewed package:
`openspec/changes/separate-pilot-generation-metadata/`.

Authoritative evidence checked:

- `docs/operations/pilot-generation-metadata-evidence.md` and its independent
  `READY_FOR_OPENSPEC` review;
- `PRODUCT.md`, `CONTEXT.md`, `docs/development-process.md`, the runtime DDL
  migration plan and GRILL-004 inventory;
- `rapid-pilot/docker-entrypoint.sh`, `docker-bootstrap.php`, `start.php`,
  `workforce-worker.sh`, `hourly-bitrix-workforce.php`, Compose and Make seams;
- the independently implemented local runner
  `bin/fmonitor2-pilot-demo.php`, its public README contract and
  `PILOT-DEMO-BOOTSTRAP-001` verifier;
- current OpenSpec status/instructions for proposal, specs, design and tasks.

## Blocking findings

1. **The claimed single owner does not delimit the two live pilot contours.**
   Proposal and design name `make up` / the Docker entrypoint as the target seam
   and require one owner/validated identity for all consumers. The repository's
   documented "production pilot locally" seam is instead
   `bin/fmonitor2-pilot-demo.php`. It owns a different generation lifecycle,
   `owner.json`, `ready.json` and `active.json`, uses DB table comments rather
   than `fm2_pilot_generation_sentinel`, and already has independently tested
   start/reset/cleanup behavior. The package neither includes that contour nor
   explicitly leaves it as a separate fixture harness. Implementing the current
   design would therefore create a third generation owner without making the
   statements "one setup owner" and "all consumers" true. The proposal must
   choose and name the authoritative TEST-USER contour, then either consolidate
   the runner and Compose lifecycle behind the new owner or narrow every
   requirement/impact statement to one contour and give the other an explicit
   retirement or compatibility boundary.

2. **Creation, bootstrap readiness and publication form no coherent state
   machine.** The spec says clean `create` publishes the sentinel plus a ready
   manifest. Design decision 6 says canonical migrations, identity seed,
   fixture/import and product setup run *after create and before ready
   publication*. The three stated CLI operations contain no prepare/finalize
   seam, and the recovery rule only compares staged manifest fields with the DB
   row; it cannot prove that the independently committing prerequisite
   operations completed. Thus a crash can recover and publish `state=ready`
   over incomplete schema/data, or create can publish readiness too early.
   Resolve the exact setup state machine: who allocates the namespace, which
   durable creator-owned marker records prerequisite completion, who is allowed
   to finalize `ready`, what `make up` does on an empty environment versus a
   restart, and how each crash boundary distinguishes recoverable metadata from
   an incomplete bootstrap without destructive repair. Proposal, spec, design
   and tasks must agree on these transitions.

3. **Literal endpoint equality is infeasible for the named consumers.** The
   manifest currently records `127.0.0.1:23306`, which is a socat endpoint local
   to the pilot container. The separate `workforce-sync` container connects to
   `mariadb:3306`; its own `127.0.0.1:23306` has no proxy. Requiring all HTTP,
   worker and import consumers to equal the manifest DB endpoint would reject a
   valid Compose generation, while accepting consumer-specific values as equal
   would weaken the promised exact identity. Likewise the entrypoint proxy and
   Compose healthcheck/published port are fixed at `8092`, while `start.php`
   treats `FMONITOR_DEMO_PORT` as configurable. Decide which stable DB/server
   identity is security-relevant separately from each adapter's transport
   endpoint, and decide one authoritative listener/manifest/configured port
   contract. Add the corresponding Compose-worker and overridden-port
   scenarios/tasks; do not defer this behavioral choice to Gate 1 after the
   current spec has already required impossible literal equality.

4. **The explicit create invocation is missing from the public orchestration
   contract.** Proposal calls `make up` / entrypoint the target seam, while the
   design says entrypoint always invokes validation and creation happens only
   through explicit initialization. On a fresh pair of volumes, `make up`
   therefore has no specified successful path. State whether the operator first
   runs a separately named initialization target, whether `make up` accepts an
   explicit create mode, and how the command proves that both DB and filesystem
   namespaces are empty. The normalized outcomes for absent, active,
   recoverable-incomplete and conflicting environments belong in the delta spec
   and task verification.

## Non-blocking strengths and cautions

- The package correctly keeps the sentinel out of the production migration
  catalogue, preserves `rapid-pilot` as an adapter, orders RED/test review/GREEN/
  regression/code review correctly, and does not authorize implementation.
- The exact four-column storage contract, singleton application checks,
  immutable nonce on restart, scoped staging cleanup and transactional worker
  recheck are consistent with the accepted evidence.
- GRILL-004 is correctly not converted into fixture semantics. Its owner
  decision may gate the exact destructive reset/seed contract, but it need not
  decide the setup identity protocol or the contour/transport issues above.
- The future Gate 1 spec still needs exact stable exit codes/bodies/transcripts,
  lock identity and timeout behavior, filesystem primitive/support matrix, and
  verifier-owned cleanup rules. Those are executable-spec details once the
  planning state machine is coherent.

## Verification

- `openspec validate separate-pilot-generation-metadata --strict`: PASS.
- `git diff --check`: PASS.
- `make architecture-check`: PASS (6 rules).

Strict structural validation does not detect the cross-artifact/runtime
contradictions above.

## Verdict

**CHANGES_REQUESTED**

No RED or implementation is authorized. After the package resolves all four
blocking findings, a different fresh reviewer should independently rereview the
complete planning package and runtime feasibility.
