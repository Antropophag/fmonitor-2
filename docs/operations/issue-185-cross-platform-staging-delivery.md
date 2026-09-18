# Delivery record — #185 cross-platform local integration staging

## Scope and authorship

- Base: `95e070893082422b067786abe6b6e5ff4ea3aa65` (`origin/main` after merge #189).
- Owner authorization: second bounded #185 slice through one PR to PR-ready; planning and apply were explicitly authorized together.
- Root `/root` authored scope, normative/OpenSpec artifacts and tests.
- Executor `/root/executor` (`gpt-5.6-sol/low`) authored production implementation.
- Independent reviewer `/root/gate3_review` (`gpt-5.6-sol/low`) approved Gate 3 after two correction rounds; it authored no spec, test or implementation.

## Implemented path

`.env` is parsed and atomically staged on the host without exact mode predicates. Each Make target bind-mounts only that checked host snapshot at `/run/fmonitor-input`; a root-only container wrapper atomically copies it to the named secrets volume, assigns `10001:10001` and `0600`, then executes the canonical Yii command through `setpriv` as UID/GID 10001. Cleanup removes the delivered file after success or failure and returns the importer status. The optional test/local Bitrix CA follows the same copy boundary.

Legacy and workforce PHP loaders require an absolute readable regular non-symlink file and retain their format validation; unrelated production/session file policies are unchanged.

## Focused evidence

- GREEN: portable host modes, unsafe path rejection, atomic concurrent publication and cleanup.
- GREEN: runtime-image host UID mismatch with `0600`, real UID 10001 reads for both formats, updated replay, exit `23` preservation, empty secrets volume and image/log redaction.
- GREEN: actual `make import-legacy` and `make sync-workforce` through Compose against isolated MariaDB and task-owned verified-TLS Bitrix endpoint; persisted facts and changed-token replay observed.
- GREEN: retained legacy/workforce DB owners, verification planner/governance, architecture guard, generated Dockerfile parity and CI consumer oracle.
- One same-source diagnostic invocation overlapped the already-running E2E and failed only because its task-owned `.env` existed; the original invocation completed GREEN. No product correction or CI retry resulted.
- Native Linux-container behavior on Docker Desktop for macOS was exercised. A separate Windows/WSL environment was unavailable, so WSL remains `UNKNOWN`, not GREEN.
- Full local `make test`/`make verify` was not run by owner rule. Exact-source CI remains required once on the committed candidate.

## Remaining parent scope

This PR does not close #185. VPN routing, import filters, engineers, chunking, #182, further owner provisioning, universal secret management, production runtime/session policy and any other parent slices remain outside this delivery.

## PR #190 interruption correction

Owner correction on 2026-09-18 requires wrapper signal supervision and moves one-shot copies from the persistent secrets volume to container tmpfs. Current-head CI run `35296146049` for `07cfbc4bae79f5fb8d774f4c25c4b38c83be6629` completed failure: `unit` had one `REGRESSION_FAILURE` in the stale exact-mode expectation `tests/Deployment/yii2_local_data_bootstrap_001_test.py`; `e2e` had one `REGRESSION_FAILURE` in unrelated `tests/Runtime/production_runtime_compose_001_test.php` (DB restart readiness expected 200, observed 503); `verify` failed only because those categories failed. Plan, fast, both integration shards and governance were GREEN. Both failures were inspected before correction; the unrelated e2e failure remains recorded and is not treated as GREEN.

Correction implementation uses a dedicated `local-integration` Compose service with container-only tmpfs and `SIGTERM`, leaving the production `php` service on `SIGQUIT`. Wrapper PID 1 launches the UID 10001 importer as a child, forwards TERM/INT/QUIT, waits for the child, cleans the delivered files and preserves a nonzero interruption result. Focused Docker evidence covers all three graceful signals plus SIGKILL confinement and retains success, exit 23 and replay.

Corrected-head CI run `35299888379` for `41cd3014573c4a9fda5e756439164e1baa6b99be` completed failure with exactly one `REGRESSION_FAILURE`: the existing exact Compose-topology test did not yet list the new bounded `local-integration` service. The runtime reconnect test that failed on the prior head was GREEN in this run; unit, both integration shards, fast and governance were GREEN. `verify` failed only because e2e contained that stale topology expectation. The topology oracle is updated in the correction rather than removing or hiding the service.

Inherited-signal correction after GREEN head `c1d99be631ad0b7d4302902bd22864381fba7be3`: child launch uses GNU `env --default-signal=INT,QUIT` (verified inside the real runtime image), and wrapper retains the first accepted interruption status (`143`, `130`, `131`) after reaping. Thus handler-free PHP does not inherit ignored INT/QUIT and a child cleanup `exit(0)` cannot turn wrapper interruption into success. Importers, tmpfs service and PHP-FPM stop policy are unchanged.
