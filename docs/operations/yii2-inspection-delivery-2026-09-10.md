# #76 — inspection journey: Gates3/5 APPROVED, final CI pending

Новый срез пока локален; merge/deployment не выполнены. Tests/spec checkpoint
`752b62a4`; checkout `../fmonitor-2-yii2-inspection-76`. Рабочий стенд не переключался.
[Текущая цель](current-delivery-goal.md) содержит ограничения и следующую очередь.

## Сверенный predecessor

Main `5822cde3ab327c728db2cc7affb661d0946123cc`: PR87 merged, source
`76e896b7d9c692a66fb722bd21de6cb091431bc4`, CI34465238864 SUCCESS с literal VERIFY_OK.
#82 выполнена PR84; #76 OPEN. Dirty исходный checkout и прочие WIP сохранены.
Готовый closeout1702cc9d перенесён в эту ветку вместе с byte-identical historical
logs; только указатели снимают прежнюю остановку. Это ещё не публикация в main.
Завершённые audit/reviews/CI predecessor не повторялись.

## Контракт, source и gates

- [Один нормативный контракт](../../specs/YII2-INSPECTION-JOURNEY-001.md).
- [Lifecycle](../../openspec/changes/yii2-inspection-journey/tasks.md).
- [Gate3 и RED/delta evidence](../../reviews/tests/YII2-INSPECTION-JOURNEY-001.md).
- [Gate5 findings](../../reviews/code/YII2-INSPECTION-JOURNEY-001.md).

Root — анализ, spec/tests, нормативная metadata. Executor/reviewer — отдельные
`gpt-5.6-sol / low`; reviewer не автор кода/тестов. Spec/tests не делегировались.
Им передавались exact restored source, bounded scope, контракт, verification plan
и необходимые unchanged sources; вся история handoff/reviews не загружалась.

Snapshot bases и полные SHA256 записаны в reviews; retained snapshots расположены
в `~/.local/state/fmonitor2/review-snapshots/`:

| Snapshot | Результат |
|---|---|
| 76-inspection-gate3-ready /63fc0023 | Gate3 CHANGES_REQUESTED |
| 76-inspection-gate3-delta /5dd2f1c9 | Gate3 APPROVED;19 артефактов совпали до checkpoint |
| 76-inspection-browser-delta /8e74075a | Gate3 instrumentation delta APPROVED |
| 76-inspection-gate5 /e93a1e3b | Gate5 CHANGES_REQUESTED; seam registration APPROVED |
| 76-inspection-gate5-correction-tests /1084a0bc | Gate3 regressions APPROVED |

Блокирующие выводы имели source evidence; ошибочное требование reviewer расширить
HTTP typed reasons было отозвано после проверки unchanged spec/adapter. Исправления
проверялись связанными delta, не повторными полными аудитами. При новой DB/projection
проблеме добавлен один regression suite, без второй acceptance matrix.

## Фактическая повторная работа

На этой точке: **2 отдельных агента,10 запусков заданий**, **2 review returns**
(первый Gate3, первый Gate5). Follow-up задания считаются запуском, обычные
сообщения — нет. Отдельный read-only closeout подсчётов включён.

Метрика проверки — верхнеуровневый запуск suite-файла, без вложенных assertions/
подкоманд, syntax и plan freshness. **83 запуска22 разных suites,61 повтор**:
14 до implementation +62 в первой implementation +7 correction-regression checks.

| Первая implementation: семейство | Запуски |
|---|---:|
| Yii HTTP / concurrency / browser | 8 / 5 / 8 |
| Legacy checklist smoke | 5 |
| Native item5 файлов, включая wiring4 | 8 |
| Current-source / online bulk / offline behavior | 2 / 2 / 1 |
| Direct HTTP auth / actual architecture-check | 3 / 7 |
| verification_ci / inventory | 2 / 2 |
| jobs / change-verification / runtime-storage / architecture-guard | 2 / 2 / 2 / 2 |
| Yii queue neighbor | 1 |

