# Handoff: ОТиЗ — пошаговая работа в согласованном интерфейсе

Дата фиксации: 2026-09-22. Этот документ — точка входа для новой сессии. Он
сохраняет продуктовый контекст, фактическое состояние checkout/стенда, все
существенные решения и замечания владельца, выполненные проверки и незавершённые
обязательства. Не считать наличие интерфейса на локальном стенде готовностью к PR.

## 1. Где продолжать

- Рабочий каталог: `/Users/antropophag/code/fmonitor-2-otiz-workflow`.
- Ветка: `codex/otiz-guided-workflow`.
- HEAD: `c8bfc42d774bbe52a6528eb44a371c8a9003e6d3`; все изменения пока dirty и
  не закоммичены.
- Исходный base при старте: тот же `c8bfc42d`; актуальный локальный
  `origin/main` на момент handoff: `dd1cd5a2aafa5a68a8872d21c95d20a4002e90c9`.
  Ветка отстаёт от `origin/main` на 10 коммитов и не имеет собственных коммитов.
- Не переключать, не очищать и не изменять исходный checkout
  `/Users/antropophag/code/fmonitor-2`: там чужой WIP.
- Локальный стенд: `http://127.0.0.1:8093`, Compose project
  `fm2-local-timofey`. БД, sessions и artifacts сохранены. На стенде установлен
  текущий dirty candidate image
  `sha256:f7d3f45fff20686ffb4cbeb85bac34f6d76bcfe69567165b1d0e7697b82464bc`;
  `db/php/web/jobs-worker/jobs-scheduler` healthy на момент handoff.
- PR, push, exact-source CI, merge и production deployment не выполнялись.
  Реальные финансовые POST на стенде не выполнялись.

Перед любым продолжением прочитать полностью:

1. `AGENTS.md` и `docs/operations/current-delivery-goal.md`;
2. `PRODUCT.md`, `CONTEXT.md`, `docs/fmonitor-2-pilot-spec.md`,
   `docs/fmonitor-2-pilot-data-model.md`, `docs/development-process.md`;
3. этот handoff, `docs/operations/otiz-guided-native-interface-delivery.md`;
4. `specs/OTIZ-GUIDED-WORKFLOW-001.md` и весь
   `openspec/changes/otiz-guided-native-interface/`;
5. `reviews/tests/OTIZ-GUIDED-WORKFLOW-001.md`.

После чтения выполнить `python3 tools/delivery/harness.py state`. Текущий
planner lane — `CRITICAL`, требуются Gate 3 и независимый final review. Локальные
полные `make test` / `make verify` запрещены владельцем.

## 2. Первоначальное поручение владельца — обязательный чек-лист

Цель: сотрудник ОТиЗ самостоятельно проходит существующий процесс от выбора
расчётной даты до проверки, подтверждения, настоящего XLSX и регистрации уже
выполненной вне FMonitor выплаты. На каждом этапе понятны состояние, нужные данные
и следующий шаг. Это native Yii-экраны, не отдельный HTML-прототип. Используется
уже выбранный вариант B; новый выбор концепции запрещён.

Обязательные границы исходного запроса:

- Сохранить три режима: «Экономика объектов», «Выполнение расчёта», «Архив
  расчётов». Это вкладки, а не четыре шага wizard. Канонические маршруты остаются
  `/pilot/otiz/...`, без SPA и буквального переноса prototype `?tab=`.
- Сопоставлять UI только с существующими командами и сохранёнными фактами; не
  создавать новую state machine, формулы, нормативы, округление, schema,
  финансовые команды или первичные данные.
- Подготовка создаёт настоящий сохранённый draft через существующий POST и после
  успеха открывает его id. Reload/back продолжают этот snapshot. Дата расчёта,
  время создания и дата факта — разные значения. Не подставлять today за
  неизвестную source date.
