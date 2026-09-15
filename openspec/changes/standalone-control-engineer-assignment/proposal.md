## Why

После #40 и #38 текущее закрепление инженера выводится из последнего применённого распоряжения, поэтому подготовка нового распоряжения вынуждает пользователя снова выбирать и подтверждать инженера, а карточка объекта не может изменить оперативное закрепление независимо от документа. Решение владельца по #52 отделяет текущий операционный факт от неизменяемого снимка инженера в распоряжении.

## What Changes

- Ввести один native public application seam и минимальное append-only хранение истории текущего закрепления инженера за объектом/монтажным делом.
- Разрешить уполномоченному пользователю назначить или заменить инженера из карточки объекта с серверной проверкой полномочий и аудитом actor/time/lineage.
- Читать standalone fact как authoritative current assignment; только при его отсутствии разрешить bounded bootstrap из последнего подтверждённого native application с явным provenance.
- Убрать из подготовки распоряжения radio/select и отдельное подтверждение инженера; показывать authoritative current engineer справочно, а при его отсутствии или неоднозначности запрещать подготовку без fallback.
- Фиксировать current engineer как immutable snapshot нового распоряжения, не изменяя прежние распоряжения, applications и исторические process facts.
- Переключить только существующие read seams #40/#38, которым нужен именно current engineer, если это возможно без их redesign.

## Capabilities

### New Capabilities

- `standalone-control-engineer-assignment`: append-only authoritative current assignment, карточное изменение, bootstrap provenance и использование текущего инженера в native preparation/order snapshot и зависимых current-assignment reads.

### Modified Capabilities

Нет.

## Impact

Затрагиваются минимальная native schema migration, новый application owner/read seam, Yii2 карточка объекта и preparation flow, существующая запись composition/order snapshot и точечные current-engineer projections. Не затрагиваются `rapid-pilot`, legacy assignment writers, checklist/offline, календарь, generic event/assignment framework, массовая миграция данных и соседние product issues.
