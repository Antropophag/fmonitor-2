# ASSIGNMENT-ORDER-ORIGINAL-WORKER-BOUNDARY-001

Версия0.1. Corrective contract для трёх findings combined Gate5v1.

## Простыми словами

Проверочный worker заранее отклоняет неверную конфигурацию, не зависает на
незакрытом command/barrier socket и проверяет размер полного ответа до записи.
Пользовательский workflow и результаты корректных команд не меняются.

## 1. Scope и ordering

Наследуется parent ORIGINAL-UPLOADv74 section16 и WORKER-PORTS001.
После FD admission и exact JSON keys/typed WorkerConfig выполняются все прежние
DSN/host/port/database/user/prefix, UTC clock, ID sequences, inspector/fault/barrier
и canonical path/metadata checks. Password/root/log paths outside repo без links,
UID/mode/type exact по existing contracts; protected root parent policy прежняя.
Safe-log metadata проверяется без открытия/чтения log, password contents не
читаются. Только после этого потребляется command. Invalid config даёт прежний
exit70, fixed stderr, empty result/barrier, даже если command peer не прислал
ничего и остаётся открытым. Config-file read failure также не печатает warnings.

## 2. Command и barrier deadlines

Command buffer никогда не превышает29000000bytes; reads<=65536. После первого
LF, который обязан быть последним buffered byte, worker требует EOF без extra
byte максимум через5 monotonic seconds. Этот EOF deadline начинается при LF;
новый срок на первоначальную доставку строки до LF не вводится. EOF before LF,
extra bytes или timeout → controlled exit70 без result/barrier/secret/DB access.

После exact READY line+flush barrier ждёт exact RELEASE line максимум5 monotonic
seconds от READY. Wrong line/EOF/timeout фиксируется в observer как failure;
application выполняет свой normal failure cleanup, но worker не публикует Result
и выходит70 с одним fixed stderr и только уже записанным READY. Domain commit
не выполняется. Ошибка observer не превращается в обычный exit0 FAILED Result.
Все acquired FD wrappers закрываются once; родители bounds/reap сохраняются.

## 3. Наблюдаемый native result encoding

Новый public pure verification port:
final AssignmentOrderOriginalWorkerResultEncoder::encode(
    AssignmentOrderOriginalResult $result
): string.

Он сериализует те же11 exact keys с теми же JSON flags и final LF, затем проверяет
strlen<=16384 до возврата. Invalid UTF8/getter/serialization/oversize Throwable
даёт только AssignmentOrderOriginalWorkerEncodingUnavailable, final RuntimeException
с message `Assignment-order original worker encoding unavailable.`, code0,
previous=null. Он не валидирует domain IDs заново и не владеет FD/SQL/write.
Worker получает готовую line только через этот port до своего sole fwrite;
ошибка encoder даёт прежний exit70/zero result bytes. Existing verifier result
fault scripts и response-loss retry semantics сохраняются. Публичный port нужен
для RED actual16384/16385 condition без private reflection или выдуманного
oversized accepted domain fact; source Gate5 подтверждает actual worker wiring.

## 4. Минимальный RED и Done

Reuse existing owned four-socket worker fixture. Test-only optional controls
оставляют command writer открытым после LF либо не посылают command; production
не получает test selectors. Valid control проходит; command EOF/barrier timeout
наблюдаются в child exit70 примерно через5s, bounded parent watchdog завершает
старую зависшую реализацию и не считается child deadline. Invalid configured
resource отклоняется без command input. Cases используют initially-owned files,
без permission transitions/native interception или real secrets.

Pure encoder literal valid line, exact16384/16385 byte boundary и invalid UTF8;
worker regressions сохраняют one-write/zero-or-short-output/replay. Exact helper
patch входит в Gate3 до GREEN. Existing approved worker/frame/FD tests неизменны.
Scope не включает новые FD kinds, global harness tuning, HTTP/selection/opening.
Gate1→RED→independent Gate3→minimal GREEN→regressions→independent combined Gate5.
