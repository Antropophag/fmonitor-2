# Инвентаризация writer/reader для cutover общего реестра распоряжений

Дата: 2026-09-05.  
Repository HEAD: `060e880cdff41b8564a005fba95d6ed796c7772f`.  
Тип работы: независимый ограниченный source audit, только чтение.  
Срок общего запуска, сообщённый владельцем: среда, 2026-09-09 09:00
Europe/Moscow. Срок не меняет delivery gates и не сужает общий goal.

Эта запись уточняет фактическую поверхность совместимости для предложенного
общего registry/allocator assignment-order identities. Она не является Gate 1
approval, executable spec, планом реализации или разрешением переходить к RED.
Код, тесты, спецификации, БД и внешние системы в рамках аудита не изменялись.

## Хэши просмотренных production sources

| Файл | SHA256 |
| --- | --- |
| `app/InstallationProcess/MariaDbInstallationProcessEnvironment.php` | `5cd931da1ff1bcd356ba2177a3edd0bb56b6aeee1d3f4accb477bd79cbc4a26a` |
| `app/InstallationProcess/ProductionInstallationProcessFactory.php` | `2fcb40a05be9d0a514f77d169a617ee0a9d6f95e245d020c125b20c96e3a5350` |
| `app/InstallationProcess/InstallationProcess.php` | `ba8a2d73ce3c96c0da7eb947727c823592118d74c7215505ea34b3bba64fc7a4` |
| `app/PilotHttp/PilotE2ECoordinator.php` | `f6491662738821743976e06086bcb988269c78a4b3d87b9899df4f65575b30b0` |
| `app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php` | `4c893c34377546ded04fc094bf5cbfd8dd5647655416ec25a8e6e28c65ef114d` |
| `app/AssignmentOrderOriginal/MariaDbAssignmentOrderOriginalEvidence.php` | `232c56f7009ee4416a35d3942dc2ee46053dc383fb0c11e769f418337f9a158d` |
| `app/AssignmentOrderOriginal/AssignmentOrderOriginalFileStorage.php` | `7f90d23d59ba193ed3fa5f917625e0da0d58176e2ae76c3e6edca73b4efc926f` |
| `app/InstallationProcess/AssignmentOrderArtifactService.php` | `4c6252c13e4294401317f2beec3a419a95cd852037c54cfac3a767eedb97ee8c` |
| `app/PilotHttp/PilotHttp.php` | `66806572db401c1fc5c7087ff7cc0b20e72e4f06203040b6cbc76ee3a1326826` |
| `app/PilotHttp/MariaDbInstallerDirectoryReader.php` | `d1dd2e1a4041ec6380beaba4e2b017646ee404832a76252565751439de56ce69` |
| `app/InspectionEvidence/MariaDbInspectionCaseDirectory.php` | `6d096c0737ad9197375f92019a3d1103e0941c219648b742f3fe385a5045096c` |
| `rapid-pilot/ObjectQueue.php` | `b3ff4103a265afb61d70cc87772f4fe57598d6b3d8eb29d397ed45a304d9ec79` |
| `rapid-pilot/ObjectDetails.php` | `6204502b1ba0060dbeb585d96639ffc306a1eea145904169e6798ba206ce5749` |
| `rapid-pilot/InspectionSchedule.php` | `dde376156315bb3c1e27620dd3a9bd54672c83397ab97778bfbf4907d16cdb42` |
| `app/PilotHttp/ChecklistSync.php` | `b1a5256a0d61a70a3feba21f99f7e358893b9aaadbf4a6195a3fc7e288b79d44` |
| `rapid-pilot/NativeOperationalPremiumInputs.php` | `9d76a501c72da1823aa7ddd65c5a95898b95d6db18cd870997a7716b3ee33fca` |
| `rapid-pilot/docker-bootstrap.php` | `700393249fd0c0982564ede62e75f090810624b5bb82eeea8330590e3c0dc29f` |

## Публичные production writers

### Создание physical assignment order и владение allocator

- HTTP prepare входит через
  `app/PilotHttp/PilotE2ECoordinator.php:125-139`: coordinator собирает
  production process и вызывает `prepareAssignmentOrder`.
- Original-first handler в том же coordinator на строках `154-164` также
  сначала вызывает legacy prepare. Ветка `template` после успеха только
  перенаправляет на сохранённый artifact.
