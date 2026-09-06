# ASSIGNMENT-ORDER-ORIGINAL-EVIDENCE-LIFECYCLE-001

Версия0.1. Техническое уточнение ORIGINAL-UPLOAD v74 section16.

## Простыми словами

Проверяющий reader не меняет файлы и факты приложения. Он проверяет конфигурацию
до чтения пароля, открывает отдельное соединение и возвращает понятную одинаковую
ошибку при недоступности. Закрытие reader не снимает чужие блокировки storage.
Формат отчётов, права пользователей и pilot workflow не меняются.

## 1. Scope и public seam

Наследуется approved ORIGINAL-UPLOAD v74: exact EvidenceReaderConfig с именами
properties/parameters databaseHost/databasePort/databaseName/databaseUser/
databasePasswordFile/tablePrefix/privateStorageRoot/safeLogFile, final readonly;
EvidenceReader interface с11 объявленными methods; final factory create(config).
Concrete MariaDb-prefixed adapter реализует interface. Публичные query arguments
caseId/orderId сохраняют exact имена. Fixed EvidenceUnavailable имеет message
`Assignment-order original evidence unavailable.`, code0, previous=null.

Worker DTO/byte-stream API, новые fault-point declarations, orphan fixture create
и изменение canonical JSON projections не входят в этот пакет. Это остающиеся
обязательные части combined original command, не добровольные улучшения.

## 2. Construction и ordering

Scalar grammar exact из parent section16: host `[A-Za-z0-9.:[\]_-]{1,255}`,
port1..65535, database `[A-Za-z0-9_]{1,64}`, user `[A-Za-z0-9_.-]{1,32}`,
prefix `[A-Za-z0-9_]{0,25}`. Нет ambient defaults или schema discovery.
Все3 paths — absolute canonical outside repository, без control/NUL/..,
не symlink. Password/log regular exact0600; root directory exact0700/0750;
владелец effective user либо root. Все scalar/path/metadata проверки завершаются
до password contents и mysqli connection. Никакого create/chmod/chown/repair.
Password1..1024 ASCII0x20..0x7e после удаления максимум одного final LF;
CR/TAB/DEL/nonASCII/remaining LF/empty запрещены. Пароль не trim-ится иначе.

Успех factory — один новый mysqli connection с utf8mb4 и read-only session.
Password descriptor закрывается после чтения при любом outcome. Failed connection/
charset/read-only setup закрывает acquired connection. Любая native warning,
false или Throwable construction становится только fixed EvidenceUnavailable,
без output/path/SQL/credential/previous. Production command writer не используется.

## 3. Reads и close

Каждый public read выполняется только пока reader открыт. DB/file/JSON failure
возвращает fixed exception без partial return и без PHP diagnostics. После close
все10 read methods бросают fixed exception, не выполняя SQL или file access.
Reader не имеет DDL/DML, schema discovery, storage cleanup или lock deletion.
Canonical JSON formats и existing projection oracles наследуются без изменения.

close делает одну native close attempt owned connection, permanently закрывает
reader до попытки и кеширует успех/ошибку. Повтор ничего не закрывает/не открывает;
при прежней ошибке бросает свежую fixed exception. Destructor не бросает.
Ни construction, ни read, ни close не удаляют metadata, stage, blob, safe-log,
password, state lock или digest lock. Cleanup task-owned fixtures принадлежит
тесту после reader/worker shutdown; historical cleanup helpers корректируются
только exact unapplied patch и independent Gate3.

## 4. Минимальная проверка и delivery

Public named config/interface/factory declarations; valid seeded reader control;
representative independently fixed invalid scalar/path/password cases до resource
access; fixed construction/read/after-close exceptions; empty storage metadata
создана public beginStage/abort, public held digest lock остаётся LOCKED для второго
storage после reader close. Это доказывает отсутствие destructive close без
private metadata fabrication, permission transitions/native interception.

Source Gate5 дополнительно проверяет ordering metadata→password→connection,
checked native results, all acquired resources, fixed error boundary и no writes.
Новые injection ports только для тестов не добавляются. Existing evidence/worker/
maintenance regressions и architecture-check обязательны. Gate1→RED→independent
Gate3→minimal GREEN→independent Gate5; declarations не заменяют behavior proof.
