# ARTIFACT-STORE-001 — долговечно сохранить и скачать PDF-артефакт распоряжения

- Статус: `APPROVED`
- Версия: `0.3`
- Дата: `2026-08-28`
- Актор: сотрудник ФКР с capability `assignment_order.prepare`
- Публичный command seam записи: `InstallationProcess.prepareAssignmentOrder(...)`
- Публичный seam чтения bytes: `AssignmentOrderArtifactService.download(installationObjectId, assignmentOrderVersion, artifactType, actorId)`
- Storage seam: `ContentAddressedArtifactStore(root)`
- Renderer adapter: `StoringAssignmentOrderRenderer(ProductionPdfAssignmentOrderRenderer, ContentAddressedArtifactStore)`

## 1. Цель и граница

Сохранить exact production PDF bytes одновременно с подготовкой распоряжения и сделать их доступными после завершения исходного request/process через авторизованный service. Process persistence продолжает хранить filename/mediaType/size/SHA-256; content store хранит immutable bytes по SHA-256.

Срез не добавляет HTTP response/controller. Download service возвращает типизированный application result, который будущий transport сможет преобразовать в безопасную загрузку.

## 2. Production storage root

`ProductionInstallationProcessConfig` расширяется обязательным string-полем:

```text
artifactStorageRoot
```

Production передаёт явный абсолютный постоянный каталог внутри home текущего service account, например `/home/fmonitor/artifacts`. Значение не имеет default, не выводится из current working directory и не может быть `/tmp`, подкаталогом `/tmp`, `/`, home root либо symlink.

`ProductionInstallationProcessFactory::create(...)` всегда требует непустой `artifactStorageRoot` и всегда собирает `StoringAssignmentOrderRenderer`. Отсутствующий/пустой root отклоняется; non-storing production fallback и прежняя factory composition без artifact store больше не допускаются. Это supersedes базовую config `PRODUCTION-COMPOSITION-001`; его integration tests также обязаны создавать secure persistent test root.

Как `create`, так и `createArtifactService` при отсутствующем/uninitialized/пустом `artifactStorageRoot` fail closed до сборки adapters и SQL тем же exact `InvalidArgumentException("Invalid artifact storage root.")`.

На POSIX constructor определяет effective account только через доступные `posix_geteuid()` и `posix_getpwuid()`. `pw_dir` обязан быть абсолютным существующим directory и разрешаться `realpath`; если environment `HOME` задан, его `realpath` обязан совпасть с `pw_dir`. Если POSIX functions, uid/home либо cross-check недоступны/противоречивы, store fail closed — fallback к process cwd/env-only home запрещён.

`ContentAddressedArtifactStore` constructor лексически нормализует absolute path без `.`/`..` components, разрешает root `realpath` и до записи проверяет:

- path абсолютный;
- каталог существует, является обычным directory, не symlink и разрешается `realpath` в тот же path;
- root является строгим потомком trusted POSIX `pw_dir` (не самим home);
- каталог readable/writable/executable process user и `stat.uid = effective uid`;
- каждый path component от trusted home включительно до root проверен `lstat`: существует, directory, не symlink, group/other write bits отсутствуют (`mode & 0022 == 0`);
- path не равен/не находится под `/tmp` и не равен filesystem root;
- запомнены exact `stat.dev` и `stat.ino` trusted home/root.

Нарушение даёт:

```text
InvalidArgumentException
message = "Invalid artifact storage root."
```

Message не содержит path, uid, mode или failing component. Store сам не создаёт configured root: его создаёт deployment с текущим effective owner, protected ancestors и backup policy. Security claim ограничен этим trusted-owner/protected-ancestor contract; store не заявляет защиту от root/kernel/current-account compromise.

Интеграционный test создаёт малый уникальный каталог только под workspace/home, например `<repo>/.test-artifacts/<random>`, и удаляет точный каталог после проверки. `/tmp` не используется.

## 3. Content-addressed layout и atomic write

Для lowercase SHA-256 `abcdef...` единственный content path:

```text
<root>/sha256/ab/cd/<64-lowercase-hex>
```

Расширение, artifact type, installation object ID, version и original filename в path не входят. Store принимает bytes, сам вычисляет SHA-256 и size; caller не передаёт target path/hash.

Перед каждым `mkdir`, temp write, existing-target read, publish и final read store заново выполняет ancestor/root validation, включая совпадение запомненных `dev+ino`. Любая root rename/swap, появившийся symlink, новый group/other write bit либо ownership change fail closed до дальнейшей операции.

Подкаталоги создаются внутри validated root с permission не шире `0750`. Если concurrent `mkdir` вернул failure/already-exists, store заново делает `lstat` exact expected child и принимает только real non-symlink directory с подходящими owner/mode внутри прежнего root; иначе fail closed. Каждый существующий shard component revalidated тем же образом.