- Проверка показывает id/date, финансовые итоги, массовый состав объектов,
  работников, blockers/warnings и одно главное следующее действие. Blocker
  запрещает accept, warning сам по себе не запрещает. ОТиЗ не исправляет
  первичный факт на этом экране. После исправления источника готовится новый
  snapshot; старый не переписывается.
- Перед accept показать date/scope/saved amount. Использовать существующую
  команду. Настоящий XLSX доступен только по действующим правилам. Accept и
  download не означают выплату.
- Перед complete-payment показать date/scope/current available amount и явно
  сказать, что FMonitor только фиксирует внешне выполненную выплату. Cancel не
  пишет ничего. Replay/error/no-change не показываются как новый успех.
  Удержания и сторно остаются отдельными действиями с основаниями и историей.
- Исправить ложное «Выплата по объекту выполнена»: draft, отсутствие blocker и
  нулевая доступная сумма не доказывают выплату. Snapshot status и ledger/payment
  state раздельны. Discipline — не выплата; reversal — сторно конкретной записи,
  не всего расчёта.
- Экономика сохраняет серверные search/filter/sort/pagination и смысл итогов.
  Нельзя фильтровать только текущую страницу. Данные не выдумывать; «Нет данных»,
  ноль и «Ещё не рассчитывался» различаются.
- Детализация — одна общая доступная drawer exact object+snapshot из экономики,
  вопросов и snapshot/archive. Суммы строки/drawer/details совпадают; исторический
  snapshot не подменяется latest. Не писать формулу на JS. Drawer закрывается
  кнопкой/Escape/backdrop, управляет фокусом и читаем на узком экране.
- Экономическая строка по возможности показывает address, snapshot/report date,
  progress fact date, premium base, Kсс, breakdown удержаний и использование
  фонда. Общая drawer должна брать только реально доступные факты и включать:
  regnumber/address/report date; progress + confirming fact date; fund/accrued/
  previous payments/withholdings/remaining fund/current available с точными
  labels; сохранённую trace без упрощённой JS-формулы; Кшах/Квып/Ксс; allocation
  workers (ФИО, табельный номер, участие/КТУ, сумма, основание); связанные blockers
  и warnings; источники, даты и доступные links; только уместные существующие
  actions. Если каких-то данных нет, это не заполняется иллюстративными значениями.
- Архив показывает реальные snapshots, различает одинаковую дату по id,
  показывает actor/timestamps/count/total/разрешённые документы. «Скачать XLSX»
  и история — настоящие действия, без демонстрационного success.
- Только публичные exports `shlz-ui`; Golos Text 400/500/600. Scoped OTIZ styles,
  без переписывания global pilot styling. Таблица 14/20, заголовки 12/18 500
  uppercase, numeric right, устойчивые ширины, доступность с первого render.
- Не добавлять prototype-actions без существующей команды. Если команды нет —
  только владелец факта, нужное исправление, доступный переход или честное
  «Скопировать текст запроса».
- Состояние шага выводится только из server facts; `localStorage`, click «Далее» и
  факт посещения вкладки не являются состоянием. Не придумывать persisted
  snapshot statuses `paid/reversed`: в действующей schema наблюдались только
  `draft/accepted`. Saved snapshot total и live ledger-relative available могут
  различаться и всегда имеют разные labels.
- Геометрия/типографика/table readability существуют на первом render. Отказ
  select behavior не должен ломать tabs, table reading или drawer close. Нельзя
  возвращать native-looking select, двойной focus ring, browser-default `th`,
  оформление таблицы только после JS или разный стиль tabs.
- Проверить Playwright’ом реальный render, keyboard и narrow. Изменяющие проверки
  только на изолированных fixtures, не на пользовательских данных стенда.
- До PR владелец должен увидеть результат на стенде, включая
  `/pilot/objects/1427`. Затем независимый final review и exact-source CI.
- Не выполнять merge, production deployment или реальные финансовые действия.

Исходные визуальные материалы:

