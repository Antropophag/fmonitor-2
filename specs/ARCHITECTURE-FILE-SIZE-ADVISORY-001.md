# ARCHITECTURE-FILE-SIZE-ADVISORY-001

## Простыми словами

Большой файл требует осмысленной проверки cohesion и responsibilities, но количество физических строк само по себе больше не запрещает поставку. Реальные нарушения владения SQL, DDL, dependency direction, state-changing seams, sessions/workforce и rapid-pilot boundary остаются блокирующими.

## Actor and public seam

Actor: разработчик или CI consumer. Public seam: `tools/architecture/check` с human-readable output либо `--json`; size metadata update выполняется только через документированный публичный CLI option.

## Normative contract

1. Threshold остаётся равным 150 физическим строкам.
2. Новый production file `>=150`, рост baselined hotspot, move/rename большого файла и пересечение threshold из-за комментария/пустой строки MUST создавать size advisory, но MUST NOT создавать blocking error.
3. JSON MUST однозначно содержать отдельные arrays `errors` и `advisories`. `ok` и process exit MUST зависеть только от `errors`.
4. Human output MUST показывать advisories при PASS и при FAIL.
5. SQL ownership, DDL/runtime migration ownership, dependency direction, rapid-pilot boundary, session/workforce ownership и public-seam ownership MUST оставаться blocking и fail-closed.
6. Size metadata update MUST сохранять все non-size baseline sections без изменений даже при наличии current unrelated violations.
7. Checker не требует сокращения или decomposition только для достижения line ceiling; review оценивает cohesion и responsibilities по смыслу.
8. Изменение не создаёт доменных фактов, authorization/audit/replay/concurrency semantics; persistence/deployment/backup/restore schema frontier неприменимы. Verification inventory меняется только штатной регистрацией одного canonical test.

## Executable examples

Public CLI fixtures MUST доказать: 149→150 comment/blank; рост hotspot; новый большой файл; rename/move; большой файл с forbidden SQL; с forbidden DDL/runtime migration; с forbidden dependency; mixed JSON/exit; size update preserving unrelated exceptions; существующие negative rule fixtures остаются blocking по прежней причине.
