# Независимый Gate 3 review: MAINTENANCE storage v1

- Дата review: `2026-09-06`
- Reviewer: отдельно назначенный agent `/root/data_transport_gate1`
- Reviewed source HEAD: `04efeab79ac59f5cd41282339f438f9f2d0485b2`
- Specification SHA-256: `d4712f8e82cc7c2f9865bd61924ef24b0cd2640ddbbde3d96be4f5dc9cc7c72e`
- Test SHA-256: `c59518137515eac17fb33c5e25039d03203c56e99340dd856528fb29d7e6dd7b`
- Verdict: **CHANGES_REQUESTED**

Existing-stage control проходит. Остальные десять failures точно показывают
отсутствующие public storage factory/list/lock/delete, active stage/content lease
exclusion, foreign lock ownership, configured principal, authorization config,
events и stale-page metadata defense.

Блокирующий пробел: `actual-storage-events` проверяет только успешный observer.
Specification требует exact Throwable behavior для DIGEST_LOCK_ACQUIRED и
DELETE_BEGIN (operation failed+cleanup) и для DELETE_DONE после уже выполненного
delete (не отменять/не повторять удаление). Текущий набор пропустит реализацию,
которая выбрасывает callback Throwable наружу, неверно классифицирует operation
или повторяет irreversible delete. Нужны точечные throwing-observer cases с
before/after inventory и once-only event/lock release; новая матрица не нужна.

После correction требуется новый exact-hash Gate 3 rereview. Остальные storage
oracles одобрены в фактической области.

