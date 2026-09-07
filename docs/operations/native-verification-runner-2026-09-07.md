# Native verification runner integration

Source preceding integration: `c93e690`. Original HTTP/UI scoped gates complete,
но общий launch goal остаётся active. Canonical15 terminal подтверждён текущим
runner; прежний full source7cb79d0 завершился failure4stages, без VERIFY_OK.
Failure inventory: external original-http-20260907/make-verify-7cb79d0-failures.json.

VERIFICATION-NATIVE-SUITES-001 Gate1/Gate3 APPROVED. Новый Python scheduler harness
исполняет копию реального runner только в синтетическом tree с trace interpreters.
Он не перехватывает production PHP/domain command и не доказывает native GREEN.
7 RED failures зафиксированы, затем7 GREEN: read-only list, full invocation/order,
3pureunit/19db mapping, Node, failure aggregation и missing dependencies.
Первое замечание Gate3 (php-r скрывался от trace) исправлено ДО approval.

Evidence outside repo:
`/Users/antropophag/.local/state/fmonitor2-verification/native-suites-20260907`.
`runner-red-v2.log`, `runner-green.log`; wrong python executable attempt
`runner-red.log` исключён из RED evidence. Spec/test hashes указаны reviews.

Runner сохраняет InstallationProcess heuristic/characterization/E2E/lint/red;
unit дополненNode с агрегированным failure. List unit/db показывает тот же ordered
набор, который выполняется. Новые19 native PHP входят в db,3command PHP+Node в unit.
Полный реальный canonical run, architecture и independent Gate5 — следующие gates;
никакого runtime/schema/deployment/remote изменения этот change не делает.

## Gate5 feedback

Reviewer нашёл Bash3.2/set-u edge для existing empty Node/unit/db inventories.
`reviews/code/VERIFICATION-NATIVE-SUITES-001.md`: CHANGES_REQUESTED; требуется
новая sensitivity case/RED/Gate3 и guarded empty-array expansion. Текущий real
canonical sourcea8e6e92 уже исполнил все22nativePHP+Node безfailure;
`native-members-observed.json` фиксирует23 наблюдения. Полный run ещё не завершён.
До terminal source/tests сохраняются неизменными;7-test GREEN не закрывает этот edge.
