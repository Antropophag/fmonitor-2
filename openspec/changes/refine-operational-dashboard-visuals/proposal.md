## Why

После объединения PR #217 столбцы диаграмм теряли высоту под production CSP, часть графиков была визуально неоднозначной, а существующая «Давность активности» не давала руководителю достаточно конкретного следующего действия. Владелец проверил последовательные варианты на локальном стенде и утвердил компактную композицию с процессными цветами, недельной нагрузкой и отдельным риском ближайших стартов.

## What Changes

- Исправить отрисовку пропорциональных столбцов без inline-style и browser-side chart runtime, сохранив публичные оболочки Dashboard/Chart Widget `shlz-ui`.
- Заменить диаграмму давности активности на read-only диаграмму «Риск срыва ближайших стартов» с пятью серверно рассчитанными категориями и точным drill-down в реестр.
- Связать цвета этапов с существующими статусными label, а цвета плановых начал/окончаний — с семантикой календаря в пределах палитры `shlz-ui`.
- Убрать повторяющиеся подписи «Начало/Окончание» под недельными столбцами, оставить легенду и показывать границы недели вертикальной парой дат.
- Сделать KPI-блоки компактными и выровненными, синхронизировать baseline столбцов, исключить пересечения и разную ширину marks на поддерживаемых viewport.
- На промежуточной ширине складывать два нижних графика вертикально, сохраняя широкую двухколоночную композицию и отсутствие page overflow.
- Уточнить основную навигацию публичными exports `shlz-ui`: Dashboard, Docs, Eye, Interface Calendar, Graph, User и Settings; перенести «Дашборд» после «Монтажников» и обеспечить одинаковую fill/stroke-отрисовку на всех маршрутах независимо от AssetBundle/cache order.

## Capabilities

### New Capabilities

- `ui/operational-dashboard-refinement`: согласованная владельцем семантика риска ближайших стартов, CSP-safe marks, цветовая система, responsive-геометрия KPI/диаграмм и единая навигационная иконография.

### Modified Capabilities


## Impact

- Public seams: `GET|HEAD /pilot/dashboard`, chart drill-down через `GET /pilot/objects`, общий Yii sidebar.
- Read model: bounded dashboard aggregation и allowlisted queue filter для пяти risk buckets; предметные таблицы и writers не меняются.
- Presentation: `dashboard.php`, `pilot.css`, Yii AssetBundle cache-busting и pinned public icon exports из `../shlz-ui`.
- Verification: существующий dashboard data/HTTP/browser acceptance дополняется regression-проверками CSP, risk values/drill-down, точных цветов/иконок и геометрии на широких, промежуточных и мобильных viewport.
- Не входят новые таблицы, фоновые jobs, изменение доменных статусов, прав, календарных событий, состава KPI, сторонняя chart library, `rapid-pilot`, merge или deploy.
