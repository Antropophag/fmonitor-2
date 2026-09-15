## Context

См. `proposal.md`. Сейчас `run.sh` читает legacy suite/runtime/path из `suites.tsv`, тогда как `ci.py` и change-verification planner независимо читают path/category из `categories.json`. Наборы совпадают, но одно логическое изменение требует двух файлов. Legacy suite и CI category не имеют стабильного 1:1 соответствия: например, `characterization` содержит governance и другие категории, а `db` в основном соответствует integration. Поэтому registration не может выводить suite из category.

## Goals / Non-Goals

**Goals:**

- Один parser/validator владеет форматом, enums, existence, uniqueness, discovery и canonical ordering.
- Python consumers импортируют его как stdlib-only module; shell runner использует его CLI output.
- Registration всегда требует явный `SUITE` и выполняет atomic replace только после полной предварительной валидации candidate content.
- Planner проверяет добавленные canonical test paths до построения Gate 3 package.

**Non-Goals:**

- Не менять shard balancing, CI setup/cache, Docker, evidence reuse, Quality Graph publisher, FAST classifier или architecture guard.
- Не менять test code semantics, product code или rapid-pilot entries; существующие ACTIVE rapid-pilot rows лишь сохраняются при механической миграции.
- Не вводить второй manifest, новый planner, Gate или CI orchestrator.

## Decisions

1. Добавить один stdlib-only module/CLI рядом с manifest, например `tools/verification/inventory.py`. Он возвращает TSV/JSON machine-readable views для legacy suite и CI category, выполняет `validate` и `register`. `ci.py` и `change-verification.py` импортируют этот module по file path, а `run.sh` вызывает CLI list. Альтернатива — три parser implementations — отвергнута как новое дублирование.
2. Canonical строка имеет четыре TAB-separated поля `suite runtime path category`. Файл сортируется стабильным tuple, выбранным и зафиксированным executable contract. Комментарии/пустые строки не используются как независимые данные; migration сохраняет все 427 логических записей, а не исторический физический порядок.
3. `categories.json` удаляется. `.quality-graph/verification-policy.json` хранит только `suite_inventory`; planner получает categories из общего parser. Workflow не парсит manifest и продолжает вызывать `ci.py`.
4. Discovery разделяется на baseline validation действующих canonical patterns и candidate-aware проверку новых paths. Существующие tracked helpers/retired tests, которые уже не входят в inventory, не становятся ошибкой задним числом. Новый added path с поддерживаемым executable test naming/runtime считается canonical, кроме действующих явных agent-harness/non-product исключений planner policy. Это обеспечивает ранний fail без широкого cleanup исторических artifacts.
5. `make register-test FILE=... CATEGORY=... RUNTIME=... SUITE=...` вызывает общий CLI. CLI проверяет исходный manifest, candidate row и полный candidate manifest до временной записи; затем делает atomic `os.replace` и повторно валидирует committed bytes. Ошибка до replace сохраняет исходные bytes; межпроцессная блокировка/stale-writer protocol не добавляются. Альтернатива с optional suite отклонена из-за доказанной неоднозначности.
6. Fast job заменяет прямой `verification_ci_001_test.py` на `inventory.py validate` с candidate/base context для discovery. Governance membership самого `verification_ci_001_test.py` сохраняется. Полная category composition остаётся ответственностью `ci.py`; aggregate expectations не меняются.

## Risks / Trade-offs

- [Параллельные product PR добавляют строки старого формата] → перед final candidate обновиться от актуального `main` и механически перевести новые rows, не поглощая product diff; PR #148 сейчас является таким ожидаемым writer.
- [Исторические неинвентаризированные helpers дают false positive] → candidate-aware discovery проверяет additions, а baseline discovery сохраняет подтверждённую действующую policy вместо общего `tests/**` cleanup.
- [Shell quoting или partial write повреждают manifest] → registration реализуется в Python с argv, temporary file в том же каталоге и atomic replace; shell/Make только передают значения.
- [Удаление JSON ломает скрытого consumer] → bounded `rg` и focused tests подтверждают отсутствие active references; historical reviews/OpenSpec records не переписываются.

## Migration Plan

1. Characterization tests фиксируют RED для четырёхполосного manifest, atomic registration, discovery, category union и fast/governance separation.
2. Механически объединить текущие 427 rows с их существующими category values, затем удалить `categories.json`.
3. Перевести `run.sh`, `ci.py`, planner, policy и Make registration seam на общий parser.
4. Выполнить focused Gate checks и независимые reviews; перед final source синхронизировать актуальный `main`, разрешая только механические inventory additions.
5. Rollback — revert всего bounded commit; формат и consumer migration должны откатываться вместе.
