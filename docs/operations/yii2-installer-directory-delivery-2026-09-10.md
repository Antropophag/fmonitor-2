# №76 — справочник монтажников Yii2

Начато 2026-09-10 по прямому поручению владельца работать автономно до PR,
готового к merge. Root — scope, normative spec и tests; отдельный
`gpt-5.6-sol/low` executor — production implementation; независимые
`gpt-5.6-sol/low` reviewers — Gates 3/5. Автономное делегирование spec/tests не
используется.

[Контракт](../../specs/YII2-INSTALLER-DIRECTORY-001.md),
[OpenSpec](../../openspec/changes/yii2-installer-directory/). Base до RED:
`aa4d20f30edb4b5e9c7de7abad8ac2d016ec4952`. Предыдущий documentary slice
поставлен PR #91; общий #76 и stand cutover остаются открытыми.

## Gate 3 и реализация

Gate 3 после четырёх полных возвратов APPROVED на source
`6bcabe783fe105cad70cf5fe7d880772d14887369564468f4e064dbcde10f87e`, package
`/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T204608Z-dc50eccc7a/`.
Все возвраты и dispositions сохранены append-only в review record. После начала
Gate 4 три test-helper несогласованности (raw URL/query counter, denied actor,
canonical CHECK fixture) исправлены root; независимые helper-delta reviews
APPROVED без изменения нормативных expectations.

Executor `/root/installer_executor` реализовал ровно пять planned production
paths: Yii DAO query, controller, view, asset bundle и URL/config boundary.
Root exact-source focused GREEN records: HTTP
`1789076525960011000-e8d2e73d6c5146ccb53ca63f4b166843`, browser
`1789076535179200000-bc8789cf820441b7a8e9f9c68324912d`, inventory
`1789076544202477000-8938706f3b0d4a289def43fa6cdb3d8f`, architecture guard
`1789076562007528000-0140be052aee4a579dcedff868453c1a`, CI roster
`1789076588075022000-f190de07750b4d43902a625d2a42eb99`, change verification
`1789076620643232000-47cef9d924414fddb0065af3fb97e820`, architecture-check
`1789076643080325000-f47bba729ada4d04a5e9ddafdc2ea4ef`.

Дополнительный local `make test` был запущен напрямую, поэтому parent harness
record отсутствует; root остановил его SIGINT/exit 130 после unit GREEN и длинного
DB этапа, чтобы не дублировать обязательный full CI. Child tests напечатали PASS,
но получили harness `UNKNOWN` из-за source drift; они остаются non-GREEN:
`1789075314701959000-379eb1b0ebbb4584aa0aa10cc32c3a74` original admission;
`1789075343047849000-36e899138ac64bc49786743db0cb3cdd` original permissions;
`1789075572559269000-8e87026a9d4348efaaa5929f3f53a6b6` selection transaction;
`1789075592102107000-58b8e1c586894080bd28d2fc9d081a72` original data writes;
`1789075602417964000-34970df03e504d0aa7df2d1b03f5e07e` database setup;
`1789075632817071000-26a7d49dd3a34f87a7482f1ad88c365f` evidence lifecycle;
`1789075640527375000-35071d78a833489296cb858cdbe22b14` evidence reader;
`1789075641488229000-e2f2ac67ba344c7f90a352010829c530` Gate5 MariaDB RED;
`1789075642210111000-aceb6dc6b8f9461dac04304f610e5211` lease race;
`1789075645667204000-210fb0c3469d4269bcba207e499b55e8` maintenance;
`1789075646651701000-9bba23ef7a7348f69b05117402174829` maintenance repository;
`1789075656170329000-f5869d0fd70a4eaa947f228e7037df43` orphan fixture;
`1789076345445123000-faa10838099e47219f050f67f5f6cd58` Yii user access;
`1789076351711628000-76ab1888fb9a44318bc9738373b3940f` user access concurrency;
`1789076355244673000-4287f5adbf344696b34bd5ef4bc1d759` user access edges;
`1789076361084742000-8a523d9c30e246ee9e29e6f1f3054258` object queue.

Затем test-owned `.test-artifacts/pha-f8e8634b5047/mutable/unreadable/shlz.css`
временно стал нечитаемым; harness `source_details` получил `PermissionError`, и
records не были созданы для selection picker view, selection unknown employment,
template generation/boundaries, artifact store, assignment-order identity registry
и concurrency/recovery/schema, original attempt audit MariaDB/schema, original
audit schema literal, original capability migration, original data MariaDB
composition/fresh. Artifact исчез при teardown; следующий original data reads и
дальнейшие показанные DB tests снова были GREEN. Cascade не исправлялся product
кодом и не считается разрешённым full run. Повтор local full не выполняется: один
full exact-source run остаётся для CI после Gate 5; все перечисленные UNKNOWN
сохраняются как non-GREEN до него.

## Gate 5 candidate

Независимый reviewer `/root/installer_gate5` после одного возврата APPROVED exact
source `4875c8e9fa33e8192ecfad792f6137d21edd625f62c57cb9a01efa423c02510b`, package
`/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T215322Z-dc84c39b40/`,
snapshot patch SHA-256
`5a8cea2becf1f04abe61bc1a9824200b7ddaf8e1addc608281267e64b438d65e`.
Findings отсутствуют. После snapshot добавлены только Gate 5 review appendix,
этот delivery appendix и task-state metadata; production/tests bytes должны
совпасть с reviewed snapshot. PR/full CI ещё UNKNOWN.
