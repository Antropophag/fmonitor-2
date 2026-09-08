## Context

См. proposal. Audit table сейчас имеет UNIQUE request/status/reason и FK к terminal request; это мешает двум уже требуемым случаям. Original standalone schema v2 пока не зарегистрирована в canonical runner1..12. DATA-INTEGRITY Gate5v2 отдельно закрыт на4ed122c.

## Goals / Non-Goals

**Goals:** сохранить каждую denied invocation и file-failure audit на прежнем журнале, сохранив terminal replay и original history. Точный contract — `specs/ASSIGNMENT-ORDER-ORIGINAL-ATTEMPT-AUDIT-001.md`.

**Non-Goals:** новая модель access control, UI журнала, новый application mutator или изменение acceptance/composition/opening.

## Decisions

- Application владеет precedence/cleanup/time/result, отдельный MariaDB writer — короткой audit transaction. HTTP/rapid-pilot не получают SQL и нового domain logic.
- RecordDenied одним transaction пытается создать terminal и всегда добавляет новую audit row. При существующем request сохраняет его без чтения result. Это избегает confidential lookup и двух несвязанных транзакций.
- Forward v3 меняет прежнюю audit table; отдельная параллельная таблица отвергнута, поскольку раздваивает историю и её canonical read. Old v2 schema API остаётся для прежних isolated fixtures; v3 migration распознаёт target до вызова v2 setup.
- InstallationProcess владеет DDL/metadata/lock; runtime adapters только читают/пишут факты. Canonical frontier проверяется перед reserve13. Schema changes требуют `make architecture-check`, baseline growth не допускается.
- No fresh terminal lookup для denied unknown: он может раскрыть accepted result. Типизированный unknown возвращается без повторной audit transaction; новая denied invocation по решению владельца снова получает отдельную запись.
- New dependency optional только ради source compatibility; deployment binds real audit writer. Unavailable default не объявляется launch-ready.

## Risks / Trade-offs

- [Сбой acknowledgement commit] → OUTCOME_UNKNOWN и отсутствие автоматического повтора; история возможной committed попытки сохраняется.
- [Снятие FK разрешает orphan audit] → только valid file-failure rows без terminal допускаются reader-ом; остальные orphan rows остаются corruption.
- [Concurrent terminal insertion] → DB request-key exclusion и atomic audit, без чтения confidential payload.
- [Изменение canonical frontier] → повторная проверка версии перед Gate1 и runner patch; старые approvals не переоткрываются.

## Migration Plan

Независимый Gate1 фиксирует exact v3/ports. Затем RED и независимый test review предшествуют schema/application GREEN. Миграция13 выполняет canonical installation original v3 и повторяется без DDL; deployment включается после Gate5. После новых audit rows возможен только forward-compatible rollback приложения; history не удаляется.

ATTEMPT-AUDITv0.2 уточняет active borrowed transaction: после DTO/prefix только один read-only state SELECT, writer ROLLED_BACK без observer/writes/transaction control; migration fixed DatabaseUnavailable до metadata/lock. Caller pending facts сохраняются. Это technical correction по Gate1, не новое product решение.

ATTEMPT-AUDITv0.3 добавляет обязательную read-only recognition exact capability-v5 в старых migrations3/4, иначе canonical repeat остановится до13. Identities/order и старые v3/v4 contracts сохраняются, grants не меняются; новый literal/name drift остаётся conflict. Это найденная source dependency, не расширение runtime прав.

ATTEMPT-AUDITv0.4 закрывает измеренный identifier1059 при prefix25: единая детерминированная mapping сокращает только два слишком длинных maintenance suffix, FK names ограничены hash-based54 bytes. Короткие существующие tables/history не переименовываются; v2 setup/metadata и maintenance/evidence consumers используют ту же policy. Это обязательный predecessor canonical13, prefix не сужается.
