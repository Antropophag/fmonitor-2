## Context
Issue #76 прямо разрешает автономную декомпозицию и сохранение нынешних сценариев,
filters/sorting/pagination и выделение owner планирования. Нормативная матрица:
specs/YII2-OBJECT-QUEUE-001.md; исторические ограничения очереди superseded только
для нового маршрута. Source base41066c68 (кандидат PR85; full CI в работе).

## Goals / Non-Goals
Полный read→schedule→return через Yii. Нет изменения cadence, отмены осмотра,
формул прогресса, карточки/календаря, физической схемы и переключения стенда.

## Decisions
InstallationProcess владеет read projection и schedule command; SQL только
MariaDb-prefixed adapters на Yii Connection. Root composition config внедряет
адаптеры/clock; application class не создаёт concrete persistence и не зависит
от PilotHttp. Controller только native AccessControl/VerbFilter/CSRF, scalar
input, mapping результата и render/303. Auth/eligibility/event используют одно
соединение/транзакцию. Публичное имя scheduleInspection честно требует регистрации
нового seam: отдельный ADR и независимое architecture review до baseline delta;
не переименовывать метод ради обхода checker.

Рендеринг следует текущему shell и публичным shlz exports. Owned JS переносится
в Yii Assets; не читается rapid runtime. Новые файлы <150 строк с осмысленными
внутренними ответственностями; не уплотнять код для hotspot ratchet.

## Dependency impact before Gate 2
- Schema frontier остаётся24, table inventories/migrations/backup payload без
  изменения. Canonical fixture migration до HTTP; приложению DML-only grants.
- Readiness: новый DB boundary требует Yii DAO; существующие mysqli schema
  predicates нельзя вызывать через второе соединение внутри schedule transaction.
  Точный способ reuse manifests/fingerprint reader уточняется до Gate2.
- Queue reads lineage (provenance/details/selection/original/application/order),
  checklist completion/retraction и completion facts. Тесты включают все buckets,
  malformed tuple/hash, cutoff membership, wildcard escaping, pagination.
- Scheduling пишет только existing schedules/events; case/order locks обеспечивают
  стабильный latest engineer, duplicate-key tuple сохраняет единственный event.
- Runtime: config/routes/assets + Yii native session; auth/users/OTIZ neighbors
  обязаны оставаться GREEN. Legacy route не удаляется до общего cutover: сейчас
  production runtime.php всё ещё на старом router. В inventory указать обе
  поверхности и условие удаления при замене entrypoint.
- Deployment: существующая image/config, версия PHP/Yii без изменения; health
  и backup/restore command contracts остаются, readiness focused regression нужен.
- Verification: новые explicit category/suite entries, исторический inventory
  и exact E2E list обновляются вместе. Полный CI один на final exact source.
- Primary data/files/stand не используются: random private DB/users, bounded
  server processes и private artifacts. Browser desktop/mobile через Yii entry.

## Risks / Trade-offs
Старый SQL status-filter считает historical completions, а label учитывает retract;
фиксируем совместимость и видимый долг, не меняем предметное поведение молча.
Pilot schedule требует latest registered order; это не gate открытия. Расширение
на native composition-only scheduling требует отдельного изменения, не этого
переноса. Независимый review проверит полноту матрицы и точный seam baseline.

## Migration Plan
Spec+plan→root RED→independent Gate3→executor GREEN→independent Gate5→CI/merge.
Рабочий stand сохранён. После поставки очередь/календарь и оставшиеся object
routes продолжаются по #76; этот срез не закрывает весь epic.

## Readiness decision
Использовать public definition manifests planning/completion/evidence и Yii reader
information_schema; literal schema не копируется. Выделение чистого сравнения
metadata сохраняет существующий mysqli public predicate и позволяет Yii adapter
сравнивать те же manifests. Паритет valid/missing/column/index/CHECK/FK drift
проверяется отдельно. Selection/application/original exact schema frontier остаётся
обязанностью существующего global health/readiness; queue query/malformed rows
fail closed. На каждом GET не копировать закрытые schema literals этих owners.

Current completion v17 и photo v19 расширяют исходный manifest. Если для Yii
нужен accessor актуальной формы, вынести его из соответствующего существующего
migration/readiness owner и переиспользовать в обоих transports; не копировать
колонки/индексы в новом read adapter. Добавлены соответствующие planned paths.

## Concrete composition root
YiiRuntime\InstallationProcessFactory::queue(Connection,prefix,legacyPrefix) и
::planning(Connection,prefix,optionalClock) создают публичные owners с внедрённым
persistence; config/yii и тестовые fixtures используют одну эту фабрику.
InstallationProcess не конструирует свои concrete MariaDb adapters и не знает
YiiRuntime factory; зависимость направлена от composition root внутрь модуля.
Test setup не навязывает db-only Component constructor application owner.

## Implemented readiness comparison
Final Yii metadata adapter сравнивает canonical metadata с public manifests,
общими с существующими mysqli predicates; literal schemas не дублированы.
Публичные current-manifest accessors v17/v19 вынесены к существующим schema owners.
Старые mysqli comparators не переписаны; совпадение результатов подтверждается
public readiness parity/fault tests. Полная унификация transport normalization
не нужна для этого среза и не выдаётся за выполненную переработку всей schema library.
