# HARNESS-OTIZ-CANONICAL-V12-001 v0.1

Статус: DRAFT / TECHNICAL_GATE_1_PENDING. Дата: 2026-09-05.
Supporting verification amendment под parent OpenSpec
`canonicalize-object-detail-snapshot-schema`, tasks4.1/4.2.

## Простыми словами

Проверка изоляции ОТиЗ должна запускаться после штатных миграций и сохранять
уже существующие таблицы, включая две новые таблицы сведений об объектах.
Вместо допуска произвольного списка версий проверка потребует точный результат
повторной миграции: версия 12, список изменений пуст. Финансовые примеры и
правила ОТиЗ не меняются.

## Actor, seam and inherited authority

Actor — developer/CI, public seam:
`php tests/Verification/harness_otiz_canonical_compat_001_test.php`.
Внутренние child seams остаются настоящими `make migrate` и
`php tests/Verification/harness_otiz_isolation_001_test.php`.
Inherited contract — `HARNESS-OTIZ-CANONICAL-COMPAT-001` v0.1: запуск только
после successful canonical migration, preservation pre-existing schema/rows,
две одинаковые изолированные проверки, deterministic injected failure cleanup.
v12 authority — approved `OBJECT-DETAIL-SNAPSHOT-SCHEMA-001` v0.4 и owner
decision `e8f17b63a3c93e8f4be5664c309b435fde3318e9`.

Новый продуктовый/финансовый outcome не вводится; нужен technical Gate 1,
не повторное согласование schema ownership владельцем.

## Preconditions and exact result

Caller до invocation устанавливает полную canonical v12 schema штатным
deployment seam. Это существующая precondition canonical compatibility
contract; harness не предназначен для запуска вместо первого deployment.
База только configured disposable test DB, synthetic existing rows сохраняются.

Первый existing `make migrate` внутри harness теперь является exact repeat
проверкой этого prerequisite. Требуется exit0, empty stderr и ровно одна JSON
строка плюс LF:
`{"ok":true,"schemaVersion":12,"appliedVersions":[]}`.
Весь process result (status/stdout/stderr) сравнивается с exact literal, без
extra lines/keys и без извлечения только последней строки как достаточного proof.
Непустой appliedVersions, missing/extra key, иной version, not-ok или process
failure — existing SETUP_FAILURE: caller не доказал готовый predecessor.
Arbitrary subset/range acceptance удаляется. Failure не переводится в skip.
Этот contract не расширяет schema repair и не разрешает создание domain facts.

После успешного prerequisite harness наблюдает и сохраняет две дополнительные
canonical tables:
`fm2_pilot_object_details`, `fm2_pilot_object_detail_quarantine`.
Они создаются только canonical v12. Harness не вставляет в них новые строки,
не изменяет hash/payload, не удаляет и не ремонтирует их. Их ordered rows и
SHOW CREATE definition включаются в existing before/after snapshots для двух
успешных runs, injected failure и final cleanup. Существующий auto-increment
restore allowlist не расширяется: у двух tables нет auto-increment.

Все имеющиеся sentinel values, финансовый transcript, count/permission/cleanup
assertions и исходные protected E2E dependencies остаются неизменными.
Изменяются только exact migration prerequisite, две наблюдаемые table identities
и version labels including final summary `v1-v12` вместо `v1-v11`.
Нет изменений production code, importer, users или real/production data.

## Rejected cases and evidence

Missing/unreadable canonical table — существующий SETUP_FAILURE. Изменение
schema/rows/counters либо иной child transcript — REGRESSION_FAILURE по existing
assertions. Controlled post-fixture failure по-прежнему обязан дать exact
`REGRESSION_FAILURE: injected after fixtures` плюс LF и восстановить все facts.
Подавление failures, broad version match и изменение финрасчётов запрещены.

## Delivery gates

После technical Gate 1 сохранить focused old-harness RED на independently
prepared v12 с exact no-op CLI proof. Root baseline already records
expected11/actual12; свежий focused run фиксирует healthy predecessor после
approval. Подготовить unapplied exact proposed patch; fresh independent Gate 3
проверяет spec/RED/patch, прежде чем patch применяется.
Затем minimal test-only GREEN через real harness и two-run/injected-failure
checks, relevant characterization, architecture/lint/diff. Fresh Gate 5 pin-ит
точный patch/spec/result и confirms unchanged finance/preservation logic.
Parent/goal требуют отдельного full make verify; эта поправка не объявляет
остальные blockers закрытыми.
