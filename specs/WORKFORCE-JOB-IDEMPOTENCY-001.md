# WORKFORCE-JOB-IDEMPOTENCY-001 — native sync retries for durable jobs

## Простыми словами

Job worker вызывает существующего владельца кадровой синхронизации. Повтор после
потери ответа не fetch-ит и не публикует второй раз; новая attempt после записанного
сбоя получает отдельный run, но остаётся частью того же job.

## Public seam

`MariaDbWorkforceSynchronization::runForJob(BitrixWorkforceDeliveryClient delivery,
string jobIdentity, int attempt, ?string observedAt): array`.

Job identity — UUIDv4, attempt 1..5. Attempt1 использует jobIdentity как run id.
Attempts2..5 получают deterministic UUID из SHA-256 namespace
`workforce-job-attempt-v1\0<jobIdentity>\0<decimal-attempt>`: взять первые 16 bytes
digest, у byte6 сохранить low nibble и поставить `0x40`, у byte8 сохранить low 6 bits
и поставить `0x80`, затем canonical lowercase `8-4-4-4-12` hex. Mapping pure/stable
и не зависит от process/time. Для identity `018f47ba-2f6d-4f80-8f42-0b37657fd911`:
attempt1 равен identity, attempt2 равен `0e3186a8-727a-4d2a-8d88-e57a7244e15c`.

## Behavior

Один существующий workforce sync advisory lock SHALL охватывать lookup всех пяти
member run ids и существующий fetch/normalization/publication owner. До fetch owner
ищет любой completed member; если найден, возвращает его durable result без transport.
Иначе exact current attempt failed row возвращает тот же safe failure без transport.
Отсутствующая current attempt вызывает только существующий execute с derived run id.

Failure attempt1, затем attempt2 success сохраняют две immutable run rows. Повтор
attempt2 и поздний вызов old attempt1 возвращают completed attempt2 result без fetch
или новых observations. Concurrent call при занятом existing sync lock возвращает
`SYNC_ALREADY_RUNNING`, не читает transport и не меняет facts. `run()` остается
совместимым; normalization/publication implementation не дублируется в Jobs.

Tests используют fake delivery only. Real Bitrix не вызывается.