- `/Users/antropophag/.local/share/fmonitor-2/prototypes/otiz-redesign/index.html`;
- `/Users/antropophag/.local/share/fmonitor-2/prototypes/otiz-redesign/rapid-pilot-objects.html`;
- связанные CSS/fonts/behavior рядом;
- `rapid-pilot/Otiz.php` и `rapid-pilot/pilot.css` — только адресный визуальный
  reference, не источник финансового поведения.

Исходно требовалось использовать `impeccable` и `prototype`. Оба применялись:
prototype — только для изучения выбранного решения; impeccable — для анализа и
нескольких layout/polish итераций. В новой сессии не начинать новый prototype.

## 3. Все закреплённые визуальные решения владельца

Это результат многочисленных просмотров реального стенда. Не откатывать эти
решения к ранней версии макета:

- Серый canvas; контент разложен на белые поверхности с радиусом и внутренними
  отступами. Запрещён «layout внутри layout»: один огромный белый прямоугольник с
  острыми краями и контентом, прилипшим к краям.
- На всех трёх вкладках верхний блок — единая цельно-белая поверхность: одинаковая
  строка табов сверху плюс mode-specific header/workflow ниже. Координаты строки
  табов должны быть одинаковыми при переходах, чтобы она не дёргалась.
- На экономике верхняя поверхность объединяет tabs + заголовок + четыре денежные
  метрики. Метрики — крупные цифры на белом фоне с вертикальными разделителями,
  без серых вложенных карточек. Вторая поверхность объединяет toolbar + table +
  pagination.
- Вертикальные интервалы между белыми поверхностями сверены с карточкой объекта;
  без чрезмерного воздуха. Pagination имеет умеренный отступ от таблицы, номера и
  стрелки находятся в одной строке.
- Select/search/button в toolbar имеют одинаковую компактную высоту 44 px, форму
  pill, normal font weight. Селекты не растягиваются по высоте, список не скрывается
  таблицей. Используется публичный shlz-ui select.
- Расчётная дата — публичный shlz-ui Date Picker с календарём, без текстовой
  подсказки формата. Исправлена лишняя белая подложка справа от popover.
- Экономическая таблица на desktop помещается в доступную ширину без horizontal
  scroll. На действительно узком экране лучше controlled horizontal scroll, чем
  деформация статусов. Первый столбец поджат разумно; лишняя щель между paid и
  withheld устранена.
- «Выплачено» и «Удержано» объединены для экономии ширины: заголовок только
  «Выплачено», крупной первой строкой paid, второй строкой «Удержано …».
- Status pills никогда не переносятся и имеют одинаковую высоту. Не обрезать
  label.
- У action column нет видимого заголовка. Во всех таблицах используется одна
  icon-only action — публичная `chevron-right-duo.svg`. Не использовать глаз
  (занят Стройконтролем), текстовые «Открыть», обычную стрелку или три точки.
- Две видимые точки рядом с double chevron были CSS ellipsis, а не частью SVG.
  Исправление: action cell не применяет `text-overflow: ellipsis`; archive action
  column 5%, author column 21%. Не возвращать override, делавший visually-hidden
  fallback видимым как `...`.
- В экономике chevron открывает object drawer. В таблице расчётных периодов
  chevron открывает весь расчёт `/pilot/otiz/snapshots/<id>`, не drawer одного
  объекта. На странице snapshot chevron выбранной object-row открывает exact
  object+snapshot drawer.
- Snapshot — массовая таблица десятков/сотен объектов. КТУ, монтажники, trace,
  источники и замечания живут в детализации выбранного объекта, не отдельными
  несвязанными блоками. Текстовой ссылки «Открыть отдельную детализацию» нет.
- XLSX представлен icon button с публичной `file-xlsx`, а не текстовой ссылкой.
- Ledger/history выплат, удержаний и сторно — структурированная таблица, не каша
  текстовых строк.
- Не выводить пустой блок/фразу «Замечаний нет».
- Вместо непонятного «Нет новой суммы» используется точное состояние
  «К выплате 0 ₽» и объяснение контекста там, где оно нужно.
