## Context

См. `proposal.md` — Why и delta spec `delivery/first-pass-ci-completeness`. Текущие владельцы находятся в `tools/delivery/` и `tools/verification/`: harness строит binding/role packages и сохраняет external evidence, verification tooling владеет registries, категориями и CI composition. PR #98/#100 доказали, что текущий plan описывает команды без полного dependency graph и без идентичности среды исполнения. Полный product matrix локально запрещён owner decision; проверка должна оставаться bounded.

## Goals / Non-Goals

**Goals:**

- Представить verification obligations как типизированный граф: changed surface → contracts/consumers → command → category environment.
- Сделать pre-publication admission воспроизводимым для exact commit tree и независимым от случайно богатой developer environment.
- Разделить executable identity и append-only lifecycle metadata без ослабления exact-source audit.
- Расширить существующие package/evidence schemas совместимо и fail-closed.

**Non-Goals:**

- Изменение product/Yii2/rapid-pilot behavior, deployment или состава #76.
- Локальный запуск полного `make test`/`make verify`, замена GitHub CI или новый Gate.
- Автоматическое обнаружение произвольных зависимостей без registry/policy; неизвестное остаётся `UNKNOWN`.
- Новый orchestration service либо реализация supervisor из #95.

## Decisions

### 1. Один типизированный obligation graph вместо эвристик package builder

`tools/verification/` остаётся владельцем machine-readable связей generated source, consumer groups, command categories и runtime prerequisites. Planner возвращает нормализованные obligations с причиной и provenance; `tools/delivery/` только связывает их с candidate/package. Это исключает копирование repository knowledge в harness.

Альтернатива — добавлять специальные условия в `harness.py` для каждого файла — отвергнута: такой список снова станет неполным и смешает ownership.

### 2. Environment profile является частью command/evidence identity

Каждая plan command получает profile с declared language dependencies, services, browser/container requirements и dependency workspace. Runner записывает наблюдаемое окружение; совместимость проверяется как `actual <= target`, поэтому GREEN из более богатой developer environment не подтверждает узкий CI job. Для команд, которые нельзя безопасно исполнить локально в эквивалентной среде, admission остаётся `UNKNOWN`.

Альтернатива — доверять category label — отвергнута результатом PR #100.

### 3. Preflight композируется из существующих bounded commands

Новый admission не вводит вторую verification систему: он материализует subset generated plan для generator, registry, import/dependency и category checks, запускает их через существующий runner и проверяет coverage. Full product suites не входят в локальный preflight.

Альтернатива — локально повторять GitHub matrix — запрещена owner decision и слишком дорога.

### 4. Evidence имеет typed purpose и два source identifiers

Record сохраняет `purpose` (`acceptance`, `boundary`, `category`), `candidate_source`, `executable_source` и environment identity. Reviewer package принимает record только если его command id принадлежит generated plan и purpose совпадает с obligation. Lifecycle allowlist задаётся явно и проверяется тестом; неизвестный путь считается executable.

Альтернатива — исключить из digest весь `docs/`/`openspec/` — отвергнута: verification inputs и executable catalogs могут находиться там, а широкое исключение ослабляет source binding.

### 5. Test-delta package использует lineage, а не повторный искусственный RED

Gate 3 delta содержит base/current test blobs, acceptance mapping, historical RED record id/source и current GREEN. Проверка требует, чтобы historical record покрывал ту же acceptance и predecessor test lineage. Review остаётся независимым; verdict сохраняется append-only.

Альтернатива — намеренно ломать уже реализованное поведение для нового RED — отвергнута как неистинное evidence.

### 6. Dependency workspace ограничен manifest и realpath

Workspace manifest хранит root kind, resolved realpath, lock/source digest и разрешённые consumers. Harness не хэширует дерево dependency в deliverable, но связывает evidence с manifest identity. Symlink escape, missing lock или mutable/unknown identity блокируют package.

Владельцы: registry/environment policy — `tools/verification/`; package, source lineage и external append-only records — `tools/delivery/`. Разрешённые зависимости — Python standard library и уже существующие Git/container/verification seams. `rapid-pilot/` не меняется. Architecture checks должны подтверждать отсутствие product imports и сохранение public CLI boundaries.

## Risks / Trade-offs

- [Registry может не знать новый тип consumer] → Не использовать generic fallback; возвращать `UNKNOWN` с инструкцией расширить registry и fixture.
- [Environment parity станет платформозависимой] → Сравнивать declarative profiles и выполнять только bounded portable probes; platform-specific отсутствие остаётся fail-closed.
- [Executable allowlist ошибочно исключит значимый файл] → Начать с узкого списка конкретных review/task metadata и mutation tests на каждый класс executable path.
- [Preflight увеличит локальное время] → Дедуплицировать commands по executable/environment identity и не включать full product suites.
- [Schema evolution сломает старые records] → Версионировать schemas; legacy record без новых полей читается как insufficient/`UNKNOWN`, но не переписывается.

## Migration Plan

1. Зафиксировать `DELIVERY-HARNESS-CI-COMPLETENESS-001`, verification input и intended RED fixtures PR #98/#100; получить независимый Gate 3.
2. Ввести schema/graph/environment validation и добиться GREEN focused tests без product code.
3. Добавить preflight admission, typed reviewer evidence, source split, test-delta lineage и dependency workspace по отдельным минимальным шагам.
4. Выполнить bounded harness/verification checks, independent Gate 5 и один exact-source GitHub CI после публикации.
5. Rollback выполняется откатом change commits; external append-only evidence и исторические CI failures сохраняются.