- Публичный application method начинается в
  `app/InstallationProcess/InstallationProcess.php:17`; текущее состояние и
  последняя версия берутся из projection на строках `89-99`.
- Production wiring находится в
  `app/InstallationProcess/ProductionInstallationProcessFactory.php:8-29`.
  Renderer обязателен на строке `23`: prepare получает
  `StoringAssignmentOrderRenderer(ProductionPdfAssignmentOrderRenderer, ...)`.
- Фактический allocator принадлежит physical таблице:
  `app/InstallationProcess/MariaDbInstallationProcessEnvironment.php:55-65`
  блокирует последнюю physical строку через `ORDER BY version_no DESC`, а
  строки `79-90` вставляют order без явного ID, получают `insert_id` и затем
  вставляют members/artifacts. Для версии больше 1 строки `82-88` требуют
  physical registered predecessor ровно версии N-1.

Это единственный найденный production creator строк
`fm2_assignment_orders`. Он одновременно владеет вычислением следующей версии
через загруженный physical aggregate и выделением ID через AUTO_INCREMENT
physical таблицы.

### Изменение physical status и связанные записи

- `MariaDbInstallationProcessEnvironment.php:55-65` выбирает переход по status
  последней physical версии; `:93-98` меняет `prepared` на `registered`;
  `:101-105` проверяет physical registered order/members перед opening.
- `app/PilotHttp/PilotE2ECoordinator.php:175` и `:187` содержат два вызываемых
  HTTP пути прямой загрузки signed original. Каждый блокирует physical order по
  case/version, вставляет `signed_original` artifact, меняет physical status на
  `registered`, добавляет process event и обновляет case. Это production adapter,
  а не verification fixture, и он обходит `AssignmentOrderOriginalApplication`.
- Production original command создаёт original root/revision/request/audit facts,
  но не создаёт physical order. Его production factory на
  `app/AssignmentOrderOriginal/AssignmentOrderOriginalFileStorage.php:36` и
  worker wiring на `app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php:136`
  используют legacy-only composition reader.

`app/demo/index.php:26,35` — отдельный demo/legacy adapter с другой схемой
полей (`order_kind`, `order_status`). Он не подключён через pilot production
factory и поэтому не является writer текущего production contour. SQL в tests,
`verify-*` и `MariaDbAssignmentOrderOriginalVerificationFixture` — fixtures и
verification helpers, не production writers.

## Original composition reader

- Public interface: `app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php:29`;
  dependency constructor: строка `57`.
- Единственная найденная production реализация:
  `app/AssignmentOrderOriginal/MariaDbAssignmentOrderOriginalEvidence.php:28`.
  Она ищет ID только в physical `fm2_assignment_orders`, использует `order_date`
  для temporal filtering physical `fm2_order_installers` и не читает registry
  или source discriminator.
- Production и worker factories подключают именно эту реализацию по строкам,
  указанным выше.

Следствие текущих bytes: selection-owned registry ID без совпадающей physical
строки возвращается как `NOT_FOUND`. При случайном совпадении physical ID reader
может прочитать legacy row как источник selection identity, поскольку проверки
registry ownership нет. Malformed physical composition сейчас представляется
как `FOUND` с пустым payload, а query failure — как `UNAVAILABLE`; это также
должно быть явно учтено reader amendment.

## Optional template и legacy prepare

Публичного same-identity render command в production source нет. Legacy prepare
всегда вызывает renderer и сохраняет artifact через wiring
`ProductionInstallationProcessFactory.php:17-29`. После prepare coordinator
переходит к artifact route на `PilotE2ECoordinator.php:139` или `:164`.

Существующий artifact endpoint только читает уже сохранённый artifact:
`PilotE2ECoordinator.php:148`, factory
`ProductionInstallationProcessFactory.php:32-39` и
`app/InstallationProcess/AssignmentOrderArtifactService.php:10-15`. Он получает
projection physical orders/artifacts и не умеет отрендерить отсутствующий
template из selection identity. Поэтому existing artifact download не является
optional-render seam, а legacy prepare не может обеспечить same-identity
поведение: он создаёт новую physical identity и composition.

## Production readers и требуемая классификация cutover

