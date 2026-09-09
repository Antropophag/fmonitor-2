# Runtime recovery and update — 2026-09-09

Technical issue36 candidate supports coordinated backup/restore of canonical v23
and preserves the historical v22 contract. Production code was independently
reviewed; executable and isolated operational proofs below are complete within
their stated bounds. The runbook is independently reviewed and operationally cross-checked. Full CI
remains pending.

## Source and image

Runtime source: `e5d1b34e2f902b9aec9a99a00c632e82b21fef5b`.
Image: `sha256:82c0a8214b35ab80de1c036f9a91a2c9451909f473c3202426e89a08c8a158cb`; OCI revision equals that source.
Later commit `edb0f48b63f2604839d5b705bb83cceb7a8bab69` changes only test-container
networking and review evidence; runtime files are identical to the image source.

Historical source `f22d80a609d52a194c1fd68b1db7ab7273740f28` retains v22 recovery
and its unchanged executable oracle. The current recovery image accepts only exact
v23 metadata:69 canonical base tables,39 AUTO_INCREMENT families and deferred[].

## Coordinated isolated restore

The source and target projects were new disposable test contours. Source scheduler,
worker and web/php were stopped in order. The bundle preserved completed and unknown
leased Jobs states seeded through public Jobs operations; causal active-child signal
behavior is separately covered by the approved worker process tests.

Backup used the runtime DB identity; restore used a separate migration identity to
create canonical schema in an empty target. Standard data-only dump/import and exact
source-image migrations avoid the historical DDL roundtrip implicit-index mismatch.
Restore changes no Jobs/outbox state, starts no handler and performs no transport.

Bundle manifest SHA256: `3d6c1158778b43754ad60d2e60b21649d69d9291d31559a304e8d37cb1973200`.
Database dump SHA256: `e430290db98c6eb0f38e0238e648eae7d1579d2dbc97841ff8c97aad161580c0`.
State archive SHA256: `73b08684dd1896e124acc1150e86e82c5c0104adad3ca76302fc183489565cd3`.

Backup CLI elapsed 1.190s; restore CLI elapsed
1.787s. These timings exclude surrounding operator work,
container startup and acceptance checks; they are not an RTO commitment.
Exact pre-resume source/target DB dump and state inventories matched. All39 counters
also passed the executable roundtrip, including an allocated/deleted high Jobs ID.

The restored runtime passed schema/storage readiness while JobsHealth honestly
reported stale role heartbeats. Preserved owner session read progress100 and private
original327; restored OTIZ returned one object and XLSX7364. Explicit DML-only fake
resume settled two fixture jobs, swept one pending intent and made one fake delivery.
No live Bitrix/email requests occurred.

## Update and compatibility

The forward executable restores exact v22, proves old63-table rows/state/35 counters
unchanged through additive migration23, then exercises v23 backup and verifies old
v22 tooling rejects its bundle before mutation. There is no schema downgrade.

An HTTP-only rollback on the new target used historical image
`sha256:8acc52ff74b6004cc0a3c7a25eeede62fe286a492a0801af339d52547fe78d93`
with Jobs stopped and without migrations. It preserved owner PDF/session,
checklist revision58/seven photos and authenticated OTIZ access. After the final return to the current image, the existing owner cookie again read
progress100/PDF327. Engineer state had revision58/seven photos; three new public
commands returned200 and produced revision61/41 items/seven photos. Every earlier
checklist operation and installer attribution, and every pre-update state file/hash,
remained intact.
This proves only the specifically exercised HTTP compatibility. Full contour/Jobs
rollback with pending, leased or ambiguous work remains unsupported.

## Evidence and remaining work

Private primary evidence: `/tmp/fmonitor2-restore36-v23-drill.BxOeX7`, root0700,
files0600. Public reports contain only synthetic counts, hashes and outcomes.
Executable evidence: `/tmp/fmonitor-recovery-portability/results.json` (all3 PASS),
`/tmp/fmonitor-jobs-recovery-forward-green.log`, and
`/tmp/fmonitor-v23-recovery-final-green.log`. Architecture7 rules passed.

Linux test launchers use host networking to reach the loopback-only test DB;
Darwin uses its existing host alias. The DB bind was not widened. The exact archived
v22 tests and application remain version-bound, and task-owned image cleanup runs
before propagating test status.

Target http://127.0.0.1:18196/ is retained for review. Primary8092, older8093,
reviewed8095 and older restore18094 were untouched. The task-owned source DB is stopped with volumes retained; target18196 is retained.
Authoritative CI remains before Done.
Retention, RPO and RTO remain owner decisions; this technical proof chooses none.


## Завершение после рестарта — 2026-09-09

PR65 https://github.com/Antropophag/fmonitor-2/pull/65 штатно MERGED
2026-09-09T07:57:44Z, merge `41e394986f006f3a18beea39d7b19ea97d7f01ce`.
Issue36 CLOSED. Уже запущенный Actions34325719485 завершился SUCCESS:
все8 jobs GREEN, literal VERIFY_OK на exact head
`81c38b6901303a82d0bc7f966954e1ab0c2925c5`. Полный лог сохранён вне repo:
`/tmp/pr65-81c38b69-green.log`. Повторный полный прогон не запускался.

Оба OpenSpec change синхронизированы с main specs и архивированы после merge;
strict validation:10 specs PASS, оба change PASS перед архивированием.
Архивные metadata сохраняются отдельным локальным commit после проверенного PR65;
это не дополнительная публикация в main. Reviews остаются неизменными.
Никаких runtime/DB/volume изменений в этой сессии не выполнялось.
Retention/RPO/RTO не утверждены; новая автономная очередь не запускалась.
Goal API после рестарта вернул null; активная либо завершённая глобальная Goal
не заявляется. Следующие технические срезы ждут нового поручения владельца.
