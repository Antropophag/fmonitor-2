# Независимый Gate3: maintenance compatibility v1

Дата: 2026-09-06. Reviewer: отдельно назначенный agent `/root/maintenance_review`,
gpt-5.6-sol low, не автор tests/source. Verdict: **APPROVED**.

Spec v0.2 SHA256 d4712f8e82cc7c2f9865bd61924ef24b0cd2640ddbbde3d96be4f5dc9cc7c72e.
Исходный test SHA256 954e5efc1e97ea61eff4a70f972a335b6c4be30bfa19521521d4702a0cf959a5.
Проверенный неприменённый patch SHA256 4e53e1079d9f3d8b1666b92548c26080d2947759b700f46e6120f52098382cab.
RED: `/Users/antropophag/.local/state/fmonitor2-verification/original-maintenance-compatibility-red-t9akxwte`.
Исходный тест завершился exit255 на obsolete authorization expectation; evidence
и log согласованы. Patch применён root только после этого verdict.

Reviewer подтвердил: настроенный principal отделён от command principal;
invalid capability даёт construction error; invalid shape не пишет audit;
future cutoff сохраняет terminal/audit. Независимые exact request IDs ...233 и
...240 плюс совпадение request/audit membership обнаруживают лишние и пропущенные
факты. Existing deletion, vanished-cursor pagination, replay, reference lookup,
locking, failure accounting и cleanup oracles неизменны. Ослабления expectations
и привязки к implementation нет. Reviewer не изменял файлы.
