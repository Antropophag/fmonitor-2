## Why

Native selection и registry reader одобрены, но original command всё ещё читает
physical orders и не принимает dateless selection. Подключение preflight и locked
validation вместе открывает прямую загрузку оригинала без PDF-шаблона.

## What Changes

- Подключить original application к approved selection identity через явно выбранный
  production constructor; основной `submitAssignmentOrderOriginal` остаётся одним owner.
- Под тем же case lock, что использует selection, повторно проверить composition
  и допустимость initial upload для текущей pending selection.
- Сохранить correction/replay/audit и защиту replaced selection. Старый physical-only
  constructor не переписывается ради исторической совместимости.

## Capabilities

### New Capabilities

- `pilot/selected-composition-original-binding`: direct original для native selection
  с согласованными preflight и locked validation.

### Modified Capabilities

Нет изменения main specs; standalone reader остаётся историческим read API.

## Impact

Slice `ASSIGNMENT-ORDER-SELECTED-ORIGINAL-BINDING-001`, actors сотрудник/Руководитель
ФКР. Authority — approved original command, SELECT-001 section17 и native Gate5,
REGISTERED-COMPOSITION-READER-001. Target seam — прежний original application.
Вне scope: PDF templates, HTTP, применение состава/открытие, migration registration,
legacy writer conversion и historical import. Новых продуктовых решений нет;
точное отображение stale selection на существующий original outcome — technical Gate1.
