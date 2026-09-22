## Context

См. `proposal.md` — Why и delta spec `specs/otiz/guided-native-interface/spec.md`. На `origin/main` `c8bfc42d` канонические Yii-маршруты уже вызывают `SnapshotPublication` для build/accept и `OtizSettlement` для discipline/complete/reverse. `MariaDbOtizSettlementView` читает snapshot, objects, allocations, issues, closures и строит accepted-only XLSX; `MariaDbObjectRegister` сохраняет серверные search/filter/sort/page. Текущий шаблон смешивает статус расчёта с платёжным состоянием и выводит «Выплата по объекту выполнена» для любого незаблокированного объекта, попавшего в fallback-ветку.

Визуальный вариант B проверен Playwright-рендером локального `otiz-redesign/index.html` при 1440×1100 и 390×844. Его композиция — источник layout, но иллюстративные статусы/суммы не источник поведения. `rapid-pilot/Otiz.php` и `rapid-pilot/pilot.css` допустимы только как адресный внешний oracle. Новый выбор концепции и throwaway prototype не создаются.

Параллельная #222 владеет записью непроцессных реквизитов, историей и effective-value consumers. Этот change не меняет object card, `MariaDbNativePremiumInputs*`, migration/schema или импортные значения; совместимость проверяется после появления её результата.

## Goals / Non-Goals

**Goals:**

- Собрать четыре пользовательских этапа из существующих серверных состояний и команд, не вводя новую state machine.
- Сформировать один read-only presentation model для header/steps/questions/drawer/archive, чтобы одинаковый snapshot/object имел одинаковые подписи и суммы.
- Сохранить первый рендер, progressive enhancement, focus management и narrow usability.

**Non-Goals:**

- Второй финансовый application owner, клиентская формула, localStorage-статус или mutation из GET/drawer.
- Новые действия «Запросить документ», «Вернуть владельцу», «Исключить» без существующей команды.
- Изменение `pilot.css` вне scoped `.fm2-otiz` правил или копирование внутренностей `shlz-ui`.

## Decisions

1. **Owning module и public seams остаются прежними.** `app/Otiz/SnapshotPublication` владеет build/accept, `app/Otiz/OtizSettlement` — discipline/complete/reverse, Yii controllers — HTTP/redirect, а views/assets — presentation. Альтернатива с новым workflow owner отвергнута как новая state machine и второй владелец фактов.

2. **Шаг определяется проекцией, а не хранится.** Draft с blockers показывает проверку и запрещённое подтверждение; допустимый draft — подтверждение; accepted с положительной доступной суммой — учёт выплаты; accepted без новой суммы — нейтральное завершение без нового payment claim. Посещение экрана и нажатие «Далее» не создают статус. Слова `paid/reversed` не читаются как статус snapshot, пока schema его не хранит.

3. **Суммы разделяются по контексту.** `total_pool_cents` и object accrued/pool — сохранённый snapshot; current availability — `max(0, accrued_cents - global_closed_cents)` с явной подписью «доступно сейчас»; ledger rows — факты выплат/удержаний/сторно. Presentation model даёт именованные поля, чтобы шаблоны не повторяли двусмысленную арифметику. Альтернатива использовать ноль как proof of payment отвергнута.

4. **Одна server-rendered страница snapshot и одна общая drawer-композиция.** Контроллер передаёт object+snapshot context; drawer content присутствует в HTML/доступен по канонической ссылке и только усиливается локальным JS. Links внутри строки не перехватываются. Focus trap/return, Escape и backdrop живут в scoped `otiz.js`; без JS остаётся обычная детализация. Альтернатива SPA отвергнута.

5. **Минимальные read-only расширения.** `snapshots()` добавляет уже сохранённые calculated/accepted actor/time, count и разрешённость документа; snapshot projection группирует blocker/warning counts и связывает closures/reversals. Имена автора берутся из существующей identity projection, если она уже доступна; при отсутствии точного имени показывается стабильная identity, не выдуманное имя. Новых таблиц нет.

6. **Контекст списков передаётся обычными URL.** Query-параметры поиска/state/sort/page/pageSize и `return`/fragment для drawer сохраняются в ссылках; после POST redirect возвращает конкретный snapshot. Session/local storage не владеют состоянием процесса. Existing sessionStorage publication operation id остаётся только transport replay identity и не становится workflow truth.

7. **UI наследует выбранный вариант B и публичные `shlz-ui` exports.** Три tabs имеют одинаковый паттерн, таблица остаётся широкой с contained scroll, первый столбец ≥210 px, заголовки 12/18 uppercase/500, body 14/20, numeric right-aligned. Локальные ОТиЗ-композиции — money summary, step rail, question queue, drawer. CSS добавляется в существующий asset pipeline с `.fm2-otiz` scope; публичные tokens/components подключаются, внутренний код библиотеки не копируется.

8. **Подтверждения мутаций — нативные dialog/серверные формы с точным preview.** Accept показывает snapshot/date/scope/amount; complete дополнительно объясняет учёт внешне выполненной выплаты. Cancel не submit-ит. Удержание и сторно остаются отдельными формами с основаниями и не становятся обязательными этапами.

9. **Проверка от публичного Yii seam.** Root пишет нормативный `specs/OTIZ-GUIDED-WORKFLOW-001.md` и intended RED: presentation/read-only invariants, false-payment regression, blocker/warning, metadata, context preservation и Playwright journey. Executor меняет production code после planner/Gate 3. Browser test использует изолированную fixture и настоящий download; реальные записи не затрагиваются.

10. **Architecture-check impact.** Новых global dependencies и production routes нет. Проверяются отсутствие SQL writes в views/controllers, отсутствие `rapid-pilot` dependency, scoped CSS/JS, `shlz-ui` public asset provenance и неизменность state-changing owners. Изменения `app/PilotHttp/*.php` не планируются; если появятся, обязательна отдельная HTTP qualification check и обновление plan.

## Risks / Trade-offs

- [Текущая available сумма зависит от последующих ledger-фактов и отличается от snapshot] → показывать обе величины с датой/контекстом и тестировать labels, не объединять их в один «итог».
- [Drawer может дублировать HTML и расходиться с таблицей] → строить обе композиции из одного presentation model и сравнивать значения browser-тестом.
- [Большой UI-slice затрагивает несколько шаблонов/tests] → один normative acceptance matrix, bounded vertical checks и полный candidate review; не дробить на несвязанные cosmetic commits.
- [#222 изменит effective read contracts] → не читать её tables напрямую; после появления branch/PR проверить фактический public interface и recorded overlap до rebase.
- [Prototype mobile capture визуально обрезает широкую таблицу] → production использует явный contained-scroll/focusable wrapper, а не скрытие колонок или карточную подмену.

## Migration Plan

1. Зафиксировать Gate 1, verification input/plan и RED на чистом worktree от `origin/main`.
2. После требуемого Gate 3 executor реализует presentation model, views, scoped assets и минимальные read projections.
3. Выполнить focused PHP/HTTP/Playwright checks и двухраундовый bounded visual pass desktop+narrow; прогнать Impeccable detector один раз после UI finish.
4. Независимый Gate 5 проверяет reconstructible exact source, после исправлений запускается один exact-source CI и создаётся PR. Merge/deploy не выполняются.

Rollback возвращает presentation/read projection к предыдущему source; новые domain facts или schema отсутствуют, поэтому денежные записи не удаляются и не переписываются.
