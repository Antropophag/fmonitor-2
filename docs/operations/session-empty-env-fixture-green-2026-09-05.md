# Faithful empty-session-root fixture — host/image GREEN

Дата: 2026-09-05. Автор patch/применение: `/root`.
Gate1/Gate3 и exact proposed patch зафиксированы commit `691abfd`.
Применён только reviewed patch
`02f590c9d8127486d6ac801f83d32d1d9c424b3e2cb03f2f8caaeb080336eb53`.

Host команда `php tests/InstallationProcess/pilot_session_storage_protocol_001_test.php`
дала exit0 и прежний `PASS: PILOT-SESSION-STORAGE-001 raw HTTP protocol tracer`.
Новый setup probe подтвердил present-empty value до прежних HTTP assertions.
Final test SHA-256:
`fef3f2c0b5308d9b445e2e3cc023321606f72cca9b19a1d1b36a4dbc6d40c174`.

## Unprivileged current-image evidence

Exact production image:
`sha256:b98963779a006f167986f082f7c7ff78f28e9fc4e30d66a6e11f9d8ecb7613d8`.
Все382 включённых production source files совпадают с checked SHA `af75b20` и
текущими source bytes; production не менялась. Tests смонтированы readonly,
их exact input hashes записаны в `session-final-inputs.json`.
UID внутри test container —10001. HTTP test listeners не публиковались на host.

Для DB-dependent tests создан отдельный internal Docker network и MariaDB с
task-owned tmpfs, без published ports. Подготовка настоящим canonical CLI дала
exact version12/appliedVersions `[1,2,3,4,5,6,7,8,9,10,11,12]`. Это private
test DB, не существующий demo или source.

Полный single-run corpus:24 existing `pilot_session_storage_*_001_test.php` и
codec/request-owner test —25/25 exit0, `SESSION_IMAGE_FAILURE_COUNT=0`.
Каждый child имел timeout90s и bounded forced termination; ни один timeout
не сработал. Test container завершён и автоматически удалён.

## Предыдущие failures не скрыты

- Initial network-none image run:19/25 PASS, пять missing-DB connection setup
  failures, плюс protocol input-transport failure (expected503/actual200).
- Следующий DB-connected run пяти cases:3 PASS, два missing canonical-table
  setup failures, поскольку первоначально пустой private DB ещё не мигрировал.
- После штатной canonical preparation и reviewed fixture correction полный
  corpus25/25 PASS. Никаких failures/skips/xfails не разрешено и tests не удалены.
- Clean Compose startup по-прежнему FAIL по отдельным generation/legacy setup
  prerequisites; session cookie stop/start этим image corpus не доказан.

## Raw evidence и checks

Private archive:
`/Users/antropophag/.local/state/fmonitor2-verification/session-compose-uz0c2i_b`.

```text
29cf45eff452f7ff67095e6aea353d33f8a150a3b2cefbda41e3ee1e801e3d6b  protocol-host-green.log
d0fd77d84b85f74a8c2f5f8a67c166878a47295b4058afb8809f9d4616384bdc  session-image-final.log
```

PHP lint и git diff-check PASS. Fresh independent Gate5 требуется для
test-only correction. Scope не включает production, original safe-log,
protected E2E или новый data-import authority. Пользователь сообщил о VPN;
это observation о доступности будущего канала, не использованный source access
и не ответ на ранее отправленный exact importer Gate1 request.
