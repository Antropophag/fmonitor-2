# №76 — документарное закрытие Yii2

Начато 2026-09-10 18:33 UTC по прямому поручению владельца продолжить №76 после merged PR89. База ee8fade4e240f7ad5886be616e89921954e36a20, worktree `/Users/antropophag/code/fmonitor-2-yii2-documentary-76`, branch `codex/yii2-documentary-76-20260910`.

Root автор спецификации/tests; `/root/documentary_executor` sol/low — реализация; `/root/documentary_gate3` sol/low — независимый reviewer. Автономное делегирование spec/tests не использовалось.

Контракт [YII2-DOCUMENTARY-CLOSURE-001](../../specs/YII2-DOCUMENTARY-CLOSURE-001.md), [OpenSpec](../../openspec/changes/yii2-documentary-closure/), [Gate3 record](../../reviews/tests/YII2-DOCUMENTARY-CLOSURE-001.md).

Gate3 APPROVED: source42e0cf30ea1f855ea9d6cf37d9b8d47a0c0e12c6a089f236fe908940db7c5c92, package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T185429Z-08b52b6b9c/`, patch3fa2b07… (полный digest в review). Три exact-source RED: URL404 при существующем открытом объекте, concurrent404, отсутствующая browser PTO form. Production в этом snapshot не менялся.

Два возврата Gate3: первый — четыре группы пробелов inventory/cross-race/read+arrays/history+browser; второй — XPath consistency и PTO form/vertical bounds. После второго root заново сверил целую A1–A7 матрицу. Список и dispositions сохранены append-only в review. Initial runner вызовы без `--intended-red` записаны REGRESSION_FAILURE; повтор с явным marker дал INTENDED_RED, предыдущие записи не переклассифицированы. Inventory initial failure исправлен добавлением exact2db+1e2e members без изменения исторических hashes; исправленный inventory GREEN1789066192201132000-e39114f8955e4ac3b2570f6afcc0bc4d. Existing completion owner baseline GREEN1789065916226719000-5ae68180c06b4a56bb83dda91aeb233b.

Реализация/GREEN/Gate5/CI/PR пока не завершены. Token/cost telemetry UNKNOWN. Стенд, общие контейнеры и чужой WIP сохранены; новая canonical fixture использует приватные БД на существующем test-db. Composer восстановлен pinned setup script в ignored vendor; harness doctor CONFIGURED в новом worktree. Migration frontier не меняется. #76 и общий cutover остаются открытыми.

## Проверка реализации и корректировка test helpers

HTTP и все четыре held-lock races GREEN. Первые HTTP/browser503 были вызваны отсутствием completion caps в allowlist общего authorizer; использован существующий exact identity store grant seam без нового SQL authority. Root поправил browser boolean-details/visible-link helper и fixture FKR checklist.read в соответствии с LocalRoleCatalog; независимый TEST-HELPER delta APPROVED, см. appendix Gate3. RED повторён на восстановленном preimplementation snapshot, где новые helper bytes совпадают с кандидатом. Нормативные expectations не ослаблены.

Полный локальный focused diagnostic: 7/8 GREEN, единственный browser failure — реальный `inert` на всём readonly checklist запрещает возврат ФКР к документам (record1789067462264435000-98d66fb161b346868d887c20895a1b23). Adjacent card/queue/inspection GREEN; отдельный make architecture-check выявил controller152lines, выше hotspot limit. Кандидат исправляется выделением формы и переносом inert только на рабочие разделы; никакого blanket permission расширения. Prior raw failures retained.

## Кандидат для PR

Финальный Gate5 delta APPROVED: snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T192855Z-c579c06440/snapshot`, base ee8fade4e240f7ad5886be616e89921954e36a20, patch SHA-256 `1ba8de8b2dd85ce56437bc1a06ac73619f1901066442709db7cb1563b3816cf7`, source `77cfb37c41a748ca01e61e01dc46fc70189b8e3b011e8de9fc65c37e58e31bee`. [Code review](../../reviews/code/YII2-DOCUMENTARY-CLOSURE-001.md).

Exact-source GREEN: HTTP1789068471692036000-9e3a240b113d46588fee95da0e7fbfa9; concurrency1789068471709745000-1139b4831af84fa7ac81b6706cd3b061; browser1789068471694895000-9f29ea5cd992438ca80ba58200c40d31; architecture1789068472982793000-7a5a66fc93c94b5394647a2121ca5e36. Все records находятся вне repo в `~/.local/share/fmonitor-2/delivery-harness/records/`.

Дополнительно root обнаружил пропущенное условие working для UI corrections после первоначального Gate5: отдельный A4 intendedRED, independent Gate3 delta APPROVED, минимальный persisted-state флаг, повтор3focused и Gate5 delta APPROVED. Новых продуктовых правил нет. Все prior approvals source-specific, failures не удалены. Код/тесты финального коммита должны совпасть с этим snapshot; после snapshot добавляются только code-review appendix, tasks и этот delivery appendix.

Предыдущие focused inventory/planner/architecture-guard/storage/Compose smoke и adjacent card/queue/inspection GREEN сохраняются для неизменённых соответствующих границ; полный CI на exact committed candidate остаётся обязательным. Это не общий GREEN cache и не waiver CI.

Root просмотрел desktop/mobile из browser evidence; механический detector по трём изменённым views вернул []. Последняя правка условий не меняет working-case layout. До PR: ~58 минут с18:33UTC; Gate3 два возврата, затем approved helper/observer/A4 deltas; Gate5 первоначальный и A4 delta APPROVED. Время/повторы после CI будут отражены closing metadata. Token/cost telemetry UNKNOWN. Стенд не переключался.

## Первый full CI — полный перечень результатов

[CI34520977300](https://github.com/Antropophag/fmonitor-2/actions/runs/34520977300) на exact commit `7aea9393fac2970c08d08b4b7a8f6e40840db366` завершился FAILURE. Unit, E2E, обе Integration shards, plan и quality-results SUCCESS. Fast и governance: единственное failing assertion `verification_ci_001_test.py::test_real_composition_keeps_contracts_once` — ожидаемый literal E2E list не содержит нового documentary browser member. Verify failed как агрегатор этих двух результатов. Полные логи всех проверочных jobs собраны вне repo: `/private/tmp/fmonitor-pr91-ci-34520977300/`; единственный runner REGRESSION_FAILURE — verification_ci_001_test.py. Новые HTTP/concurrency/browser имеют literal PASS в CI. До получения полного inventory исправлений не было.

Коррекция A7 обновляет только literal ожидаемый состав CI, не ослабляет uniqueness/coverage assertions и не меняет production/test routing. Независимый delta review и новый full exact-source CI обязательны; первый failure не переклассифицируется.

## Исправленный состав CI

A7 test-alignment и verification-only Gate5 APPROVED: corrected source `dc9ac4f7174279fb066b193ce4df5d59998332e5f2f2a7ef0dadd3b8ff270db0`, package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T194801Z-2e5dcfb59e/`. CI contract16/16 GREEN1789069633889502000-afff3f2d89e148ef8363f4cd2c54b92a. Единственное изменение исполняемого test-кода — добавлен ожидаемый documentary browser member; production bytes совпадают с первым кандидатом7aea9393. После snapshot добавлены только review/этот delivery appendix. Новый полный CI обязателен для correction commit.
