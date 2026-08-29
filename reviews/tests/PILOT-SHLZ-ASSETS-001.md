# Test review: PILOT-SHLZ-ASSETS-001 v0.2

- Reviewer: separately tasked agent `/root/shlz_timing_test_review`
- Test author: separately tasked Gate 2 agent; reviewed corrective commit `2414f54`
- Reviewed commit: `2414f54392226b2d55f84bfa434f2c9160871d8e`
- Specification: `specs/PILOT-SHLZ-ASSETS-001.md` v0.2 at `331b8ac9616b99162fe75b7bc501e1dc223a9d73`
- Public seam: raw HTTP `GET|HEAD /pilot/assets/shlz.css`, browser-relative manifest routes, and configured root HTML
- Red command and intended failure: `php tests/InstallationProcess/pilot_shlz_assets_001_test.php` on production baseline `545fdfa` with the reviewed test tree — deterministic RED, exit `255`; the trusted root-owned fixture expected exact `200` GET/HEAD bytes but the baseline returned redacted `503`.
- Verdict: `APPROVED`

## Findings

- Timing correction: `2414f54` removes only the assertions that mutation must continue after the first response byte. The raw HTTP server invokes the application synchronously: graph capture, whole-graph revalidation, response selection, and response construction precede header/body emission. Mutations after the first byte are therefore outside the specification's per-request capture boundary and cannot be required to affect an already constructed response.
- Preserved overlap sensitivity: the worker still continuously replaces one member identity and changes both a second member's mode and the root entrypoint mode. The test requires at least 20 timestamped mutations after request transmission and no later than the first received byte, proves the worker is alive at that boundary, and requires exact redacted `503`. An implementation that validates before the overlap and then emits an unchecked graph remains caught; only irrelevant post-emission timing was removed.
- Traceability: the test covers the fixed split-export manifest and exact routes, recursive imports and grammar, manifest-only access, GET/HEAD parity, stylesheet order, route/method priority, trusted owner/mode rules, symlink/traversal rejection, graph bounds, in-request drift, and legitimate between-request replacement required by sections 2–5 and 7–9.
- Seam and expected-value independence: all behavior is observed through real raw HTTP. Expected status, headers, bytes, route mapping, graph limits, and mutation outcomes are literals from the approved specification and task-owned fixtures, not production parser output or private Showcase contents.
- Rejected cases and sensitivity: malformed/escaping imports, malformed routes, unsupported methods, unknown members, broken/symlinked graphs, permission/owner mismatch, identity/mode drift, collisions, and size/member/depth excess retain exact fail-closed assertions. The baseline RED occurs at a newly required owner boundary rather than setup failure.
- Determinism and isolation: three consecutive clean-baseline runs failed identically at the root-owner oracle with exit `255`; each run removed its `psa-*` artifact. Fixtures, database, HTTP processes, mutation process, Docker ownership (when used), permissions, and sockets have attempt-all cleanup within task-owned paths.
- Cherry-pick note: `2414f54` is a one-line delta whose parent contains the preceding owner-boundary test. Applying that commit directly to `545fdfa` conflicts because the prerequisite test lines are absent. For the requested clean-baseline RED, the exact reviewed test file at `2414f54` was resolved into the cherry-pick, producing a synthetic one-file commit and no production changes.
- Gate 5 obligation: executable HTTP behavior cannot prove descriptor open count without a forbidden production hook or unavailable platform watcher. The implementation reviewer must inspect that every graph member is opened once, kept through whole-graph revalidation, rewound/re-read on the same descriptor, closed exactly once with attempt-all cleanup, and that the response uses captured bytes.

## Required changes

None.

## Verification evidence

- `git diff --check 545fdfa..ffac424` — PASS.
- `php -l tests/InstallationProcess/pilot_shlz_assets_001_test.php` — PASS.
- `php tests/InstallationProcess/pilot_shlz_assets_001_test.php` — RED in three consecutive runs, each exit `255`: trusted root-owned fixture expected `200`, baseline returned `503`.
- Post-run inspection — no `.test-artifacts/psa-*` fixture remained.

Gate 4 may proceed for exact specification v0.2 and reviewed test commit `2414f54`.
