## Why
Bitrix delivery request использует key `select`, который checker ошибочно считает SQL.

## What Changes
Узкое распознавание quoted PHP array key перед `=>`, с сохранением SQL detection.

## Capabilities
### New Capabilities
- `architecture/php-array-key`: корректное различение key и SQL.
### Modified Capabilities

## Impact
Только tools/architecture/check.py и его CLI fixtures; baseline неизменен.
Executable contract ARCHITECTURE-PHP-SELECT-ARRAY-KEY-001.
