## Why

Перед тестовым запуском уже импортированные из legacy FMonitor объекты должны сохранить фактическое оперативное закрепление инженера строительного контроля. Сейчас импорт дел создаёт только пустые process cases, а пользователи Yii2 и standalone assignment существуют отдельно, поэтому владелец вынужден вручную создавать доступ и повторно распределять объекты.

## What Changes

- Добавить offline migration seam с обязательным preview: он читает выбранные уже импортированные объекты и их `responsstroicontrol` из legacy БД, сопоставляет legacy инженера с локальной Yii2 identity по сохранённому exact legacy user ID и классифицирует каждую строку как ready, already applied, skipped или conflict.
- Расширить админку пользователей явным связыванием локальной identity с единственным legacy `users.id`; создание/приглашение пользователя, назначение роли и безопасный первый вход продолжают использовать существующий IdentityAccess owner без переноса legacy-паролей.
- Применять только подтверждённый preview через существующий public owner standalone-закреплений, сохраняя actor/time/source operation/legacy object/user lineage и append-only историю.
- Сделать повторный запуск идемпотентным; не заменять текущее native-закрепление и не создавать распоряжения, applications, originals или opening facts.
- Выпускать итоговую сверку с counts и стабильными reason codes; legacy БД остаётся read-only.

## Capabilities

### New Capabilities

- `legacy-control-engineer-assignment-migration`: детерминированный preview, подтверждённое применение и сверка legacy-закреплений для уже импортированных объектов тестового контура.
- `legacy-user-identity-link`: административно подтверждаемая однозначная связь local Yii2 identity с exact legacy user ID без доверия к совпадению ФИО и без переноса пароля.

### Modified Capabilities

Нет.

## Impact

Затрагиваются IdentityAccess schema/application/UI для явного legacy identity link, offline installation-process importer, существующий `ControlEngineerAssignment` public seam и focused Yii2/admin/CLI tests. Источник — read-only таблицы legacy `users`, `users_roles`, `fm_maintable`; target — canonical local identities и `fm2_control_engineer_assignments`. Не меняются `rapid-pilot`, legacy данные, signed documents, состав распоряжений, opening/checklist facts, общий перенос пользователей и автоматический массовый выбор объектов вне уже импортированного контура.