Новый content пишется через exclusive-create randomized temp file в том же leaf-directory, полностью flush/fsync-ится, получает permission не шире `0640`. После повторной root/chain проверки он публикуется atomic no-overwrite `link(temp, target)`/эквивалентом и temp удаляется; обычный rename, способный перезаписать target, не используется. Если concurrent publish сообщает existing target, store revalidates root/chain/target и принимает только exact regular immutable content. Partial target никогда не виден reader.

Если target hash уже существует, store после chain revalidation читает его и проверяет exact size/hash/bytes. Exact content означает idempotent success без перезаписи; любое несовпадение означает corruption и fail closed. Symlink/non-regular target или path component отклоняется. Все constructed components после root derived только из regex `^[a-f0-9]{64}$`; input filename никогда не интерполируется. После open и непосредственно перед возвратом/write success root identity/ancestor chain проверяются снова.

Storage errors бросают:

```text
ArtifactStorageException
message = "Assignment order artifact storage failed."
```

Underlying path, bytes, OS/driver message и персональные данные наружу не попадают.

## 4. Storing renderer behavior

`StoringAssignmentOrderRenderer` вызывает защищённый adapter seam `ProductionPdfAssignmentOrderRenderer.renderAssignmentOrder(documentInput)` ровно один раз. После успешной генерации одного combined PDF element он сохраняет exact `order` bytes в content store и возвращает исходный renderer element без изменения filename/mediaType/bytes/order. PDF содержит распоряжение и приложение как отдельные страницы; отдельный artifact type `appendix` текущая production factory не создаёт.

`InstallationProcess` вычисляет metadata/hash из тех же returned bytes и сохраняет их обычным atomic process persistence. Поэтому DB metadata и storage address совпадают по construction, а не по доверенному caller hash.

При render/storage failure wrapper не возвращает partial artifacts. Public prepare наследует exact `ORDER-PREPARE-007` render-failure result: process version, assignments, artifact metadata и success event не сохраняются.

Если content успешно опубликован, а последующий DB transaction неуспешен, immutable blob может остаться orphan. Это безопасно: он не доступен download service без process metadata. GC/reconciliation orphan content — отдельный slice; process transaction не удаляет shared content-addressed blob при rollback.

## 5. Exact successful prepare

Production factory из `PRODUCTION-COMPOSITION-001` теперь собирает для process path именно `StoringAssignmentOrderRenderer`, используя `artifactStorageRoot` config. Остальные adapters/migrations v1–v4 прежние.

Public action:

```php
$prepare = $process->prepareAssignmentOrder(4512, [1042], 73, 18);
```

Command result сохраняет один metadata element, полученный из фактических returned PDF bytes:

```text
order:
  filename = Распоряжение о закреплении монтажников.pdf
  mediaType = application/pdf
  size = exact byte length фактически созданного PDF
  sha256 = lowercase SHA-256 фактически созданного PDF
```

Store содержит exact combined PDF byte sequence по одному derived path. PDF начинается с `%PDF-`; тест не фиксирует byte hash между независимыми renders, потому что PDF factory вправе включать renderer metadata. Process/external tables не получают blob/path columns.

## 6. Download authorization и lookup

```php
$result = $artifactService->download(4512, 1, 'order', 18);
```

Service первым вызывает `MariaDbProcessUserDirectory.actorCanPrepareAssignmentOrder(18)`. Для пилота download подготовленного/зарегистрированного распоряжения требует exact existing capability `assignment_order.prepare`; confirm/open/engineer capability не дают download access.

При false возвращается exact стандартный отказ без чтения process projection/store и без раскрытия наличия файла:

```text
accepted = false
violations = [{
  code: FORBIDDEN,
  message: "У вас нет права скачивать артефакты распоряжения.",
  field: null
}]
```

После authorization service требует positive integer installationObjectId/version и artifactType exact `order` или `appendix`. Invalid request бросает `InvalidArgumentException("Invalid artifact request.")` до store read.

Service читает public process projection, находит exact version и unique artifact type. Он принимает metadata только если:

- filename — непустой valid UTF-8 basename длиной не более 255 code points без `/`, `\\`, control chars или `..` segment; Unicode production filename разрешён, но никогда не используется как filesystem path;
- mediaType exact `application/pdf` для текущего production output либо `text/html` для уже сохранённых legacy artifacts;
- size — nonnegative integer;
- sha256 exact lowercase `[a-f0-9]{64}`.

Filesystem path строится только из validated sha256 по разделу 3. Filename используется лишь как returned download filename, никогда как path.

## 7. Integrity verification и results

Store проверяет path через `lstat`, открывает только regular non-symlink expected hash path внутри validated root, затем проверяет `fstat` открытого handle и exact expected size до чтения. Он читает не более `metadata.size + 1` bytes и заново проверяет exact byte size/SHA-256 до возврата; подмена на слишком большой файл не вызывает unbounded read.