| Consumer | Фактическое чтение |
| --- | --- |
| Process aggregate/history | `MariaDbInstallationProcessEnvironment.php:121-140`: все physical orders, members и artifacts |
| Prepare form/current prefill | `app/PilotHttp/PilotHttp.php:155-169`, особенно `:163,167`: последняя physical версия и её members |
| Object card/history | `PilotHttp.php:188-254`, особенно `:208-248`: highest physical version; policy для prepared change на `:188-193` сохраняет prior registered predecessor |
| Object queue | `rapid-pilot/ObjectQueue.php:36`: status у physical `MAX(version_no)` |
| Installer directory/availability | `app/PilotHttp/MariaDbInstallerDirectoryReader.php:8-9`: registered physical order, но MAX считается по всем physical версиям |
| Object details | `rapid-pilot/ObjectDetails.php:41`: latest registered; `:88-89`: team по MAX всех physical версий и physical-only history |
| Inspection attribution | `app/InspectionEvidence/MariaDbInspectionCaseDirectory.php:16-45,68-93`: latest registered physical order и members |
| Inspection schedule | `rapid-pilot/InspectionSchedule.php:32`: MAX physical version с обязательным registered status |
| Checklist attribution | `app/PilotHttp/ChecklistSync.php:138`: latest registered physical order |
| Premium inputs | `rapid-pilot/NativeOperationalPremiumInputs.php:21-22,54`: latest registered physical order |

Effective assignment readers должны продолжать брать applicable physical facts
до отдельного apply-original slice. Selection history/card и original-source
readers требуют additive registry awareness. Механическая замена каждого
`MAX(version)` на registry MAX была бы неверной: pending selection не должна
скрывать действующее physical основание. Exact cutover contract обязан явно
классифицировать каждого consumer.

Уже существующий риск legacy projection виден в нескольких readers: новый
physical prepared order может скрыть previous registered order, когда MAX
считается без status predicate. Proposed selection ledger сам по себе этот риск
не исправляет и не должен молча менять уже утверждённое поведение.

## Фактическая N-1 несовместимость и startup admission

Bootstrap сейчас запускает migration через
`rapid-pilot/docker-bootstrap.php:44-66`, а ready manifest публикует только после
остальных bootstrap действий на `:67-99`. Ошибка даёт общий
`MIGRATION_FAILED`/exit 70 на `:80-83`.

Runtime factory на
`ProductionInstallationProcessFactory.php:51-54` проверяет только charset. Он
не проверяет schema fingerprint, registry receipt/frontier, writer generation,
compatible build marker или ownership completeness. Prefix validation на
`:56-60` допускает до 32 bytes, что не совпадает с proposed prefix-25 contract.

Из actual code следуют конкретные несовместимости:

1. Уже работающий N-1 process после backfill продолжит выделять ID через
   physical AUTO_INCREMENT и версии через physical aggregate. Registry он не
   видит и не обновляет.
2. Новый selection writer и N-1 legacy writer могут независимо выбрать один
   case version; целостность одной physical таблицы не предотвращает collision
   с отдельным selection ledger.
3. N-1 original reader не найдёт selection source или, при совпавшем physical
   ID, прочитает неправильный source.
4. N-1 UI/read projections не покажут selection history и не умеют optional
   same-identity render.
5. Текущий startup допускает процесс после обычной schema initialization без
   доказательства полного registry cutover. Уже запущенный процесс вообще не
   проходит повторный admission.

Текущий bootstrap предоставляет точку, где будущий fail-closed admission
технически может быть вызван до публикации ready manifest. В reviewed source нет
готового механизма, который исключает старый writer после cutover: нет проверки
совместимости в application factory или request path и нет доказанного барьера
для уже работающего N-1 процесса. Это констатация actual state, а не выбор
реализации.

## Границы вывода

- Аудит не доказывает полноту внешних deployment/process managers; просмотрен
  только repository production contour на указанном HEAD.
- Fixtures, tests, demo и verification helpers отделены от live writers. Они
  остаются будущими compatibility consumers, но не расширяют production writer
  manifest.
- Запись не утверждает migration, reader amendment, optional-render contract,
  N-1 protocol или Gate 1. Она предоставляет source evidence для закрытия
  ранее найденных P0/P1 gaps.
- Никакой safe-log механизм не рассматривался и не предлагается.
