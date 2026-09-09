# Root pilot startup regression — 2026-09-09

Corporate stand source: `41e39498`. After `make down`, fast-forward main and
`make up`, pilot/MariaDB became healthy but workforce-sync restarted with
`{"ok":false,"error":"CONFIGURATION_INVALID"}`.

## Cause and verification gap

PR64 changed `rapid-pilot/workforce-worker.sh` into an explicit-prefix `--once`
adapter (implementation commit `116f65c4`). Root `compose.yaml` and its generator
template still invoked it without arguments, omitted the prefix, and waited for
the removed `/tmp/workforce-ready` marker. This is a deterministic caller/callee
contract mismatch, before any corporate Bitrix request.

`legacy_workforce_once_delegation_001_test.php` positively asserts that no-argument
invocation exits64. The existing worker CLI test was changed to supply `--once`
and an explicit prefix. The runtime-wiring review approved that new contract and
`deploy/runtime/compose.yaml`, without including the remaining root caller.
The retained integration report records successful isolated new-runtime Compose
smoke and full CI `34315902017` on `088b51be`. Those checks did not establish
that root `make up` remained functional. This is missing deployment-path coverage
and incomplete migration/review scope, not evidence of a corporate configuration
mistake or a falsely reported test result.

## Correction contract

See `specs/PILOT-JOBS-STARTUP-001.md`. Root deployment must use the native Jobs
worker/scheduler and health operations, with pilot manifest/private configuration
translation confined to its adapter. A root-caller regression and isolated real
Compose exercise are required. Do not treat health as proof of an external sync.

## Evidence status

Initial local reproduction: `git show origin/main:rapid-pilot/workforce-worker.sh
| sh` returned64 and `CONFIGURATION_INVALID`, matching the corporate log.
The correction adds pilot-only configuration translation and starts the native
worker and scheduler together. The new resolved-root-caller test is GREEN after
the recorded RED. Existing private Bitrix startup, historical once delegation,
workforce CLI and native Jobs runtime contract checks pass. `make architecture-check`
passed the HTTP qualification and all7 architecture rules. Independent bounded
source review is APPROVED in `reviews/code/PILOT-JOBS-STARTUP-001.md`.

Initial real root-image/root-Compose smoke passed local HTTPS delivery of51
synthetic workforce entries, native job completion, health and restart preservation.
Review then strengthened the smoke to wait for DB commit rather than transport
completion; the final exact test rerun also passed via
`python3 tests/Deployment/pilot_jobs_compose_001_test.py`. The smoke is registered in the E2E
inventory so CI exercises the root deployment as well as the separate runtime.
Full CI and installation on the corporate stand remain pending. No retained stand
or its volumes were changed; all smoke resources belong to isolated test projects.
