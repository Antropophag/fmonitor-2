## Context

См. `proposal.md`. На `origin/main` Quality Graph уже владеет planner, четырьмя
category jobs и fail-closed aggregation; `.github/actions/setup-runtime` ставит
host runtimes. PR #117 полезен как reference для состава зависимостей и
разделения profiles, но его 487-строчный execution runtime смешивает запуск с
snapshot/provenance/fixture policy.

## Goals / Non-Goals

**Goals:**

- один прозрачный launcher поверх обычного container runtime;
- один recipe с тремя явными targets/profiles и pinned image references;
- существующая test command остаётся владельцем selection semantics;
- implementation + infrastructure LOC удерживаются существенно ниже 500.

**Non-Goals:**

- изменение `harness.py`, test inventory, planner или aggregation;
- snapshot/candidate identity, lifecycle/provenance/fixture models;
- orchestration resources шире минимально нужных DB/browser dependencies;
- удаление `setup-runtime` в PR A или до GREEN PR B.

## Decisions

1. Launcher будет небольшим shell/Python entry point, который валидирует только
   profile, монтирует checkout, запускает переданный argv и возвращает exit code.
   Альтернатива из #117 с регистрацией/классификацией команд отвергнута: она
   дублирует Quality Graph и меняет ответственность selection.
2. Три profiles собираются из одного container recipe с общим базовым слоем и
   явными profile targets. Все внешние base/service images закрепляются digest;
   lockfiles остаются источником версий package dependencies.
3. Integration/browser service setup переиспользует существующий
   `compose.test.yaml`/Make targets либо минимальный profile-specific compose
   overlay. Launcher не становится владельцем БД lifecycle, если существующая
   команда уже им владеет.
4. PR A расширяет уже выполняемый существующим Quality Graph container setup
   contract test, поэтому workflow и graph manifests не меняются. PR B меняет
   только run lines четырёх jobs и
   декларативный Quality Graph manifest; planner и aggregation остаются byte-for-byte
   неизменными, кроме неизбежного regenerated digest/manifest.
5. Root владеет contract и Gate 2 tests; отдельный executor реализует только
   после Gate 3 APPROVED; независимый reviewer решает Gate 5.

## Risks / Trade-offs

- [Multi-architecture image digests различаются] → закрепить manifest-list digest
  и проверять фактически разрешённый digest на поддерживаемой CI/local platform.
- [DB/browser profiles могут потребовать service networking] → сначала доказать
  существующие команды без новой orchestration abstraction; при конфликте
  остановиться и предложить минимальный compose adjustment.
- [Внутренний harness guard требует запрещённую метамодель] → не расширять scope;
  зафиксировать конфликт и предложить узкое изменение guard владельцу.
- [Оценка превысит 500–800 LOC] → STOP до реализации и перепроектирование с
  владельцем.

## Migration Plan

1. PR A: launcher, pinned profiles и parity/focused tests внутри уже выбранного
   test corpus; Quality Graph файлы и `setup-runtime` остаются без изменений.
2. После merge и GREEN PR A создать PR B от нового `main`; заменить только
   команды category jobs на launcher и проверить неизменность plan/aggregate.
3. После подтверждённого GREEN новой схемы отдельно удалить `setup-runtime`;
   rollback PR B возвращает прежние run lines без отката PR A.