- Абстрактная полоса «Дата и подготовка / Проверка объектов / Подтверждение и XLSX /
  Учёт выплаты» удалена: она не помогала пользователю.
- Автор расчёта показывается именем/email реального actor, а не `№1`.
- Sidebar может закончиться раньше длинной таблицы: это не причина искусственно
  растягивать sidebar на всю document height.

## 4. Что реализовано

Изменённые production boundaries:

- `app/Otiz/MariaDbOtizSettlementView.php` — минимальные read-only metadata для
  UI, без изменения финансовых правил.
- `app/YiiRuntime/Controllers/OtizSettlementController.php` — передача нужного
  read context.
- `app/YiiRuntime/Controllers/PilotAssetController.php`,
  `config/yii/assets.php` — минимальная выдача нужных публичных shlz-ui assets.
- `app/YiiRuntime/ViewSupport.php` — минимальная корректировка публичной
  presentation helper.
- `app/YiiRuntime/Views/_otiz-nav.php` — ровно три согласованных режима.
- `app/YiiRuntime/Views/_otiz-snapshot-list.php` — табличный список реальных
  snapshots, actor metadata и double-chevron action.
- `app/YiiRuntime/Views/otiz.php` — экономика, подготовка даты, архив, server-side
  filters/sort/pagination, сводка, shared top surface, object drawers.
- `app/YiiRuntime/Views/otiz-snapshot.php` — массовая snapshot table,
  truthful calculation/payment states, confirmation/payment previews, XLSX,
  structured ledger и exact-object drawers.
- `app/YiiRuntime/Assets/otiz.js` — shlz DatePicker wiring, drawers/dialogs и
  focus behavior; финансовых вычислений нет.
- `app/YiiRuntime/Assets/pilot.css` — scoped OTIZ visual composition and responsive
  rules. Файл уже содержит несколько слоёв прежних OTIZ overrides; перед финалом
  желательно аккуратно консолидировать только если это не меняет проверенный
  render.

Tests/spec/governance changes:

- `.quality-graph/verification-policy.json`;
- `specs/OTIZ-GUIDED-WORKFLOW-001.md`;
- `openspec/changes/otiz-guided-native-interface/**`;
- `tests/Yii2/yii2_otiz_shlz_ui_001_test.php`;
- `tests/Yii2/otiz_shlz_ui_browser.mjs`;
- `tests/Yii2/yii2_otiz_commands_001_test.php`;
- `tests/Yii2/yii2_otiz_publication_browser_001_test.php`;
- `tests/Otiz/snapshot_publication_browser_001_test.mjs`;
- `reviews/tests/OTIZ-GUIDED-WORKFLOW-001.md`;
- delivery/current-goal documents.

Ключевой truthful-state defect исправлен: draft/unblocked/available=0 больше не
попадает в общее сообщение «Выплата по объекту выполнена». Snapshot status и
ledger/payment result выводятся раздельно.

## 5. Проверки и артефакты этой сессии

Скриншоты и machine-readable evidence хранятся вне checkout:

- initial stand pass:
  `/Users/antropophag/.local/share/fmonitor-2/otiz-guided-native-interface/stand-20260921/`;
- first polished pass (desktop/narrow, drawer, object 1427):
  `/Users/antropophag/.local/share/fmonitor-2/otiz-guided-native-interface/stand-polished-20260921/`;
- recomposed pass after major owner feedback:
  `/Users/antropophag/.local/share/fmonitor-2/otiz-guided-native-interface/stand-recomposed-20260922/`;
- select/calendar focused captures:
  `/Users/antropophag/.local/share/fmonitor-2/otiz-guided-native-interface/controls-fixed-20260922/`;
- latest three-tab captures after consistent header + no-dots fix:
  `/Users/antropophag/.local/share/fmonitor-2/otiz-guided-native-interface/stand-20260922/objects.png`,
  `payments.png`, `history.png`.

Последняя read-only Playwright проверка реального стенда при 1440×1000:

