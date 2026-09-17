# Delivery #180 — focused-check dependency cache

- Assignment: owner request 2026-09-17; only issue #180, PR-ready from main.
- Base: `266e01f78d86493acbd531466afe67aec23f775c`.
- Authorization: normal mode; root authors scope/spec/tests, separate executor
  authors production change, independent reviewers decide required reviews.
- Merge/deploy/settings: not authorized.
- Full local `make test` / `make verify`: prohibited.

Evidence and exact-source state are appended as the delivery advances. Full
BuildKit logs remain outside the checkout under the delivery evidence root.

## Gate and authorship record

- Root authored contract/OpenSpec/test/inventory and captured Gate 2 RED.
- Independent Gate 3: `/root/issue180_gate3`, `gpt-5.6-sol / low`, `APPROVED`,
  no findings; see `reviews/tests/FOCUSED-CHECK-CACHE-180.md`.
- Executor: `/root/issue180_executor`, `gpt-5.6-sol / low`; changed only
  `tools/delivery/Dockerfile.focused-checks` by moving two stage-local arguments.

## Bounded Docker evidence

Environment: Docker Desktop 4.90.0 on Linux/arm64, client/server 29.7.2;
Buildx 0.36.1-desktop.1. Full logs are under
`/Users/antropophag/.local/share/fmonitor-2/issue-180/`.

Baseline `main` A→B used source-only change in disposable
`tools/verification/run.sh`, unchanged lock digest
`929674deb3529f974e719fcd0e37cb2a1a91c81236f16c8e812527587367d5ad`:

- A source `52871ab2…`, image `sha256:5e7d9d6c…`, wall 45.22 s.
- B source `e0f0a9e8…`, image `sha256:28eaf4d5…`, wall 47.14 s.
- B reran apt/PHP extension compilation, Composer install and uv sync. This is
  the observed defect, not an inferred benchmark.

Corrected A→B used the same source-only fixture and unchanged lock digest:

- A source `c6e9f20a…`, image `sha256:e6ce3240…`, wall 45.06 s while populating
  the corrected recipe cache.
- B source `234220e1…`, image `sha256:2c0d7f54…`, wall 3.28 s. BuildKit reported
  `CACHED` for apt/PHP extensions, Composer validate/install and uv sync; only
  candidate copy/final layers ran.
- Public `run-in-profile governance` observed exact source `c6e9f20a…`, image
  `sha256:cc9acb46…`, exit 0; external wall 6.23 s versus unchanged internal
  command duration 0.713245 s.
- Gate 5 correction then ran the public route from a disposable source B whose
  tracked marker was asserted inside the executed container. It reported source
  `eb82f7aa…`, image `sha256:93f87c92…`, exit 0, external wall 10.10 s and
  internal duration 0.533917 s. `docker image inspect` retained exact B source
  and unchanged lock labels. The same fail-closed equality predicate used by the
  launcher rejected existing stale source A (`52871ab2…`) and stale lock fixture
  (`bbd601f3…`) against B expectations; retained output is
  `identity-witness.log`.

Dependency-input fixture added JSON whitespace only to disposable
`composer.lock`: lock label changed to `bbd601f3…`; apt remained cached, while
Composer validate/install reran. Image `sha256:4fc643fb…`; wall 17.37 s. No
project dependency version changed. A separate `--no-cache` build of the same
fixture reran apt/PHP, Composer and uv successfully, image
`sha256:0b37f78d…`, wall 43.58 s.

These are bounded observations on one workstation, not a stable percentage or
multi-run performance claim. Shared caches were not pruned.

Gate 5 correction cycle 1 addressed only missing retained identity evidence; no
production, test or contract byte changed as part of the correction.
