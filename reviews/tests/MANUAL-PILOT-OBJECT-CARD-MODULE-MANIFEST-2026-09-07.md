# Object card module manifest — review draft

Дата: 2026-09-07
Статус: **APPROVED — independent test review; focused execution pending**

## Scope

Изменён только внутренний script-manifest oracle в
`tests/InstallationProcess/pilot_object_card_001_test.php`. Production code и
остальные card expectations не изменялись.

Исторический full verify достиг точного stale assertion:

```text
Example A broad reader without capability script has only the approved src attribute:
/pilot/assets/object-details.js
Expected: 1
Actual: 2
```

## Основание

`docs/operations/autonomous-ui-repair-restart-handoff-2026-09-07-1120Z.md`
фиксирует approved repair: ObjectDetails script загружается как ES module, чтобы
инициализировать публичные SHLZ tabs. Текущий `ObjectCardView` выдаёт
`<script type="module" src="/pilot/assets/object-details.js">`.

Test oracle теперь требует два exact ordered manifest:

```text
navigation.js   -> {src: /pilot/assets/navigation.js}
object-details  -> {src: /pilot/assets/object-details.js, type: module}
```

Проверка строит полный map атрибутов, сортирует ключи и сравнивает exact equality.
Поэтому новый лишний attribute, удалённый `type`, другой type/source или иной
порядок scripts остаются обнаружимыми. Сохранены assertions: ровно два scripts,
пустой inline content, запрет inline event handlers и javascript URLs, GET/HEAD
parity, RBAC, card facts/structure и отсутствие недоступных mutation controls.

## Проверка автора alignment

До завершения текущего full verify DB test намеренно не запускался.

```text
php -l tests/InstallationProcess/pilot_object_card_001_test.php
PASS

git diff --check -- tests/InstallationProcess/pilot_object_card_001_test.php
PASS
```

Независимый reviewer должен проверить exact test hash ниже и после освобождения
verification environment выполнить focused DB test. Этот draft сам по себе не
является Gate 3 approval.

```text
dc47353eb868c76434d4303a62b03361c6b2eb4bb1b78ec9dc3f720709c608aa  tests/InstallationProcess/pilot_object_card_001_test.php
```

## Независимый review

Reviewer: `/root`, не автор изменения теста (`/root/architecture_diagnosis`).
Проверен указанный hash/diff, actual ObjectCardView и основание ES module в
approved UI repair. Изменение соответствует разрешённому интерфейсу и не
ослабляет exact attribute oracle: неизвестные атрибуты, отсутствие type, неверные
source/type, inline content и изменение порядка остаются ошибками. Остальной
тест не изменён. Verdict **APPROVED** для тестового reconciliation. Focused GREEN
ещё не заявляется: общий verify занимает тестовое окружение.

## Последующее focused исполнение

После полного verify выполнен focused card test с тем же test-admin окружением.
Script-manifest assertions прошли. Следующее независимое расхождение:
`Example A broad reader without capability visible literal/order: 77-000123`,
expected true, actual false. Whole card test **FAIL**, не объявлен GREEN.
Private log: `runtime/card-module-focused.log` в manual-pilot state directory.
Первый запуск без suite environment отдельно завершился setup denial из-за
исторического fallback DB password; это не product RED и не результат проверки
module manifest. Следующее расхождение исследуется до фиксации завершённого пакета.
