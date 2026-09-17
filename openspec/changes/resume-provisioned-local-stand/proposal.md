## Why

Повторный `make up` уже работающего локального Yii2-стенда ошибочно завершается `IDENTITY_NOT_EMPTY`, потому что проверка `already_provisioned` признаёт только первозданную identity-БД. После обычного входа, приглашения или появления второго пользователя безопасный локальный запуск должен продолжаться, не превращая bootstrap в механизм ремонта либо повышения прав.

## What Changes

- Разделить первичное создание initial owner и read-only подтверждение ранее созданного владельца для продолжения существующего local stand.
- Подтверждать владельца по однозначной identity-записи, bootstrap-provenance назначений и необходимым активным полномочиям, а не только по email или исходному паролю.
- Разрешить повторный local `make up` после обычного развития identity: входов, дополнительных пользователей, приглашений и append-only истории.
- Сохранить fail-closed отказ без изменений при чужом/неоднозначном владельце, блокировке владельца, отсутствии необходимых полномочий или произвольной непустой identity-БД.
- Не менять production-семантику первичного bootstrap, не сбрасывать credentials/profile/sessions/roles/history и не восстанавливать отозванные полномочия.
- Добавить изолированные MariaDB-сценарии и проверку реальной цепочки `Makefile up` → CLI → identity owner; при необходимости адресно зарегистрировать owner/tests в `capability_ownership`.

## Capabilities

### New Capabilities

Нет.

### Modified Capabilities

- `operations/initial-owner-provisioning`: существующий local startup получает безопасное read-only подтверждение bootstrap-владельца после нормального развития identity, сохраняя строгий clean-create и fail-closed production bootstrap.

## Impact

Срез затрагивает `Makefile`, `bin/fmonitor2-provision-initial-admin.php`, `app/IdentityAccess/MariaDbInitialOwnerProvisioning.php`, normative contract `INITIAL-OWNER-PROVISIONING-001`, focused Runtime/Deployment/Architecture tests, verification inventory и только необходимую адресную ownership-регистрацию. Источник — текущее поведение на `origin/main` после merge #188 и owner assignment #185. Не входят POSIX modes, UID файлов, VPN, import-фильтры, инженеры, chunking, #182, алгоритмы planner/harness, исключения или merge/deploy.