- `/pilot/otiz/objects`, `/pilot/otiz/payments`, `/pilot/otiz/history` загрузились;
- tab box на всех трёх: `x=355.1875, y=64, width=989.625, height=49.296875`;
- document width равна viewport width: `[1440,1440]`;
- double-chevron count: economy `50`, payments `3`, history `3`;
- archive action cell: `overflow: visible`, `text-overflow: clip`, width `50.234375`;
- итоговый screenshot подтверждает отсутствие точек рядом с шевроном.

Дополнительно в этой сессии:

- `git diff --check` — GREEN;
- PHP lint `otiz.php`, `otiz-snapshot.php`, `_otiz-snapshot-list.php` — GREEN;
- impeccable layout detector для двух views и CSS — `[]`;
- предыдущий fixture Playwright desktop/narrow/keyboard journey был GREEN до
  последних UX-правок (зафиксировано в delivery record);
- текущий WIP уже содержит root-authored expectation для archive icon-link.
  После интеграции на base `3c242f34` `php tests/Yii2/yii2_otiz_shlz_ui_001_test.php`
  GREEN; executor не менял root-owned test.

Владелец сам проверяет Safari. В последней итерации было прямое указание «Не надо
трогать Safari»; автоматизировать Safari нельзя. Для дальнейших browser checks
использовать Playwright/Chromium.

## 6. Delivery/Gates и #222

- Gate 1 принят поручением владельца.
- Gate 3 test/spec review был `APPROVED` для более раннего exact source; после
  последующих test/UI изменений требуется новый prepared package и повторный
  независимый review exact source. Старый approval не переносить автоматически.
- Gate 4 production был выполнен отдельным `gpt-5.6-sol / low` executor. Root
  писал scope/spec/tests. Последние visual fixes также делал тот же executor.
- Gate 5 ещё не выполнялся. PR и CI `UNKNOWN`.
- Harness сейчас: dirty source
  `e064b22406bea15e236b2e5b9dec1c713eb8e122dd4e079a5afbfb263bf65f55`,
  executable source
  `41dbce8476b8d6130160fe3d5a1abaf1176d3a8cb85e92c139fb55d4ac08281a`,
  `merge_ready=false`, `publication_ready=false`, next action
  `prepare/review exact source before publication`. Эти digests изменятся после
  исправления теста/rebase/commit.
- #222 вошёл в `origin/main` через PR #226; candidate интегрирован на
  exact base `3c242f34e8f30986f1b8354c4ef947a4c63936dc`. Публичный seam
  `MariaDbEffectiveObjectDetails` реализован. OTIZ slice не создаёт свой
  effective-values mechanism, не меняет `MariaDbNativePremiumInputs*` и
  остаётся read-only snapshot/ledger presentation consumer. Shared asset/policy
  conflicts разрешены механически; runtime compatibility ещё должна
  быть подтверждена bounded checks и exact-source review.

## 7. Что обязательно сделать в новой сессии

1. Зафиксировать `git status`, прочитать этот документ и `harness.py state`.
2. Candidate уже state-preservingly интегрирован на `origin/main` `3c242f34`
   с merged #222/#226. Перед дальнейшей работой сверить status/harness и не
   трогать чужой checkout, stand, БД или Safari.
3. Archive icon-link expectation уже присутствует и GREEN; не возвращать
   button «Подробнее», видимые точки или текстовую fallback-ссылку.
4. Повторить bounded focused tests из verification plan. Не запускать local full
   suite. Проверить как минимум truthful-state commands/render, публикацию,
   snapshot browser flow и OTIZ shlz Playwright.
5. Сделать один bounded Playwright pass desktop+narrow+keyboard по fixture и
   read-only pass реального 8093. Проверить три tabs, calendar, selects, economy
   drawer, snapshot exact-object drawer, archive navigation, no overflow, focus
   return, Escape/backdrop. На fixture обязательно перепроверить: blocker реально
   запрещает accept; warning не запрещает; draft и zero available не называются
   выплатой; discipline/reversal имеют точный смысл; reload продолжает тот же id;
   исправление source не меняет старый snapshot; row/drawer суммы и context
   совпадают; error оставляет объяснение и recovery. Не выполнять financial POST
   на стенде.
