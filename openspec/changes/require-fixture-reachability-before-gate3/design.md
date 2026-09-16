## Context

См. `proposal.md` — Why и delta spec. Gap-check `origin/main` `764f2c0f2118a8c8f8cdb7b8235fb360e982bb13` показал: `_gate_expectations()` сводит mapped Gate-3 commands к `INTENDED_RED`, `_validate_evidence()` валидирует identity/source/outcome, а `command_prepare()` требует покрытие mapped commands, но не знает о недостигнутом remainder. Slice #123 уже защищает provenance самого RED marker; этот slice добавляет только второе, orthogonal доказательство reachability.

## Goals / Non-Goals

**Goals:**

- Использовать существующие `harness.py run` records и `prepare` admission.
- Дать test author минимальный opt-in control path только для applicable tests.
- Отличить explicit boundary reached от setup failure, crash и случайной строки в output.
- Воспроизвести пять forensic defect classes до Gate 3, включая realistic #20-shaped post-fork/DB fixture без изменения замороженной #20.

**Non-Goals:**

- Универсальная статическая проверка произвольных tests или instrumentation всего test corpus.
- Новый Gate, test framework, evidence store, semantic log parser либо product implementation prerequisite.
- Выполнение реальных product mutations, production requests или изменение product/domain persistence.

## Decisions

### 1. Opt-in declaration lives beside existing acceptance mapping

Applicable acceptance добавляет bounded reachability declaration в существующий verification input: test identity и стабильное имя boundary. Planner переносит declaration в plan/package; отсутствие поддержки или неизвестная форма fail closed. Tests без declaration работают как раньше.

Это предпочтительнее эвристики по исходнику (`INTENDED_RED` position, fixture API names): эвристика не может надёжно определить control-flow и быстро превратится в новый analyzer/framework.

### 2. Same test exposes a narrow control variant

Test author добавляет в applicable test deterministic control variant, который заменяет только раннюю product assertion на заранее заданное synthetic continuation value и идёт по обычному remainder path до explicit boundary. Variant запускается тем же mapped command через bounded runner mode/environment, запрещающий production targets и product mutations. Boundary сообщает отдельный structured control token, который runner связывает с declaration; zero exit или строка сами по себе недостаточны.

Предпочтительный concrete shape должен переиспользовать уже существующие healthy/defective fixture variants, где они есть. Для остальных достаточно одного маленького conditional control path в конкретном test. Отдельный универсальный fixture DSL и fork всех tests отвергнуты.

### 3. Runner owns classification; prepare owns admission

`tools/delivery/harness.py` остаётся владельцем retained evidence/classification. Новый bounded run option формирует explicit outcome наподобие `FIXTURE_REACHABLE` только при валидном structured boundary token, exit 0, отсутствии control markers (`SETUP_FAILURE`, `UNKNOWN`) и совпадении declared boundary. Любой exception/nonzero/timeout/signal даёт существующий failure outcome, даже если marker был напечатан раньше.

`command_prepare(... role=reviewer, gate=3)` для applicable acceptance требует два records текущего exact source: обычный `INTENDED_RED` и `FIXTURE_REACHABLE`. Оба связываются с acceptance id, command id, environment и test blob; reachability mode/boundary также входят в record identity. Package остаётся `NOT_REVIEWED`.

Альтернатива — считать обычный успешный запуск control script достаточным — отвергнута: случайный early exit и output spoofing дали бы ложное доказательство.

### 4. Probe is read-only with respect to product facts

Control может создавать только disposable test infrastructure в уже изолированной fixture namespace и читать/валидировать её. Он не вызывает product command mutations и не меняет production code. Если remainder по природе требует product mutation, test должен поставить boundary до mutation либо slice получает `NEEDS_OWNER`; generic framework для безопасной симуляции не строится.

### 5. Sensitivity matrix is tooling-only

Bounded meta-tests создают disposable synthetic mapped tests для missing table/column, wrong helper arg, malformed provider/index и invalid setup/CSRF source. Realistic case повторяет форму #20: ранний product RED, затем child/post-fork connection к disposable DB fixture с controlled healthy/defective variants. Замороженные файлы/ветка #20 не изменяются и не становятся dependency.

Owning module — `tools/delivery/`; allowed dependencies — Python standard library и текущие harness/planner helpers. Persistence owner — существующий внешний `FMONITOR_HARNESS_HOME`; новые repository/runtime stores отсутствуют. `rapid-pilot/` не используется и не меняется. Architecture-check impact ограничен tooling/test registration и текущими verification boundaries.

## Risks / Trade-offs

- [Test author может поставить boundary слишком рано] → normative contract и Gate-3 reviewer проверяют, что declaration покрывает материальный remainder; sensitivity test требует defect после early RED.
- [Control variant расходится с ordinary path] → связывать его с тем же test blob/command и разрешать обход только одной именованной product assertion.
- [Probe случайно мутирует product facts] → bounded mode запрещает production targets; fixture-only writes остаются disposable, а mutation-requiring remainder ведёт к `NEEDS_OWNER`.
- [Новый outcome ломает consumers] → добавить его только к opt-in runner mode; обычные GREEN/INTENDED_RED/SETUP_FAILURE records сохраняют формат и semantics.
- [Generic mechanism требует большого rewrite] → остановиться с `NEEDS_OWNER`, не вводить DSL/framework и не инструментировать весь corpus.

## Migration Plan

1. Root фиксирует normative tooling spec, acceptance matrix и executable RED на текущем Gate-3 preparation.
2. После независимого Gate 3 executor добавляет минимальный opt-in runner/prepare contract.
3. Выполнить focused tooling tests, sensitivity на synthetic defective и realistic #20-shaped fixture, независимый final review и один exact-source CI run.
4. Rollback — откатить opt-in code/tests; existing evidence records остаются append-only и не переинтерпретируются.
