# ARCHITECTURE-PHP-SELECT-TOKENS-001

Версия0.1,2026-09-06. Draft technical ratchet correction.

## Простыми словами

Architecture-check ошибочно считает PHP enum SELECT и dotted capability name
SQL-запросом. Правило должно запрещать SQL в business owner, разрешая обычные
идентификаторы PHP без изменения утверждённых публичных имён.

## Contract

Actor — developer/CI. Public seam `python3 tools/architecture/check.py --json`
над isolated fixture repository и прежним baseline. Для PHP production files
case declaration `case SELECT = ...`, class constant reference `Foo::SELECT`
и whole quoted dotted capability atom `'assignment_order.composition.select'`
не являются SQL. Удаляется только эта lexical часть до SQL detection.
Quoted actual SELECT/UPDATE/INSERT/DELETE SQL, включая SQL на той же строке
с enum/constant, по-прежнему дают sql_ownership violation в non-MariaDb business file.
Bare quoted `'SELECT'` для concatenated SQL не считается capability atom.

Не применять file allowlist, baseline growth, отказ от проверки строки целиком
или переименование public capability. Fingerprints настоящих нарушений остаются
на исходном нормализованном source; DDL/rapid-pilot mutation и остальные rules
не меняются. SQL не переносится в domain owner. Tests проверяют actual public
CLI status/envelope; не выводят expected values из реализации detector.

Gate1 → demonstrated RED → independent Gate3 → minimal scanner correction →
existing architecture-tool tests + make architecture-check → independent Gate5.
Это correction измеренного blocker, не speculative harness tuning.
