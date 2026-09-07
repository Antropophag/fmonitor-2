# ARCHITECTURE-PHP-SELECT-ARRAY-KEY-001

Версия0.1,2026-09-07.

## Простыми словами

Readonly Bitrix request содержит PHP array key `select`; текущий checker ошибочно
считает его SQL. Исправляем только lexical распознавание ключа, сохраняя запрет
реального SQL и прежний baseline.

## Contract

Actor developer/CI. Public seam `python3 tools/architecture/check.py --json` над
isolated repository fixture с неизменным tools/architecture/baseline.json.
Для PHP production source whole quoted literal `'select'` или `"select"`
(ASCII case-insensitive) непосредственно перед PHP `=>` с необязательными
пробелами на той же строке является именем array key, а не SQL keyword.
Только этот quoted token исключается из SQL detection. Результат для
`$request = ['select' => ['ID']];` в app/Workforce/Request.php — exit0,
JSON oktrue/errors[]. Вложенные arrays и одинарные/двойные кавычки эквивалентны.

Bare quoted `'SELECT'` без key-arrow, concatenation `'SELECT'.' id FROM users'`,
SQL внутри значения и SQL на той же строке с таким ключом остаются нарушением:
exit1, JSON okfalse, sql_ownership error с исходным source fingerprint.
Например `$request=['select'=>'SELECT id FROM users'];` и
`$request=['select'=>[]]; $db->query('SELECT id FROM users');` оба отклоняются.
Если `select =>` встречается внутри более длинного SQL literal, literal нельзя
исключать. DDL и rapid-pilot mutation по-прежнему обнаруживаются даже рядом с
ключом. Другие правила и утверждённый ARCHITECTURE-PHP-SELECT-TOKENS-001 сохраняются.

Нет file allowlist, baseline edits, FS/domain writes у checker или пропуска строк.
Authorization/audit неприменимы: readonly developer CLI. Tests работают только
с временными synthetic fixture files и не изменяют source repository/baseline.
Ожидания определены примерами выше независимо от scanner implementation.

Gate1 → RED public CLI → independent Gate3 → minimal GREEN → tool regressions,
make architecture-check → independent Gate5. Approved test expectations не меняются
без повторного Gate2/3. Это технический prerequisite текущего Bitrix delivery.
