# INTENDED-RED-FIXTURE-REACHABILITY-001 — достижимость fixture перед Gate 3

## Простыми словами

Ранний честный RED больше не сможет скрыть сломанную часть теста, которая идёт после проверки отсутствующего поведения. Только явно применимые tests получают bounded control: обычный запуск по-прежнему доказывает отсутствие product behavior, а отдельный безопасный запуск доказывает, что fixture remainder действительно исполняется. Это не новый Gate, не GREEN продукта и не общий framework.

## Actor, oracle и public seam

Actor — root/test author, готовящий test candidate, и независимый Gate-3 reviewer. Source oracle — forensic-классы #20 и существующий contract `INTENDED-RED-OBSERVATION-PROVENANCE-001`. Public seam остаётся `python3 tools/delivery/harness.py run` и `prepare`; evidence хранится append-only во внешнем harness home.

Applicable acceptance явно объявляет `fixture_reachability` для конкретного mapped test: непустое стабильное имя `boundary` и единственный допустимый `probe_kind=fixture_read_only`. Declaration относится только к tests, у которых настоящий ранний `INTENDED_RED` не позволяет исполнить материальный fixture/infrastructure remainder. Отсутствие declaration сохраняет действующий Gate-3 contract; safeguard не распространяется автоматически на все tests.

## Separate exact-source evidence

Для declared applicable test Gate-3 preparation MUST получить два разных retained records текущего exact source и environment:

1. обычный mapped command с outcome `INTENDED_RED` и expected acceptance marker;
2. тот же mapped command в bounded `--fixture-reachability <boundary>` mode с outcome `FIXTURE_REACHABLE`.

Records MUST совпадать по acceptance id, command id, command environment, test blob и declared boundary. Один record/outcome не заменяет другой. Stale, foreign, duplicated-as-both, malformed или неидентифицированное evidence MUST блокировать preparation. Package после успешной проверки остаётся `NOT_REVIEWED` и требует независимый Gate 3.

## Bounded control protocol

Runner передаёт child только `FMONITOR_FIXTURE_REACHABILITY=<boundary>`. Test control variant SHALL обходить только раннюю product assertion synthetic continuation value, затем выполнять обычный fixture/infrastructure remainder. После достижения declared boundary test печатает отдельную точную строку `FIXTURE_REACHABLE: <boundary>` и завершает child с exit `0`.

`FIXTURE_REACHABLE` разрешён только при exit `0`, точном единственном declared marker и отсутствии `SETUP_FAILURE`/`UNKNOWN`. Wrong/missing/duplicate marker, arbitrary output, exception, nonzero, timeout или signal MUST NOT доказывать reachability. Raw child exit, verdict и stdout/stderr сохраняются без переписывания.

Control SHALL быть read-only относительно product facts и production systems. Допустимы создание, чтение и удаление disposable test infrastructure в изолированной fixture namespace. Product command/HTTP mutation, production target либо обход иных acceptance assertions запрещены. Если безопасная boundary требует generic instrumentation или перестройки test architecture, result — `NEEDS_OWNER`, а не расширение framework.

## Acceptance matrix

| Case | Observation | Required result before Gate 3 |
|---|---|---|
| A | Applicable declaration, только ordinary `INTENDED_RED` | Reject: missing `FIXTURE_REACHABLE` |
| B | Healthy ordinary RED + exact healthy control | Prepare `NOT_REVIEWED` |
| C | Undeclared/non-applicable existing test | Existing Gate-3 behavior unchanged |
| D | Unknown key/kind, empty boundary, unmapped test | Planner fails closed |
| E | Reachability record from other source/environment/command/acceptance/blob/boundary | Reject |
| F | Exact marker followed by nonzero/crash | `REGRESSION_FAILURE`, reject |
| G | `SETUP_FAILURE` plus reachability marker | `SETUP_FAILURE`, reject |
| H | Wrong/missing/duplicate marker or zero without marker | Non-reachability outcome, reject |
| I | Timeout/signal after marker | `INTERRUPTED`, reject |
| J | Missing fixture table/column after early RED | Reject before Gate 3 |
| K | Wrong helper argument after early RED | Reject before Gate 3 |
| L | Malformed data-provider/index after early RED | Reject before Gate 3 |
| M | Invalid CSRF/setup source after early RED | Reject before Gate 3 |
| N | Broken post-fork DB fixture after early RED | Reject before Gate 3 |
| O | Healthy #20-shaped post-fork disposable DB fixture | Ordinary `INTENDED_RED` plus separate `FIXTURE_REACHABLE` |
| P | Repeated/concurrent controls | Independent records; no borrowed outcome |

## Inapplicable architecture groups

Этот tooling-only slice не меняет product schema/data, state transitions, authorization, audit/history, HTTP/screens/import/cron, backup/restore или deployment. `rapid-pilot/` и frozen #20 не читаются как runtime dependency и не изменяются. Новые external dependencies, Gate, evidence store, LLM analysis и test framework отсутствуют.

## Done

Все cases A–P имеют executable coverage; sensitivity показывает минимум один synthetic defective test и realistic #20-shaped healthy/defective post-fork DB fixture. Planner-required independent Gate 3 и final review имеют явный verdict, focused checks GREEN, один exact-source GitHub CI GREEN, отдельный PR готов и не merged.
