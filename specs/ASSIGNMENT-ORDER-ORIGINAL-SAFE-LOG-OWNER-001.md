# ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-OWNER-001 — владелец открытого журнала

Версия0.1, 2026-09-06. **DRAFT / FRESH INDEPENDENT GATE1 REQUIRED**.

## Простыми словами

Журнал получает одного владельца открытого файла. Он проверяет именно тот
дескриптор, в который затем пишет, и закрывает его при ошибке. Требования к
настроенному файлу остаются прежними. Поведенческие тесты дополняются точной
проверкой data flow в независимом code review; отклонённые ранее способы
вмешательства между проверкой пути и открытием не используются.

## 1. Authority, actor и scope

Actor — deployment operator, задающий уже утверждённый `safeLogFile`.
Owner resolution:
`docs/operations/assignment-order-original-production-safe-log-owner-resolution-2026-09-05.md`.
Никакое новое пользовательское действие, право, поле config или product outcome
не вводится. Feasibility review:
`docs/operations/safe-log-shared-owner-feasibility-review-2026-09-05.md`.
Это не Gate1 approval; прежний G5-SAFELOG-2 остаётся открытым до полного нового
цикла и combined original-command review.

Этот contract заменяет только pending safe-log construction observer/interval
fixture в parent original-upload spec. Их rejection/review records неизменны.
Нельзя повторять native interposition, syscall/metadata substitution, loader
injection, interval observer/permission transition, timing loops или privilege
changes. Public factory получает обычный configured path, без test selector.

## 2. Public owner и pure policy

Namespace: `FMonitor2\AssignmentOrderOriginal`. Existing safe-log interfaces
наследуются из original command contract.

```php
interface AssignmentOrderOriginalRequestSafeLogObserver extends AssignmentOrderOriginalSafeLogObserver
{
    public function useRequest(string $requestId): void;
}

final class AssignmentOrderOriginalSafeLogAttributePolicy
{
    public static function accepts(
        int $mode, int $uid, int $device, int $inode,
        int $effectiveUid, int $expectedDevice, int $expectedInode,
    ): bool { /* pure policy */ }
}

final class AssignmentOrderOriginalOpenedSafeLog implements AssignmentOrderOriginalRequestSafeLogObserver
{
    private function __construct(mixed $handle, int $sequence, string $requestId) {}
    public static function open(string $file, string $requestId = ''): self { /* owned acquisition */ }
    public static function canonical(string $file): string { /* path-only compatibility query */ }
    public function useRequest(string $requestId): void { /* same correlation semantics */ }
    public function record(string $event, array $safeFields): void { /* retained-handle append */ }
    public function close(): void { /* explicit idempotent close */ }
    public function isClosed(): bool { /* public lifecycle observation */ }
    public function __serialize(): array { /* always reject */ }
    public function __unserialize(array $data): void { /* always reject */ }
    private function __clone(): void {}
    public function __destruct() { /* close fallback; never throws */ }
}
```

Declarations описывают public signatures, не implementation. Raw stream/FD,
path-backed writer, arbitrary descriptor adoption, metadata provider, opener
callback или mutable public property не предоставляются. Private construction
и отсутствие cloning/serialization не позволяют обычному caller создать
непроверенного двойника владельца. Reflection не является application API.

Pure policy не выполняет I/O. Она возвращает true тогда и только тогда, когда
`(mode & 0170000) === 0100000`, `(mode & 0777) === 0600`,
`uid === effectiveUid`, `device === expectedDevice`, `inode === expectedInode`.
Это существующий regular/current-EUID/access-permission contract, а не новая
политика специальных Unix bits. Значения в production происходят из реальных
native observations; literal inputs допустимы только в прямых pure-policy tests.

## 3. Acquisition и ошибки

`open(file,requestId)` принимает тот же trusted pre-existing path. Перед open
проверяются absolute/canonical grammar, no empty/dot/dotdot segments, отсутствие
symlink у entry и иных parent aliases, regular type, current effective UID и
exact access bits0600. Сохраняется существующая Darwin `/var/...` →
`/private/var/...` системная canonical alias; дополнительные symlink parents
этим исключением не разрешаются. `canonical` использует эти же path checks,
ничего не открывает/создаёт и возвращает resolved canonical path.

