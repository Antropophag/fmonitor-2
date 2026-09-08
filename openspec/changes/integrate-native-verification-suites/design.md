## Context

См. proposal. Runner принадлежит tools/verification, вызывается Makefile.
Application/persistence owners и rapid-pilot adapters не меняются.

## Goals / Non-Goals

**Goals:** membership и execution используют общие ordered arrays; PHP и Node
failures агрегируются без раннего прекращения других checks.
**Non-Goals:** новый CI workflow, изменение продуктовых assertions/интерпретаторов,
пропуск медленных тестов, новые grants или изменения protected E2E.

## Decisions

Существующую InstallationProcess heuristic сохраняем для ограниченного diff.
В standalone family явные три pure command tests относятся к unit, default=db
защищает fixture-based native tests, в которых нет literal SQL entrypoint.
Рекурсивное разрешение require-графа не требуется для точного current membership.
List позволяет независимо проверить inventory без production interception.
Trace-only scheduler fixture изолирован от application/native transport;
реальный canonical run обязателен отдельно. Architecture baseline не меняется.

## Risks / Trade-offs

Более длительный full verify → фиксировать все результаты и cost; не менять skips.
Future pure tests попадут в db до явного reviewed mapping → correctness сохраняется.

## Migration Plan

Gate1/RED/Gate3→runner GREEN→реальные native suites→Gate5/source commit→full verify.
Текущий full verify source7cb79d0 должен завершиться до изменения runner/tests,
чтобы не смешать evidence разных sources. Remote запрещён этим session handoff.
