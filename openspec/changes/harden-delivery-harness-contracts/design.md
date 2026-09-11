## Context

См. `proposal.md` — Why. Владельцы orchestration уже разделены между `tools/delivery/harness.py`, `tools/delivery/harness_context.py`, `tools/delivery/change-verification.py` и canonical verification runner. Ошибки PR #89/#91 возникли на связях между этими публичными seams и дублируемыми registries. Evidence остаётся append-only вне checkout; тесты обязаны быть bounded и не обращаться к production systems.

## Goals / Non-Goals

**Goals:**

- Сделать полный process/JSON/artifact contract одного запуска наблюдаемым одной table-driven матрицей.
- Проверить межseamные цепочки настоящими CLI и настоящими возвращёнными plan/package артефактами.
- Устранить либо детерминированно обнаружить дублирование roster до full CI.
- Доказать sensitivity orchestration tests ограниченным fault injection.

**Non-Goals:**

- Новый orchestrator, Gate 6, обязательный дополнительный reviewer или mutation testing приложения.
- Изменение domain state, persistence либо адаптеров `rapid-pilot/`.
- Ослабление exact-source evidence, full CI, uniqueness или coverage.

## Decisions

### 1. Владельцем остаётся delivery tooling

`tools/delivery/` владеет runner records, active bindings, package/plan lifecycle и hook context. Verification runner остаётся владельцем состава и запуска suite. Разрешённые зависимости — Python standard library, Git/GitHub CLI как уже наблюдаемые внешние seams и существующие repository verification files. Persistence owner — внешний `FMONITOR_HARNESS_HOME`; repository хранит только код и deterministic fixtures.

Альтернатива — вынести новый orchestration framework — отвергнута как лишний новый seam и non-goal issue #90.

### 2. Contract matrix запускает публичный CLI в subprocess

Матрица создаёт изолированные child fixtures для всех outcome/exit комбинаций, запускает `harness.py run`, читает stdout JSON и canonical retained record по возвращённому пути, затем проверяет filesystem boundaries. Сигнал и timeout ограничиваются монотонными deadlines и process-group cleanup.

Альтернатива — unit-тестировать `execute()` напрямую — отвергнута: она не ловит расхождение возвращаемого значения `main()` и shell exit.

### 3. End-to-end fixtures используют временные реальные worktrees

Multi-worktree и lifecycle tests создают bounded temporary Git repository/worktrees либо безопасные worktrees из синтетического fixture repo, вызывают публичные prepare/state/hook/change-verification CLI и очищают только созданные пути. Они не подменяют plan удобными внутренними JSON-файлами.

Альтернатива — monkeypatch внутренних helpers — допустима только для отдельных fault cases, но не является доказательством end-to-end interoperability.

### 4. Roster получает каноническую derivation boundary

Канонический registry должен быть существующим machine-readable источником состава suite; CI composition и тестовые ожидания выводятся из него общей публичной функцией/командой либо сравниваются с ним отдельным fast consistency check. Literal expectations сохраняются лишь для независимых инвариантов порядка/кратности, а не как вторая вручную поддерживаемая копия полного списка.

Альтернатива — удалить literal roster assertion — отвергнута, потому что потеряет гарантию PR #91. Альтернатива — обновлять все копии вручную — сохраняет исходный класс дефекта.

### 5. Fault injection ограничен orchestration boundaries

Тесты применяют детерминированные подмены к изолированной копии Python source/registries либо поддерживаемые fault hooks только в test process. Для каждой заявленной мутации фиксируется конкретный test, который обязан упасть; baseline выполняется тем же путём без мутации.

Альтернатива — внешняя mutation-testing платформа — не нужна и увеличивает время/зависимости.

### 6. Gate 3 dimensions входят в verification input schema/tooling

Infrastructure input перечисляет dimensions со значениями covered/not-applicable и доказательством/обоснованием. Existing check/prepare fail-closed валидирует полноту по типу seam; reviewer получает эти сведения в том же package. Новый approval state не вводится.

## Risks / Trade-offs

- [E2E orchestration tests могут стать медленными] → Использовать маленькие synthetic repos, bounded subprocess deadlines и разделение fast contract/focused broader chain.
- [Fault injection станет связан с форматированием source] → Мутировать явные behavioral seams или structured fixture registries; каждый fault держать минимальным и именованным.
- [Derivation roster может скрыть общий дефект источника] → Сохранить независимые invariants: обязательные категории, uniqueness, порядок фаз и точное однократное включение.
- [Восстановленный harness в историческом dirty checkout отличается от main] → Реализация начинается только после binding exact source и сверки с финальными PR89 blobs; публикация выполняется из отдельного чистого worktree.

## Migration Plan

1. Зафиксировать spec и RED для public runner matrix и PR #91 roster regression.
2. После независимого Gate 3 review реализовать минимальные runner/registry corrections.
3. Добавить end-to-end chains и fault sensitivity, сохраняя каждый шаг bounded.
4. Запустить focused suites через harness, затем canonical full CI для exact candidate source и независимый Gate 5 review.
5. Rollback — удалить hardening commit(s); внешний append-only evidence не удаляется и не переинтерпретируется.
