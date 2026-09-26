# LOCAL-DOCKER-STORAGE-BUDGET-001 — bounded local Docker growth

## Простыми словами

Локальная проверка FMonitor 2 не должна снова занять сотни гигабайт. Перед тяжёлой focused-сборкой проект проверяет свободное место, при необходимости чистит только собственные старые образы и cache собственного builder, затем повторяет измерение. Он никогда автоматически не удаляет контейнеры, volumes, сети, данные стенда или ресурсы других проектов.

## Нормативный контракт

- Actor: developer or repository-owned local verification consumer.
- Public seams: `tools/delivery/run-in-profile <governance|integration|browser> <command> [args...]`; storage diagnostic/maintenance helper invoked by that runner; repository-owned disposable Compose runners explicitly inventoried by this slice.
- Source oracle: owner assignment 2026-09-26 and measured diagnosis recorded in `docs/operations/local-docker-storage-budget-delivery.md`.
- Preconditions: Docker/Buildx available for a real run; tests MUST provide deterministic fake `docker`, disk measurement and lock seams.
- Defaults: hard free-space floor 50 GiB; cleanup target 80 GiB; project builder cache maximum 30 GB; minimum unused age 48 hours.

### LDB001-A — stable dependency identity

Focused image identity MUST be derived from profile plus the byte identities of the Dockerfile and every dependency input that can change image contents. Git/source revision MUST NOT participate in that dependency identity. Two source-only revisions MUST address the same focused tag; a dependency-input byte change MUST address a different tag.

### LDB001-B — honest execution provenance

Each completed run MUST report the current Git SHA, executed immutable image ID, profile, command status and duration. The built/loaded image MUST carry `org.fmonitor.owner=focused-checks`, profile and dependency-digest labels. Source provenance MAY be a per-build label only if it does not create a source-derived tag and the executed result remains bound to the current source through the existing bind-mounted workspace.

### LDB001-C — exactly one guarded build

`run-in-profile` MUST reach every focused build through one owning storage-guard seam and MUST issue at most one build for one invocation. Callers MUST NOT independently run maintenance commands.

### LDB001-D — sufficient-space path

When measured free space is at least 50 GiB, the guard MUST emit a machine-readable `DOCKER_STORAGE_GUARD` outcome with `cleanup=skipped`, MUST NOT invoke cleanup, and MUST allow exactly one build.

### LDB001-E — low-space recovery path

When free space is below 50 GiB, the guard MUST acquire the project maintenance lock, remeasure after lock acquisition, and only if still low invoke project-bounded image and builder-cache cleanup. It MUST remeasure afterward. It MAY allow exactly one build only when free space is then at least 50 GiB. The cache command MUST target the exact project builder, retain entries used within 48 hours, cap retained use at 30 GB and target at least 80 GiB host free space where supported.

### LDB001-F — fail-closed path

Missing/non-numeric/negative measurement, unsupported required Docker/Buildx capability, lock failure, cleanup failure, or post-cleanup free space below 50 GiB MUST stop before build with non-zero status and a stable safe reason. No fallback to global maintenance is permitted.

### LDB001-G — bounded image selection

Automatic image cleanup MUST use a positive exact label selector `org.fmonitor.owner=focused-checks` and minimum age 48 hours and MUST rely on Docker's unused-image semantics. It MUST NOT use a negative label selector or select every unused image.

### LDB001-H — forbidden effects and the sole volume exception

The storage guard MUST NOT invoke `docker system prune`, `docker volume prune`, `docker container prune`, `docker network prune`, remove a container/network/volume, or edit Docker Desktop/daemon settings. The sole permitted automatic volume-removal path in this slice is the exact isolated disposable-project teardown defined by K/L: `docker compose --project-name <validated-fm2-disposable-name> --file <exact-file> down --volumes --remove-orphans`. Persistent local-stand resources and foreign project resources MUST remain outside all selection argv.

### LDB001-I — idempotent maintenance

Repeated maintenance with no newly eligible resources MUST succeed as a no-op and retain identical safety selectors and budgets. A failed cleanup MUST remain a failure and MUST NOT be represented as reclaimed space.

### LDB001-J — concurrent maintenance

Maintenance MUST use one project-scoped inter-process lock. A waiting runner MUST remeasure after acquiring it; if the first runner restored the floor, the second MUST skip cleanup. Resources used by an active run MUST NOT become removal candidates.

### LDB001-K — disposable success teardown

Every changed repository-owned disposable Compose caller that creates ephemeral volumes MUST name an exact isolated project and execute `down --volumes --remove-orphans` exactly once after successful completion. The inventory MUST distinguish it from persistent stand callers and current `compose.test.yaml` tmpfs paths that create no volume.

### LDB001-L — disposable failure and signal teardown

After resources exist, ordinary failure and supported INT/TERM handling MUST attempt the same exact-project teardown. The original non-zero operation status MUST be preserved; cleanup failure MUST be separately observable and MUST NOT replace success with a false GREEN. No teardown may derive its project identity from uncontrolled ambient values.

### LDB001-M — safe diagnostics and configuration

The diagnostic seam MUST report machine-readable host free bytes, Docker image/build-cache/volume totals when observable, configured thresholds and whether an action is automatic or manual. It MUST NOT dump the environment, credentials, Docker config contents or secret paths. Overrides used by tests/operators MUST be decimal integers and rejected before Docker effects unless all hold: hard floor 10–200 GiB inclusive; target 10–300 GiB inclusive and not below hard floor; cache maximum 1–100 GB inclusive; retention 1–720 hours inclusive. Defaults remain 50/80 GiB, 30 GB and 48 hours.

### LDB001-N — verification and architecture boundary

A deterministic fake-tools test MUST cover A–M including full argv/order, two source revisions, dependency invalidation, sufficient/low/recovered/unrecovered measurement, capability/lock/cleanup errors, idempotency, concurrency, success/failure/signal teardown, redaction and forbidden effects. Architecture verification MUST reject new direct destructive-prune ownership outside the approved storage seam. Local delivery MUST use only planner-selected focused checks plus architecture check; full local `make test`/`make verify` remains prohibited.

## Done

The complete RED matrix, planner-required independent reviews, focused GREEN, strict OpenSpec validation and one exact-source selected CI GREEN are required. PR-ready does not authorize merge, deploy, host settings edits or historical volume deletion.
