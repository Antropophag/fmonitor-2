# Перезапуск сессии — #76, 2026-09-10

Владелец попросил найти момент для перезапуска после поручения автономно завершить
#82 и продолжать #76. Выполнение приостановлено на сохранённом Gate3 checkpoint;
незавершённая реализация сохраняется как WIP, не выдаётся за GREEN.

## Где продолжать

- Checkout: `/Users/antropophag/code/fmonitor-2-yii2-preopening-76`.
- Branch: `codex/yii2-preopening-76-20260910`.
- HEAD: `036b095bce71c23188f8e62d5f2b871641fdf19f` — root spec/tests, независимый
  Gate3 и закрывающая документация PR86. Неизменённые spec/tests одобрены.
- Исходный `/Users/antropophag/code/fmonitor-2` — исторический dirty WIP; не менять.
- Рабочий стенд не переключён. Все текущие проверки используют частные fixture DB.

## Что завершено

#82 полностью завершена и закрыта: PR84, merge
`8c4468738a96953055d35fe6c05bd0c5d26539e4`, source
`11ecb84677b40f7c29ed5b72a49bf260af30956f`, CI34418478143 SUCCESS/VERIFY_OK.
Процесс требует root authorship spec/tests, независимые Gates3/5, полный план до
RED, воспроизводимый snapshot, содержательные commits и один full exact-source CI.

По #76 после этого:
- PR85 users/activation/proxy privacy merged `907cb0a3b05ff5248ee097dd3a271b385c3dcc7b`;
  source `41066c687e94f19b46bf32cd1b09e2c8f3c2e8b9`, CI34431377731 SUCCESS/VERIFY_OK.
- PR86 queue/inspection planning merged `f804f3f6fa7baa7264a51b6e503c13f48406f2d7`;
  source `58023c823fb0552fb6f6e2521e56f8bacbd7b55e`, CI34439847358 SUCCESS/VERIFY_OK.
  [Delivery record](yii2-object-queue-delivery-2026-09-10.md).

## Текущий срез

OpenSpec `yii2-preopening-journey`, contract
[спецификация](../../specs/YII2-PREOPENING-JOURNEY-001.md),
[независимые тестовые ревью](../../reviews/tests/YII2-PREOPENING-JOURNEY-001.md).
Gate3 first RETURN с6 группами покрытия; root исправил весь набор вместе;
corrected independent PASS без замечаний. Двенадцать HTTP/browser/image tests.
Все intended RED подтверждены: отсутствующие Yii routes404 или app-owned logo.
Registry и CI inventory — по15PASS. Native fixture/proxy preflights GREEN.

Approved source snapshot:
`/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-preopening-gate3-corrected`,
patch `cb6c12601403a61806a2382f68d15635761837aac0d636c7c9e174e3aeaaacc2`,
base `f804f3f6fa7baa7264a51b6e503c13f48406f2d7`.
Перед commit все34 reviewed artifacts сверены byte-for-byte. Дополнительные
изменения только в review/status/docs/tasks; spec/tests не менялись.

Gate4 начат отдельным sol/low executor; он выделил card DAO отдельному sol/low
agent. На момент паузы реализация неполная, никаких Gate4 GREEN, Gate5 или CI
для текущего production WIP нет. Агенты остановлены для перезапуска; новая сессия
не должна рассчитывать, что их контекст или выполнение сохранились.

## Решения и незавершённые правки

Путь: card→selection→optional template POST→raw PDF original/correction/history/
exact download→open_confirmed→card. Yii owns native User/Session/CSRF/HTTP.
Новый YiiObjectCard read owner принимает actor явно; moved card SQL — MariaDb YiiDAO.
Уже module-owned native selection/submission/history/command APIs переиспользуются
целиком через idle mysqli, по ADR0003. Нет outer Yii/native transaction, смешанных
writes, SQL в контроллере, PilotHttp/rapid includes или нового HTTP framework.

Root при промежуточном просмотре потребовал:
1. Разделить плотный общий PreopeningController на coherent card/selection/
   original/execution controllers и route-local input models; не minify до149 строк.
2. Не оставлять выдуманные dataOrigin/hasPtoAct/provenance: проекция читает реальные
   canonical facts, сохраняет состояния/85–15%/историю и truthful degradation.
3. Права public card owner должны быть точными независимо от Yii web admission.
Эти требования относятся к незавершённой реализации, не к новому Gate3 return.

## Следующий шаг

Прочитать current goal, AGENTS, development-process и документы среза. Проверить
restart snapshot/заметки исполнителей, текущее дерево. Продолжить Gate4 отдельным
sol/low executor, сохраняя root ownership tests. Тестовые дефекты правит root с
независимым delta Gate3; не ослаблять ожидания для GREEN.

После полного focused failure inventory — grouped fixes, relevant native neighbors,
root desktop/mobile visual QA один раз и одна grouped correction, затем actual
`make architecture-check` (не только checker unit; не запускать их одновременно).
Capture/restore exact Gate5 source, независимый review, meaningful implementation
commit, один full exact-source CI, merge. Не запускать duplicate local full.
Затем продолжать #76; весь #76 ещё не завершён и не закрывается после этого среза.

## Оснастка и доказательства

Pinned vendor уже установлен в checkout. MariaDB тестовый container
`fmonitor2-test-test-db-1`,127.0.0.1:23306; private per-test DB/user. Нельзя reset
shared DB/stand. Public Playwright: sibling `shlz-ui/node_modules/playwright`.
Restored review checkouts используют `/private/tmp/shlz-ui` public sibling symlink.
Тесты private files/schema/real native commands отделены от runtime credentials.

Постоянный restart bundle:
`/Users/antropophag/.local/state/fmonitor2/restarts/2026-09-10-preopening/`.
Он содержит WIP source snapshot+manifest, root/agent handoffs и relevant logs.
Снимок восстанавливается repository tool `tools/delivery/review-source.py restore`.

Последний executor handoff в bundle: `76-preopening-executor-restart.md`.
Он фиксирует5 реально запущенных focused failures: card/routes503,
selection/http503, original form без видимого выбранного монтажника. Object-card
view отсутствует; общий controller и формы неполны. Child сообщает lint для4
card DAO файлов, это не focused GREEN. Следующая сессия начинает с этого полного
известного списка, а не объявляет реализацию законченной.
Read-only next-slice navigation: `76-inspection-inventory-restart.md` в bundle;
это только карта последующих checklist/offline/completion boundaries, не новые
approved spec/tests и не разрешение пропустить текущий Gate4.
