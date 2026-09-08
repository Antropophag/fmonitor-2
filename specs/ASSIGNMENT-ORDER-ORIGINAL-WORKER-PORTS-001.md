# ASSIGNMENT-ORDER-ORIGINAL-WORKER-PORTS-001

Версия0.1. Техническое уточнение approved ORIGINAL-UPLOADv74 sections7/16.

## Простыми словами

Проверочный worker использует уже обещанные имена параметров и неизменяемую
конфигурацию. PDF декодируется из base64 один раз через объявленный stream port.
Три предусмотренных отказа чтения действительно достигают repository, чтобы
проверка ошибок не зависела от случайного сбоя БД. Пользовательские команды и
результаты успешной загрузки не меняются.

## 1. Exact API

Наследуются parent declarations: interface ByteStreamFactory с
fromBase64(string $base64): AssignmentOrderOriginalByteStream; final readonly
WorkerConfig с12 exact properties/types/parameter names; final WorkerBootstrap
run(configJsonPath,commandReadFd,barrierReadFd,barrierWriteFd,resultWriteFd):int.
Имена имеют полный prefix AssignmentOrderOriginal, namespace прежний.
Worker реально строит этот DTO после exact JSON key/type admission.

Concrete public port для constructible tests:
final AssignmentOrderOriginalBase64StreamFactory implements ByteStreamFactory,
constructor(?AssignmentOrderOriginalWorkerFaults $faults=null). fromBase64 имеет
exact interface signature и возвращает прежний MemoryStream с этим faults.
Это verification composition, не runtime production selector.

## 2. Strict decoder

До decode проверяется canonical standard alphabet/padding (empty допустим).
Единственный base64_decode(...,true) находится в factory; exact re-encode equality
отвергает nonzero unused padding bits. Невалидные bytes дают InvalidArgumentException
с fixed message `Invalid worker base64.`, code0, previous=null. Worker переводит
это в прежний exit70/fixed stderr/empty result contract до secret/DB/barrier.
Размер20MiB+1 остаётся transport-valid и достигает application FILE_TOO_LARGE.
Bootstrap не удерживает decoded string вне stream и не декодирует для precheck.
Existing stream-read/close fault behavior сохраняется на возвращённом stream.

## 3. Lookup fault points

Добавляются exact declared enum cases REQUEST_LOOKUP=request_lookup,
FINGERPRINT_LOOKUP=fingerprint_lookup, LINEAGE_LOOKUP=lineage_lookup.
Existing WorkerFaults target выбирает один point. Native repository вызывает
before(point) один раз после input validation, до SQL/observer, для request,
fingerprint и каждого root/assignment/revision lineage read соответственно.
Fault Throwable возвращает typed UNAVAILABLE без result/metadata/SQL; unrelated
point не влияет на read. Reference lookup и commits этим patch не меняются.
Production по-прежнему не выбирает verifier faults из request/env/global.

## 4. Проверка и scope

Literal DTO/reflection и named construction; direct declared stream port читает
known decoded bytes/EOF, empty и20MiB+1, rejects whitespace/URL/bad-padding/nonzero
padding bits; stream faults действуют. Zero-SQL public repository fault reads и
native no-fault/unrelated controls. Existing worker protocol/transport/lease и
repository read regressions подтверждают прежние outcomes. Source Gate5 доказывает
sole decoder/no retained extra copy и actual DTO/point wiring.

Этот пакет не утверждает новый framing/FD lifecycle algorithm или полноту всего
worker contract; оставшиеся фактические mismatches требуют отдельных gates.
Orphan fixture behavior, combined command, HTTP/selection/opening отдельно.
Gate1→RED→independent Gate3→minimal GREEN→regressions/architecture→Gate5 обязательны.