Повторы были вызваны applied-composition wiring, pager/видимостью раздела,
async polling defect, подтверждённой bulk/photo гонкой, shared adapter/traits,
повреждённым при форматировании content-type regex, seam registration/trait path,
затем configured DB и projection regressions. Повторённые governance/registration
checks также учтены и не объявлены экономией. У двух ранних architecture запусков
нет сохранённого terminal verdict; они не считаются GREEN evidence.

Отдельно:3 invalid/setup exploratory attempts (photo token, missing preopening
filename, current-crew credentials),6 executor browser probes,1 direct facade
probe,1 root minimal Playwright repro. Два setup дефекта нового regression fixture
(плановая дата/association column) учтены в его4 запусках, но не названы RED.
Full local suite0, full CI0. Токены/стоимость не измерены. Elapsed начинается
2026-09-10T13:38:41+03:00 — создание worktree по git reflog.

## Evidence и осталось

Logs/plans: `~/.local/state/fmonitor2/deliveries/76-inspection-20260910/`.
Первый кодовый кандидат имел HTTP/concurrency/browser/native/legacy/offline/
architecture GREEN, но Gate5 выявил обход Yii DAO и потерю current employment
overlay; это не завершённый Gate4 после новых regressions. Связанный fallback
инженера также защищён browser RED. Executor исправляет эти границы после
одобренного Gate3 delta. Далее focused GREEN → Gate5 delta → один full exact-source
CI → merge. Базовая структура единственного owner и ровно2 accept seam entries
одобрены reviewer; остальных baseline allowances и нового governance слоя нет.

Completion ПТО/декларация, оставшийся runtime/console и общий cutover остаются #76.
Переключение стенда — отдельный согласованный шаг с сохранностью БД/PDF/фото,
сессий, offline действий и jobs; локальный код не выдаётся за deployment.

## Исправленный финальный кандидат

Yii DAO использует переданное соединение; native completeItem отдельно инъецирован
целиком. Non-item revision row создаётся только внутри мутации/транзакции; фото
commit/rollback проверены на Yii events. Owner отклоняет inactive/invited до replay.
Current overlays сохраняют explicit NULL и historical sourceUpdatedAt; legacy
crew и engineer fallback восстановлены. Финальные logs `gate5-overlay-*.log`
(boundaries/http/concurrency/browser/legacy) GREEN; actual architecture7 правил
GREEN. План проверен; source frozen для Gate5 delta.

Обновлённые счётчики:2 агента,11 запусков до Gate5 delta;2 review returns.
После предыдущего checkpoint добавлены1 root auth RED и21 executor suites
(boundaries7, HTTP/concurrency/browser/legacy по3, architecture2).
Итого105 suite invocations22 файлов/команд,83 повтора. Причины текущей correction:
Yii positional bind1-based, отсутствовавший revision row для первого non-item,
auth guard и точная nullable overlay parity. Дополнительно6 диагностических
boundary probes (включая setup failures), не GREEN evidence. Registration
после последнего одобрения не повторялся. Full local0, CI0.

## Gate5 APPROVED / финальный checkpoint

Independent Gate5 delta APPROVED на snapshot
`15a85d72c878e0d01d4dc2c814d7dd9dd713caa8949064f4676e269b63ec7db8`,
base752b62a4; restored `/private/tmp/fmonitor-76-inspection-gate5-final`.
Все изменённые source artifacts побайтово и по modes совпали с reviewed snapshot
перед implementation commit. Дополнительно внесены только Gate5 verdict, tasks
checkbox, current-goal и этот delivery record. Source/test изменения после
approval не вносились. Всего2 агента/12 запусков заданий,2 review returns;
105 suite invocations/83 повтора. Reviewer delta не повторял проверки.
Следующий шаг: один full exact-source CI и merge; deployment не разрешён этим шагом.

Pre-PR whitespace delta: staging выявил18 пустых строк с пробелами в3 новых
traits, которые unstaged diff не видел. Root удалил только эти пробелы; PHP tokens
без T_WHITESPACE совпали. Independent Gate5 delta fa21181b APPROVED без повторных
тестов. Это nonsemantic commit preparation, не новая реализация/root code policy.
2 агента/13 запусков заданий,105 suite invocations/83 повтора;2 review returns.
Исправление включено в тот же implementation checkpoint до PR/единственного CI.
