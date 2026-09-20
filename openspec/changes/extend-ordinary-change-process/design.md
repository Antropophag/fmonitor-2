## Context

Существующие `change-verification.py`, `.quality-graph/verification-policy.json`, canonical test inventory и `harness_context.py` уже владеют lane, selected checks, lifecycle declaration и review packages. Узкий `COMPACT_MAINTENANCE` отделяет ceremony от CI, а `bounded-server-rendered-presentation` связывает FAST с ownership/oracle. Новый contract расширяет эти же seams; admission policy является чувствительной, поэтому данная доработка сама проходит Gate 3 + final.

## Goals / Non-Goals

**Goals:**

- Обобщить compact eligibility на три закрытых ordinary change kinds с декларацией автора и механической проверкой доступных границ.
- Развести `required_reviews` и CI breadth в одном planner result.
- Позволить FAST presentation включать изменённые regression tests через существующий ownership/oracle/consumer graph.
- Fail closed при чувствительной или неизвестной семантике и при неполном CI evidence.

**Non-Goals:**

- Новый registry, LLM classifier, универсальный AST-анализатор или whitelist PR/путей.
- Реализация #107/#153, supervisor, общего cache/metrics, изменение merge authority или product runtime.
- Повторная реализация исторических product changes либо изменение стенда.

## Decisions

1. **Расширить существующую lifecycle declaration.** `COMPACT_MAINTENANCE` получает общий `change_kind` и структурированное risk rationale для `PRESENTATION`, `READ`, `APPLICATION_TEST_OR_REFACTOR`. Planner-owned sensitive boundaries имеют приоритет. Альтернатива — второй ordinary registry — отклонена как источник расхождения.
2. **Оставить два независимых результата.** Lifecycle route формирует `required_reviews`; verification coverage формирует FAST/FULL CI. Неполный mapping эскалирует только CI. Альтернатива — выводить Gate 3 из FULL — противоречит owner decision.
3. **Расширить current ownership/oracle graph.** Presentation boundary принимает связанные test owners и consumers, только если closure полон и все изменённые tests выбраны. Не добавляются имена исторических PR или файлов как разрешения.
4. **Проверять mixed-file sensitivity по доступному diff-aware механизму.** Используются существующие semantic boundary/sensitive-method сигналы и exact source recomputation при каждом prepare. Формальное доказательство всей программы не требуется; unknown sensitive risk fail closed.
5. **Один нормативный contract.** Новый stable spec будет источником acceptance; OpenSpec proposal/design/tasks только ссылаются на него и не дублируют полную матрицу. Для обычных будущих задач остаётся допустим compact task/delivery record.
6. **Исторические изменения — replay fixtures.** #187/#194/#209 и unseen example материализуются из Git diff/fixture descriptors для оценки общего правила. Их identifiers не участвуют в production classification.

Owning module — delivery/verification tooling. Разрешённые зависимости — Git exact source, Quality Graph policy, canonical inventory, harness evidence. Persistence owner и rapid-pilot adapter отсутствуют; product tables/history не затрагиваются. Architecture check обязан покрыть policy/harness boundaries.

## Risks / Trade-offs

- **[Ложный ordinary для смешанного файла]** → sensitive-method/diff signals имеют приоритет, unknown требует Gate 3.
- **[FAST пропустит изменённый test/consumer]** → closure требует все changed tests, mapped consumers и environment checks; иначе FULL.
- **[Дублирование contract prose]** → один stable spec, OpenSpec содержит ссылки и implementation decisions.
- **[Исторический fixture превратится в whitelist]** → тест включает unseen module/example и проверяет отсутствие identifiers в policy.
- **[Stale package после нового diff]** → source digest и plan пересчитываются/сверяются на reviewer и CI boundaries.

## Migration Plan

1. Добавить нормативный contract и RED end-to-end regressions.
2. Подготовить Gate 3 package, получить независимое approval.
3. Минимально расширить planner/harness/policy и focused fixtures.
4. Выполнить final review, один exact-source CI и открыть отдельный PR.
5. При rollback удалить только новую классификацию/mapping; существующие sensitive и FAST v1 правила остаются.
