## Why

Инженер стройконтроля работает со списком объектов на телефоне, но текущая строка смешивает кликабельный layout и вложенные кнопки, занимает лишнюю высоту и не даёт независимого доступа к документации, плану инспекции и чек-листу. Согласованный макет разделяет эти действия, сохраняет существующие проекции и использует компоненты и иконки `shlz-ui`.

## What Changes

- Перестроить Yii2-список стройконтроля в компактные мобильные записи с полными адресом, подъездом, регистрационным и заводским номерами.
- Сохранить существующие фильтры, серверный поиск, состояния завершения, индикатор локальной синхронизации и маркер отгрузки; на мобильном скрыть заголовок и описание раздела, но оставить белую панель поиска и фильтров.
- Добавить независимую круглую кнопку технической документации, доступную только при effective Bitrix link; при отсутствии ссылки показывать disabled-состояние без перехода.
- Представить планирование инспекции круглой кнопкой: primary для отсутствующего плана, outlined для существующего; сохранить текущие create/reschedule/cancel команды и точные метки плана, без даты рядом с `Инспекция сегодня`.
- Сделать правую вертикальную область со штатным `chevron-right-duo` единственным переходом в чек-лист; остальная запись не открывает чек-лист.
- Сохранить desktop-описание раздела и табличную читаемость, keyboard focus, touch targets и отсутствие горизонтального overflow на 320 px.
- Не менять persistence, RBAC, Bitrix refresh, inspection-planning application seams, calendar, checklist mutations, фильтрацию/пагинацию или `rapid-pilot`.

## Capabilities

### New Capabilities

- `ui/construction-control-mobile-list`: responsive presentation and independent construction-control row actions over existing read and command seams.

### Modified Capabilities


## Impact

Изменяются Yii2 queue view, локальные queue styles/interaction wiring, read projection заводского номера, focused HTTP/browser contracts и canonical presentation specs. Схема БД, writers, deploy/runtime composition и внешние API не меняются. Owner 2026-09-25 явно supersedes прежний запрет отдельной document-кнопки в construction-control queue для этого bounded slice.