Current effective UID получается именно через native `posix_geteuid`;
недоступность этого наблюдения — fixed acquisition failure, не предположение
из script owner. `getmyuid`, input/config UID и cached metadata не заменяют его.

Один acquisition owner:

1. Получает final non-following pathname observation и exact device/inode.
2. Открывает существующий canonical файл native `fopen(...,'r+b')`: режим не
   создаёт отсутствующий файл и не обрезает существующий.
3. Выполняет real `fstat` на этом handle; false или отсутствие integer
   mode/uid/dev/ino отклоняется.
4. Передаёт именно эти четыре descriptor fields в approved pure policy вместе
   с current effective UID и final pathname device/inode. Path metadata не
   подставляется вместо descriptor attributes.
5. Только после успешной проверки создаёт opaque owner, который удерживает
   этот же handle для чтения initial line count и последующего append/close.

Любой path/open/stat/identity/attribute/acquisition failure закрывает открытый
handle, если он появился, и бросает `RuntimeException('safe log unavailable')`
с code0 и previous=null. Не создавать/replace/truncate/chmod/chown/repair файл,
не писать diagnostic при construction failure, не обращаться к DB/private root.
Чтение initial line count не переписывает bytes; filesystem atime от обычного
чтения не выдаётся за изменение документных фактов.

## 4. Append и lifetime

Owner сам implements existing `AssignmentOrderOriginalRequestSafeLogObserver`.
`useRequest` задаёт exact requestId для следующей correlation. Начальный sequence
равен числу строк существующего файла, как прежний `file(...,FILE_IGNORE_NEW_LINES)`;
включается последняя непустая незавершённая LF строка. Файл читается только через
retained handle, без `file(path)`/повторного pathname-open.

Каждый `record` увеличивает per-owner sequence на1 перед encode и строит exact
ordered JSON keys `correlationId,event,safeFields,sequence`, flags
`JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES`, ровно один завершающий LF.
Correlation — first12 lowercase SHA256 exact requestId. Существующие restrictions
на event/safeFields и отсутствие filename/path/bytes/SQL/exception сохраняются.
Новый global cross-process sequence contract не вводится.

Append использует этот retained handle: LOCK_EX → seek end → one fwrite →
fflush → attempt-always unlock. Все writers этого специализированного safe-log связываются с одним owner
implementation и соблюдают прежний file-lock protocol; evidence reader не пишет.
Произвольные сторонние writers не добавляются новым config/route.
Prior bytes не переписываются и не обрезаются. Partial/failed write не retry-ится
и не ремонтируется. Ошибка возвращается fixed RuntimeException выше; existing
best-effort observer handling сохраняет выбранный original-command Result.

`close` пытается закрыть только собственный handle, делает owner permanently
closed и идемпотентен: повтор не повторяет I/O. `isClosed` отражает lifecycle.
После close `record` и `useRequest` бросают fixed RuntimeException без записи
или reopen. `__serialize`/`__unserialize` всегда бросают тот же fixed exception
без изменения owner; clone недоступен. Destructor выполняет close fallback
и не выпускает исключение/вывод. Не создавать второго владельца того же handle.

## 5. Compatibility и production factory

Existing `AssignmentOrderOriginalFileSafeLog(string $file,string $request='')`
остаётся compatibility facade для текущих worker/public regression callers.
Он немедленно получает `AssignmentOrderOriginalOpenedSafeLog::open` и хранит
только typed owner; canonical/useRequest/record/destruction делегируют ему.
Facade не хранит raw handle/path и не содержит самостоятельного fopen/file/
fwrite/fstat implementation. Это не второй logger owner.

