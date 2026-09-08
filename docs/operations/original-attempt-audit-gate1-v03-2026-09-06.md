# Независимый Gate 1 rereview: ATTEMPT-AUDIT-001 v0.3

- Дата review: `2026-09-06`
- Reviewer: отдельно назначенный agent `/root/data_transport_gate1`
- Executable specification SHA-256: `5dfdae5981df906c543d9591bb157d1ab5b94bb8d9e9c4f28619da638babb6e0`
- Parent ORIGINAL-UPLOAD v73 SHA-256: `c19c9e99244ae75a4756c03042d2cb8dc8762127c063c35dd9cd3df9a1872624`
- OpenSpec proposal SHA-256: `8b44832bf256da87738166fbfd0253808c8edb377c7e90bb539ccd815f23bd31`
- OpenSpec design SHA-256: `aff55554893d4d380772bdfbea818b26c7c1c84c3c811e4223216dffb62907e9`
- OpenSpec tasks SHA-256: `4c32e827644d01a0123a309e65138e1982064cda576f420aa3ec03b3bfb68d9e`
- OpenSpec delta SHA-256: `9ebbc32f0c3c35177f95b831b0f154a0f69f5f1a44d4dad73c6fa973468d8c51`
- Verdict: **APPROVED**

Review ограничен v0.2→v0.3 section 10 и согласующими правками proposal/design.
Sections 1–9, owner policy и APPROVED v0.2 review не переоткрывались.

Source dependency реальна: текущий общий `ProcessCapabilityChecksClassifier`
распознаёт только v3/v4. После установки original capability v5 canonical repeat
повторно запускает migrations 3/4 до 13 и без successor recognition получает
conflict либо downgrade. Поэтому read-only recognition v5 необходима для
достижения migration 13 и не является новым product behavior.

Successor задан однозначно: прежние columns/indexes/no-FK и engineer-position
CHECK сохраняются; capability CHECK ровно один, имеет exact name
`ck_fm2_process_user_capability_v5` и ровно шесть перечисленных literals без
duplicates/extra values или дополнительных CHECK. Classifier возвращает exact
`['state'=>'v5','capabilityConstraint'=>'ck_fm2_process_user_capability_v5']`.
Migration 3 принимает его как read-only repeat; migration 4 возвращает exact
`['applied'=>false,'schemaVersion'=>4,'constraintsChanged'=>[]]`. Ни одна из них
не делает DDL/DML, downgrade или grant. Их identities/order и остальные v3/v4
outcomes сохраняются.

Migration 13 не пропускается и остаётся единственным владельцем full original
family validation/setup. Exact v5 marker без original family не объявляет
readiness: migrations 3/4 проходят read-only, после чего 13 обязан выполнить
whole-family preflight/setup или fail closed.

Минимальный additional RED пропорционален зависимости: direct repeats 3/4 на
exact v5 сохраняют rows/catalog; замена одного нового literal и неправильное имя
v5 CHECK дают conflict без mutation; canonical clean→repeat through 13 доказывает
сквозной порядок. Этот набор чувствителен к пропуску migration, ложному marker
success и downgrade, не расширяя общую матрицу.

Противоречие прежней фразы «migrations1..12 не меняются» устранено точно:
identities/order сохраняются, меняется лишь recognition установленного successor.
OpenSpec согласован и strict validation проходит. Новых блокирующих
неоднозначностей, API или product choices нет.

**APPROVED** разрешает schema/new-recognition RED и независимый Gate 3 на этих
точных хешах. Решение не утверждает tests, implementation, Gate 5, combined
command или launch readiness.