Successful current-production `order` result:

```text
accepted = true
filename = Распоряжение о закреплении монтажников.pdf
mediaType = application/pdf
size = <exact generated byte length>
sha256 = <exact generated lowercase SHA-256>
bytes = <exact combined PDF bytes>
```

Ранее сохранённый HTML `appendix` остаётся доступен по прежнему metadata для обратной совместимости; новый production prepare его не создаёт.

После authorization любое отсутствующее version/type/metadata/blob, invalid persisted metadata, symlink/nonregular path, read failure, size mismatch или hash mismatch fail closed единым exception:

```text
ArtifactUnavailableException
message = "Assignment order artifact is unavailable."
```

Exception не различает missing/corruption и не раскрывает path, expected/actual hash или DB/OS details. Corrupt bytes никогда не возвращаются.

## 8. Factory/service composition и reload

`ProductionInstallationProcessFactory::create(...)` по-прежнему возвращает только `InstallationProcess`, теперь со storing renderer. Factory добавляет отдельный method:

```php
$artifactService = ProductionInstallationProcessFactory::createArtifactService(
    mysqli $connection,
    ProductionInstallationProcessConfig $config,
): AssignmentOrderArtifactService;
```

Он использует то же prefix validation, `processTablePrefix`, `legacyTablePrefix`, `artifactStorageRoot`, production process reader и `MariaDbProcessUserDirectory`. Renderer/clock/Workforce/LegacyObject download service не получает и не вызывает.

После prepare исходные process/service/connection уничтожаются. Новое connection + factory service скачивает combined PDF; bytes проходят exact size/SHA-256 verification, даже если external object/workforce/engineer descriptive rows были удалены. Для authorization actor `18` сохраняются active user/role/prepare capability.

## 9. Gate 2 observability

Integration test suite:

1. создаёт persistent test root под workspace/home и production v1–v4 MariaDB fixtures;
2. собирает production process factory, вызывает public prepare и проверяет public metadata;
3. уничтожает исходные objects/connection;
4. новым factory service скачивает `order`, проверяет PDF signature и exact persisted size/hash;
5. проверяет forbidden actor без store read;
6. отдельно повреждает exact test blob и ожидает `ArtifactUnavailableException`, без возврата bytes;
7. удаляет только exact test root после завершения, предварительно проверив, что path остаётся внутри заранее созданного trusted-home workspace test parent;
8. передаёт root через ancestor symlink и ожидает exact redacted `InvalidArgumentException` до content operation;
9. отдельно делает root либо ancestor от trusted home group/other writable и ожидает тот же redacted отказ;
10. после успешного construction атомарно переименовывает root и создаёт replacement directory по прежнему path; следующая deterministic store/read operation обнаруживает changed `dev+ino`, бросает `ArtifactStorageException`/`ArtifactUnavailableException` по соответствующему seam и ничего не пишет в replacement;
11. запускает два store instances, одновременно впервые сохраняющих один hash при отсутствующих shard directories; оба получают success, существует один exact blob, temp/partial targets отсутствуют.

Test не вызывает private path-builder и не выводит expected hash из downloaded bytes. Direct filesystem fixture mutation разрешена только для corruption precondition; successful observation идёт через service.

## 10. Не входит в срез

- HTTP headers/range/streaming/controller;
- public links, share tokens и anonymous download;
- separate `artifact.download` capability;
- blob DB storage/object cloud storage;
- orphan GC, retention, backup/restore verification;
- re-render/versioned templates;
- DOCX/1С ДО final file;
- deletion of historical artifact metadata/content;
- antivirus/content-disposition browser policy.

## 11. Решения и доказательства

- `ProductionPdfAssignmentOrderRenderer`: текущий production PDF factory с combined order/appendix output.
- `specs/DOCUMENT-RENDER-HTML-001.md` v0.2: historical HTML renderer contract, сохраняемый для legacy compatibility.
- `specs/ORDER-PREPARE-007.md`: renderer/storage failure maps to no partial process result.
- `specs/PRODUCTION-COMPOSITION-001.md`: one production factory/config and explicit prefix routing.
- data model: process DB stores reproducible metadata/hash while byte strategy belongs to renderer/storage.

## 12. Утверждение Gate 1

- Владелец продукта: пользователь проекта
- Дата: `2026-08-28`
- Решение: `APPROVED`
- Комментарий: версия 0.2 первоначально ввела durable content-addressed HTML storage/download; версия 0.3 сохраняет его POSIX owner/ancestor/root-identity invariants и отражает последующую production PDF composition.

Версия `0.3` синхронизирует contract с уже действующей production PDF factory. В рамках rapid-delivery задачи пользователь явно отменил обязательные Gate 1–5; security/storage invariants версии 0.2 не ослаблены.
