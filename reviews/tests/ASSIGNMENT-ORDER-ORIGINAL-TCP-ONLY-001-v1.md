# Независимый Gate 3 review: TCP-only localhost v1

- Дата review: `2026-09-06`
- Reviewer: отдельно назначенный agent `/root/data_transport_gate1`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001` v0.7, section 14
- Specification SHA-256: `30f402ceb136cd72fcc97801a28c51c9186ce78e8ea5454bf0e98ee272814bd9`
- Parent specification SHA-256: `461577954b8f89f07900e1b3ebb217ceec3f981421d6420f51e79d9c4f5f18ee`
- Test SHA-256: `4d40325189586ce53ae0b8678bb1ff661ba08a75787265551125ef6b1d8a8238`
- Child SHA-256: `1c86eadc43d62d467ab86917dffdb726a80b1eca602c58f4de6cbb2409bf67f6`
- Reviewed source HEAD: `8415d1f67aab360076644115ba5da0ee2101ad8a`
- Verdict: **APPROVED**

Reviewer не писал тест, child helper, спецификацию или production source.

## Review

Тест вызывает публичный
`AssignmentOrderOriginalMariaDbFreshTerminalReaderFactory::open()` и наблюдает
реальный внешний transport side effect. Он не вызывает private validator и не
зависит от планируемой реализации. Ожидаемое значение независимо следует из
v0.7: `localhost`, `LOCALHOST` и `LocalHost` должны вернуть typed `UNAVAILABLE`
с нулём Unix-socket connections. Отдельный direct-mysqli control на том же
listener ожидает ровно одно соединение и тем самым исключает неработающую
ловушку как ложный GREEN.

Fixture изолирован: canonical `realpath('/tmp')`, новый короткий random
task-owned root mode0700, отдельный synthetic password file mode0600 и новый
Unix socket. System socket и реальные credentials не используются. Child
проверяет точное значение `mysqli.default_socket`; listener не посылает MySQL
greeting и сразу закрывает accepted connection. Child stdout/stderr ограничены
и сверяются с фиксированным протоколом без native diagnostics, paths или
credential bytes.

Ожидание child, pipes и listener выполняется одним `stream_select` loop с
монотонным пятисекундным deadline. Timeout приводит к bounded SIGKILL/reap.
Listener, pipes/process, password, socket и root освобождаются в `finally`; перед
удалением проверяется сохранённая `(dev, ino, mode)` identity и порядок удаления
идёт от socket/file к root. Sleep, interception, permission failure и global
runtime mutation процесса теста отсутствуют.

Сохранённый RED:

- archive: `/Users/antropophag/.local/state/fmonitor2-verification/original-tcp-red-eg6kpnwq`
- `evidence.json` SHA-256: `f6ccd3fdad8fc4f4e694083aa766c5b7bd55bd3f143e6ff1066811f4ea9c4425`
- `red.log` SHA-256: `646a2ac05b60c7b68fee4b337124151a8c9d1662f960be593782bce3fc60df9a`
- exit: `255`

Direct sensitivity даёт `accepts=1`. Все три factory variants дают
`accepts=1` вместо independently expected `0`, после чего падает только итоговая
TCP-only assertion. Setup и typed `UNAVAILABLE` проходят. Ранее исправленная
duplicate-require диагностика не является частью retained RED и не представлена
как отсутствие поведения.

**APPROVED** разрешает минимальный GREEN для public fresh-reader factory на этих
точных хешах. Review не утверждает worker-specific покрытие, production code,
Gate 5 или общий DATA-INTEGRITY пакет.

