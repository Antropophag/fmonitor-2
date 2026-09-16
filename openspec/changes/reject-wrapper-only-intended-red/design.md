## Context

См. `proposal.md` — Why и delta spec. Сейчас `harness.py` ищет expected marker в объединённых raw stdout/stderr процесса. Для profile route stderr содержит `RUN_IN_PROFILE_RESULT` с сериализованным argv; поэтому metadata способна ложно удовлетворить admission после реального setup/regression failure. `run-in-profile` уже имеет естественную границу: child stdout/stderr наблюдаются до отдельного wrapper diagnostic.

## Goals / Non-Goals

**Goals:**

- Явно передать runner-у provenance разрешённого child observation без semantic parsing логов.
- Сохранить существующие raw streams, exit code, command verdict и control-marker priority.
- Проверить helper-level matrix и минимум один настоящий public harness/wrapper route.

**Non-Goals:**

- Перестройка evidence format, исправление dependency visibility, worktree identity, product tests или Gate policy.
- Распознавание смысла произвольных строк, очистка metadata regex-эвристиками или hard-code конкретной dependency/issue.

## Decisions

### 1. Owner — delivery runner, provenance — structured wrapper protocol

`tools/delivery/harness.py` остаётся владельцем classification и retained evidence. Profile wrapper предоставляет отдельное structured указание на границу/канал child observation; runner проверяет intended marker только в bytes разрешённого observation, а control outcomes и raw verdict — по существующим правилам. Допустимые зависимости: Python standard library и текущий wrapper protocol. Persistence owner остаётся внешним `FMONITOR_HARNESS_HOME`.

Альтернатива — вычищать argv/JSON regex после объединения — отвергнута: это неполно и превращается в semantic log parser.

### 2. Direct command сохраняет совместимый oracle channel

Для обычного неврапперного acceptance command stdout/stderr остаются разрешённым observation channel. Для известного structured wrapper diagnostic metadata исключается из marker admission, но сохраняется без изменений в evidence. Неизвестный/повреждённый protocol fail-closed не предоставляет intended-RED provenance.

Альтернатива — запретить intended RED всем direct commands — нарушает healthy fixtures и необоснованно меняет Gate semantics.

### 3. Public-route sensitivity обязательна

Тестовая fixture запускает настоящий `harness.py run` с bounded wrapper, который отделяет child observation от metadata. Defective case намеренно помещает marker только в argv/diagnostic и доказывает старый false positive; positive case помещает тот же marker в legitimate child oracle. Тест не требует Docker, vendor или product suite.

### 4. Остальные архитектурные группы неприменимы

Schema/database, backup/restore, domain authorization/audit/history, HTTP/screens/import/cron и deployment не затрагиваются. `rapid-pilot/` не читается и не меняется. Architecture-check impact ограничен существующими tooling/tests boundaries; новые внешние dependencies отсутствуют.

## Risks / Trade-offs

- [Wrapper diagnostic может измениться] → Версионировать/валидировать минимальный structured observation envelope и fail closed для intended RED.
- [Legacy direct fixture случайно зависит от marker в stderr] → Сохранить direct stdout/stderr как legitimate channel и покрыть healthy fixtures.
- [Control marker окажется только в wrapper metadata] → Control classification и intended marker provenance проверяются раздельно; raw diagnostic всегда retained.

## Migration Plan

1. Зафиксировать normative contract, plan и executable RED на старом behavior.
2. После Gate 3 внести минимальную compatibility extension wrapper/runner.
3. Выполнить focused checks, независимый Gate 5 и один exact-source CI run.
4. Rollback — откатить slice B code/tests; append-only external evidence не удалять и не переинтерпретировать.