`ProductionAssignmentOrderOriginalFactory::create(mysqli,config)` связывает
нового opened owner непосредственно из unchanged config.safeLogFile до любых
database operations или privateStorageRoot validation/access. Неуспех acquisition
переводится в прежний exact
`AssignmentOrderOriginalProductionConfigurationUnavailable`: message равен
basename класса, code0, previous=null. Нет env/HTTP/CLI/global selector, extra
factory argument, fault injection или отдельной alternate opening path.

Worker/evidence-reader поля safeLogFile сохраняют отдельный уже утверждённый
serializable configuration/resource-order contract. Compatibility writer теперь
использует общий opened owner; evidence reader остаётся read-only и не становится
writer. Этот amendment не меняет его authorization/config или domain behavior.

## 6. Independently fixed examples

Pure positive:
`mode=0100600,uid=1200,device=7,inode=900,effectiveUid=1200,expectedDevice=7,expectedInode=900`
→ true. По одному изменению → false: mode0100640; type0040600; uid1201;
device8; inode901; expected effectiveUid1201. Root UID0 при actual/expected0
и остальных valid fields разрешён; это literal pure input, не privilege change.

Real owner fixture — task-owned ordinary file, созданный изначально0600,
с bytes `prior-line\n`. Request `00000000-0000-4000-8000-000000000001`;
`record('owner_probe',['phase'=>'positive'])` даёт exact final bytes:

```text
prior-line
{"correlationId":"11e594f48195","event":"owner_probe","safeFields":{"phase":"positive"},"sequence":2}
```

Каждая показанная строка заканчивается LF. Затем useRequest `...0002` и
record same event with phase `second` добавляют только:

```text
{"correlationId":"e79acd97ac88","event":"owner_probe","safeFields":{"phase":"second"},"sequence":3}
```

Close twice, then record → fixed failure, bytes unchanged, isClosed=true.
Serialization rejection не закрывает живого owner: последующий valid record
всё ещё возможен. Отдельный изначально0640 файл с fixed bytes отклоняется,
bytes/device/inode/UID/access-mode/mtime/ctime сохраняются; проверка не меняет
mode в validation/open interval. Missing path остаётся absent.

Новые real fixtures используют exclusive creation под нужным umask с restoration
исходного umask, без permission transitions, symlink races или observers.
Existing stable pathname/symlink/production-factory regression tests сохраняются
как уже одобренные controls; их expectations не ослабляются.

## 7. Mandatory proof split и gates

Gate2: explicit missing public owner/policy assertion RED, затем fixed pure
matrix, real valid/invalid acquisition, exact append/correlation/close и
compatibility/factory regressions. Fixtures целиком task-owned, primary bytes
остаются вне repository; cleanup проверяет exact identity и сохраняет external
decoys. Обычные исходно неправильные metadata — не временной mismatch fixture.

Gate3 обязан явно подтвердить предел evidence: stable-file black-box tests
**не различают** fstat и ошибочный повтор lstat. Они доказывают public policy,
ownership/append/closed-state behavior, но не источник metadata и не сами по
себе отсутствие retained native FD. Не заявлять обратное.

Gate5 дополняет behavioral GREEN exact structural proof на reviewed SHA:

- private acquisition делает real global fstat именно нового retained handle;
- descriptor mode/uid/dev/ino feed policy, expected identity приходит из final
  non-following pathname observation, effective UID из native effective-UID call;
- тот же handle передаётся только private constructor и используется для count,
  append и close; raw handle не выходит наружу и pathname не переоткрывается;
- every failure after open closes that handle before fixed redacted error;
- compatibility facade не содержит альтернативного open/write implementation;
- production factory получает owner до DB/private root и сохраняет fixed errors;
- clone/serialization/adoption не создают unvalidated writer.

Отсутствие этой source proof запрещает APPROVED даже при всех passing tests.
Такой proof split должен получить fresh independent Gate1 заранее; feasibility
review его не заменяет. Никакие ранее отвергнутые механизмы не реализуются.
После scoped owner Gate5 нужен independent combined original-command Gate5;
whole portal/CI/launch completion по-прежнему требует общей persistent goal.