6. Ещё раз показать владельцу локальный стенд и актуальные скриншоты. Это owner
   checkpoint до PR; не считать прежние ругательства/итерации окончательным
   approval без явного подтверждения.
7. После визуального approval: подготовить exact source через harness, получить
   требуемый повторный Gate 3 (если plan требует на изменённом test/spec) и
   независимый Gate 5.
8. Только затем commit/push/PR. Запустить один требуемый exact-source GitHub CI,
   полностью инвентаризировать failures при наличии. Merge/deploy не выполнять.
9. В финале сообщить PR и HEAD, пошаговый пользовательский маршрут, ссылки на
   screenshots, focused checks/CI, неперенесённые fake prototype-actions и точный
   статус совместимости #222.

## 8. Готовый промпт для новой сессии

```text
Продолжи delivery «Пошаговая работа ОТиЗ в согласованном интерфейсе».

Работай ТОЛЬКО в существующем worktree
/Users/antropophag/code/fmonitor-2-otiz-workflow
на ветке codex/otiz-guided-workflow. Не трогай checkout
/Users/antropophag/code/fmonitor-2 и чужой WIP. Ничего не очищай и не теряй:
текущий candidate dirty и ещё не закоммичен.

Сначала полностью прочитай:
- AGENTS.md;
- docs/operations/current-delivery-goal.md;
- docs/operations/otiz-guided-native-interface-handoff-2026-09-22.md;
- docs/operations/otiz-guided-native-interface-delivery.md;
- PRODUCT.md, CONTEXT.md, docs/development-process.md;
- specs/OTIZ-GUIDED-WORKFLOW-001.md;
- весь openspec/changes/otiz-guided-native-interface/;
- reviews/tests/OTIZ-GUIDED-WORKFLOW-001.md.

Затем выполни `python3 tools/delivery/harness.py state` и `git status`. Используй
существующие Gates и prepared role packages: root владеет scope/spec/tests,
production выполняет отдельный gpt-5.6-sol/low executor, reviews независимы.
Локальные full `make test`/`make verify` запрещены.

Не начинай новый выбор дизайна или новый prototype. Сохрани все визуальные решения
из handoff: серый canvas, две осмысленные поверхности экономики, единый белый
header с табами без прыжка на трёх вкладках, compact 44px pill controls,
server-side table behavior, non-wrapping status pills, combined paid/withheld,
icon-only double chevron без точек и без заголовка actions, mass snapshot table,
exact object+snapshot drawer, icon XLSX, structured ledger, отсутствие abstract
steps и «Замечаний нет». Не трогай Safari; browser work только Playwright.

Прежний stale root-authored Playwright selector закрыт в текущем WIP:
`php tests/Yii2/yii2_otiz_shlz_ui_001_test.php` GREEN на integrated base,
но согласованный UI теперь использует настоящий icon-only link
`Открыть расчёт #…`. Не откатывать это решение; дальше выполнять только
bounded focused checks.

Candidate уже интегрирован на `origin/main` `3c242f34` с merged #222/#226.
Публичный `MariaDbEffectiveObjectDetails` присутствует; не создавай второй
effective-values механизм и не меняй `MariaDbNativePremiumInputs*`.

Локальный стенд 8093 уже state-preservingly обновлён; его DB/sessions/artifacts
нельзя сбрасывать. Последние screenshots и все артефакты перечислены в handoff.
Изменяющие browser checks — только на isolated fixtures; на стенде только read-only.
Перед PR снова покажи владельцу стенд и дождись явного approval. Затем exact-source
review, Gate 5 и один требуемый CI. Не выполнять merge, production deployment или
реальные финансовые действия.
```
