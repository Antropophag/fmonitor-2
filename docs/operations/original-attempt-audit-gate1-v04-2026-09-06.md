# Независимый Gate 1 rereview: ATTEMPT-AUDIT-001 v0.4

- Дата review: `2026-09-06`
- Reviewer: отдельно назначенный agent `/root/data_transport_gate1`
- Executable specification SHA-256: `898bb71819ea5d01faf305ce2c26ad1dae63e33081c01c15e0a61fff11b48bb4`
- Parent ORIGINAL-UPLOAD v73 SHA-256: `c19c9e99244ae75a4756c03042d2cb8dc8762127c063c35dd9cd3df9a1872624`
- OpenSpec proposal SHA-256: `4014c0e5940cb68779832a58453b55393de079d69e29862a9f859d6944924e4f`
- OpenSpec design SHA-256: `2d792344b28de4d0d718196fc3ff19ab10f0f6bf5c6dcc9a6b81e02924a888e0`
- OpenSpec tasks SHA-256: `4c32e827644d01a0123a309e65138e1982064cda576f420aa3ec03b3bfb68d9e`
- OpenSpec delta SHA-256: `9ebbc32f0c3c35177f95b831b0f154a0f69f5f1a44d4dad73c6fa973468d8c51`
- Probe SHA-256: `11e3110f9b9ae43278224809d9f844811da9c108374eb978e73dc74afc014a9c`
- Probe log SHA-256: `a5a3809f14ffef392cda02bdcda0151ba5a479fd9c9cf5aeca988d62fa2083cd`
- Verdict: **APPROVED**

Review ограничен v0.3→v0.4 section 11 и согласующими изменениями
proposal/design. Sections 1–10 и прежние Gate 1 verdicts не переоткрывались.

Измеренный blocker подтверждён: при prefix25 public original-v2 migration
доходит до native identifier limit, revisions table имеет ровно64 bytes, а
generated FK name превышает64; два maintenance table names имеют75 и73 bytes.
Это делает прежнее обещание prefix0..25 невыполнимым без physical-name policy.

Section 11 задаёт одну детерминированную и конструктивную mapping. Только два
maintenance logical suffix сокращаются и только когда prefix+logical превышает
64 bytes; при длине до64 и для остальных пяти tables физические имена прежние.
Prefix сохраняется byte-exact. Mapping зависит только от валидированного prefix и
logical name, не от существования table, config flag или ambient schema, поэтому
schema owner, metadata, maintenance, fixture и evidence consumer выбирают одно и
то же имя без probe/fallback или dual-write.

Boundary примеры согласованы с длинами: prefix15 сокращает requests, но оставляет
audits; prefix17 сокращает обе. При prefix25 новые aliases укладываются в лимит.
FK names также конструктивны и bounded: `fk_ao_` плюс48 lowercase hex от exact
prefix/NUL/logical-owner/NUL/local-column bytes. FK semantics и нормализованная
logical referenced-table metadata остаются прежними; допустимые существующие
generated FK names не требуют rename.

Compatibility задана fail closed. Existing v2 API/status/schemaVersion и короткие
physical names сохраняются. V3 использует тот же mapping и может распознать
leading partial после1059. Наличие одновременно canonical и дополнительного
physical alias даёт preflight conflict до DDL; implementation не выбирает table
по existence. Logical declarations, canonical JSON, rows, audit IDs и grants не
меняются.

Минимальный RED достаточен и не превращён в новую матрицу: public prefix25
currently-unavailable→APPLIED/repeat, identifier/FK inventory, duplicate/drift
negative, public maintenance/evidence smoke и четыре literal threshold pairs.
Он различает полный schema success, metadata-only успех и runtime fallback.

OpenSpec artifacts согласованы; strict validation проходит. Новых blockers,
product choices или public API нет. Исправление сохраняет prefix25 contract,
а не сужает его.

**APPROVED** разрешает naming/schema RED и независимый Gate 3 на этих exact
хешах. Решение не утверждает tests, implementation, Gate 5, combined command или
launch readiness.
