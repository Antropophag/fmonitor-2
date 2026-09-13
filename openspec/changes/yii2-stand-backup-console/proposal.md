## Why

№76 переводит production application и console entrypoints на Yii2/PHP. Предыдущий кандидат ошибочно вынес backup control plane в отдельный Python runtime; новый срез исправляет ownership и оставляет Python только внешнему verification harness.

## What Changes

- Добавить Yii2 console commands `stand-backup/create` и `stand-backup/verify`.
- Разместить backup protocol и filesystem ports в PHP-модуле `app/RuntimeRestore`.
- Сохранить exact target, content-addressed bundle, independent hash, crash-consistent replay и safe-output требования.
- Удалить `tools/delivery/stand-backup.py` из поставляемого candidate.
- Перепривязать verification к PHP public seam; Python допускается только как внешний black-box test runner.
- Не выполнять live backup, restore, reset, deployment или stand mutation.

## Capabilities

### New Capabilities

- `operations/yii2-stand-backup-console`: Yii2/PHP console owner проверяемого backup bundle.

### Modified Capabilities

Нет.

## Impact

Actor — deployment operator; source oracle — issue #76 и exact PR #113. Production code меняется только в PHP `app/RuntimeRestore`, `app/YiiRuntime/Commands` и Yii console config. Public seam — `php bin/yii stand-backup/create|verify`. Старый Python WIP сохраняется как review history, но не входит в candidate.
