# BITRIX-ORDER-DOCUMENT-LINKS-001 — ссылки технической документации заказа

## Scope

Только read-only legacy-контракт ссылок Битрикс в native owner. Не входят #12/#30/#40/#66, integration/recovery redesign, object identity, `rapid-pilot` и shared verification/harness.

## Legacy evidence

- `../fmonitor/application/controllers/Integration.php::create_public_link_folders`: direct children, `disk.folder.getExternalLink`, folder `ID/NAME`.
- `expandFolderName`: форма диапазона `A.F-B.F`.
- `../fmonitor/application/controllers/Tables.php`: folder name сопоставляется с `fm_maintable.zavnumber`.
- `showcell.php`: ссылка отображается в строке объекта.

Legacy — только evidence; runtime dependency отсутствует.

## A1 — Delivery

Bounded adapter SHALL читать direct child folders настроенного root только через `disk.folder.getchildren` и `disk.folder.getExternalLink`. Configuration, transport, API, JSON/schema, pagination и limits SHALL fail closed без partial list. URL SHALL быть HTTPS, без credentials и exact configured Bitrix origin. Secrets/raw upstream body MUST NOT возвращаться или сохраняться. Содержимое документов MUST NOT загружаться или проксироваться.

## A2 — Mapping

Единственный ключ SHALL быть nullable string `fm_maintable.zavnumber`; `regnumber`, object id и иные identity MUST NOT быть fallback. Обычное folder name сохраняется exact. `A.F-B.F` с одинаковой дробной частью раскрывается включительно; другое дефисное имя отклоняет refresh.

## A3 — Safe refresh

Единственный public application owner SHALL принимать complete validated list и в одной transaction полностью заменять current owned links projection. Exact duplicates схлопываются. Complete empty list очищает projection. Любая failure MUST оставить предыдущую projection неизменной. Runs, snapshots, replay IDs и audit framework не требуются.

## A4 — Import/read/card

Forward migration SHALL добавить nullable binary-exact `zavnumber` в managed mirror и одну links table с необходимыми unique/index constraints. Existing legacy snapshot/import SHALL переносить `zavnumber` byte-exact. Read owner SHALL выбирать distinct links exact по order number. Несколько объектов одного заказа получают одинаковые ссылки; соседние номера не смешиваются.

Секция SHALL отображаться только в существующей object card после `objects.read`, экранировать source folder name и различать «Номер заказа не указан», «Техническая документация не найдена» и «Техническая документация временно недоступна». Отказ секции не ломает карточку.

## A5 — Console

Yii2 console command SHALL выполнить один delivery и вызвать public application owner. Command MUST NOT писать SQL напрямую и SHALL вернуть только безопасный `published` или safe failure result.

## A6 — Hourly schedule

Existing native Jobs scheduler SHALL ставить один sync job на каждый часовой slot `Europe/Moscow`. Повтор того же slot MUST NOT создавать duplicate; после простоя SHALL ставиться только текущий slot без backlog. Existing worker SHALL вызвать ту же console/application composition без прямой записи projection и без нового scheduler/worker framework.

## Verification

Focused tests покрывают parser/delivery, transactional replacement/failure preservation, schema/import, exact read, card authorization/states, console delegation и hourly scheduling. Полный suite выполняется только exact-source GitHub CI.
