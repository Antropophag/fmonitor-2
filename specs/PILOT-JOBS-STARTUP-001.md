# PILOT-JOBS-STARTUP-001

## Простыми словами

После обновления `make up` должен запускать кадровую синхронизацию действующего
пилота. Перенос планирования в Jobs не должен ломать корневой Compose.

## Public seam and contract

Actor: deployment operator invoking root `make up` with prepared private Bitrix
configuration and an existing or freshly provisioned pilot state volume.

1. Root Compose starts both a workforce worker and scheduler, using the existing
   Jobs runtime. `make up` waits for both together. No second scheduling loop or
   business writer is introduced in the pilot adapter.
2. The adapter reads exactly one ready pilot manifest from the mounted state root
   and passes its validated nonempty process prefix to Jobs. It does not create,
   rewrite, migrate or delete pilot state. Missing, ambiguous, malformed or non-ready
   manifests fail with a bounded configuration error before starting Jobs.
3. Worker translates the existing private Bitrix configuration to the native
   transport configuration; token bytes stay in a private file and never enter
   output. Scheduler and health need no Bitrix secret. Normal shutdown cleans up
   the worker's staged token.
4. Health uses the native Jobs health operation for the same instance and prefix;
   it cannot be satisfied by a stale `/tmp/workforce-ready` file. Health reports
   process/queue readiness, not proof of a successful external Bitrix delivery.
5. SIGTERM reaches the existing Jobs process owner; Compose grants its existing
   60-second shutdown grace. Restart preserves existing pilot and Jobs facts.
6. Historical explicit-prefix `workforce-worker.sh --once` retains its current
   contract. Production runtime configuration remains explicit and unchanged.

## Verification

Resolve the actual root Compose caller and exercise its entrypoint and health
commands, including rejected manifests. An isolated Compose smoke must start both
services with a real migrated database, verify health and restart, and exercise
native workforce delivery against a local HTTPS fixture. Existing one-shot and
Jobs boundary regressions remain required. Full CI is a separate delivery gate.
