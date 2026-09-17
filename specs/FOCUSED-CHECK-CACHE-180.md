# FOCUSED-CHECK-CACHE-180 — cache focused-check dependencies on source changes

## Простыми словами

Обычное изменение исходника должно менять проверяемый image/source identity, но
не заставлять Docker заново устанавливать неизменившиеся системные, PHP,
Composer и Python зависимости. Lockfile или runtime pin по-прежнему обязан
инвалидировать свой dependency layer. Этот срез не меняет harness, CI policy,
версии зависимостей или формат `RUN_IN_PROFILE_RESULT`.

## Нормативный контракт

- Actor: developer or existing CI consumer.
- Public seam: `tools/delivery/run-in-profile <profile> <command> [args...]` and
  its existing `tools/delivery/Dockerfile.focused-checks` build.
- Source oracle: GitHub issue #180, owner assignment 2026-09-17.
- Preconditions: Docker/BuildKit available; canonical pins and lockfiles valid.

### FCC180-01 — source-only cache reuse with honest identity

For A→B where only tracked executable source and its honest digest change, the
OS-package/PHP-extension, Composer-install and uv-sync layers MUST be cache hits.
The resulting image label `org.fmonitor.executable-source`, image ID and executed
source MUST identify B. A stale or mismatched A image MUST fail existing checks.

### FCC180-02 — dependency invalidation remains intact

A controlled byte change to a canonical dependency input MUST invalidate its
corresponding dependency installation layer. Runtime/dependency pins and lockfile
membership in image identity MUST remain unchanged; no working-project dependency
upgrade is permitted.

### FCC180-03 — cold/common-profile compatibility

With no matching cache the recipe MUST still build correctly. The shared common
stage MUST remain usable by governance, integration and browser profiles. One
bounded common-stage cold proof plus existing representative profile regressions
is sufficient; three independent cold builds are not required.

### FCC180-04 — bounded regression and evidence

A cheap regression MUST reject stage-local source metadata arguments before any
dependency `RUN`. Bounded real BuildKit evidence MUST cover baseline A→B,
corrected A→B and dependency-input invalidation. Evidence MUST report platform,
Docker/BuildKit versions, exact A/B source identities, image IDs, cache decisions
and wall time around the whole public command. Existing internal
`duration_seconds` and its schema MUST remain unchanged. One machine/sample MUST
NOT support a stable performance percentage claim.

### FCC180-05 — boundaries

The slice MUST NOT change application code, CI composition, FAST/Gates,
dependency versions, artifact identity, stand user/data, harness architecture,
shared cache ownership or unrelated #179 children. It MUST NOT prune shared Docker
caches or delete foreign worktrees/volumes.

## Done

Gate 2 RED, planner-required independent reviews, bounded focused GREEN, strict
OpenSpec validation, one final exact-source CI GREEN and a PR against the main
base are required. Merge/deploy/settings are excluded.
