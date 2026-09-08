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

## Empty inventory correction

Full sourcea8e6e92 terminal exit2: failed db/characterization/E2E only;unitPASS.
Все22nativePHP+Node реально выполнены безfailure.9-test harness расширен emptyNode
и emptyPHP+Node cases, RED2fail/7pass, independent Gate3v2 APPROVED. Guarded Bash3
array expansion сохраняет set-u и nonempty arguments;9testsGREEN в
`empty-inventory-green.log`, bash-n/diffPASS. Current complete unit/db lists
сохранены в `unit-list-final.tsv`/`db-list-final.tsv`; нет исключений legacy tests.
Full repeat после следующих canonical expectation repairs остаётся launch gate.

## Independent closure

Gate5 v2 APPROVED corrected source63264d7: independent9scheduler/Bash3 syntax/
argv conservation/diff checks, bytewise unchanged unit/db inventory106/96.
Reviewer лично проверил real sourcea8 log:23newmembers exactly once/no failures,
unitPASS/fullterminalFAIL3stages. Scope runner6/6 tasks complete, не archived.
Final exact-source fullVERIFY после consumer repairs остаётся общей integration
обязанностью; этот review не является launch approval.
