## Why

Выплаты, удержания и сторно ОТиЗ всё ещё записываются транзакциями presentation-adapter `rapid-pilot/Otiz.php`, поэтому финансовое основание не сериализовано между принятыми срезами и допускает повторную выплату A02. #70 переносит эти append-only факты к одному владельцу `app/Otiz` в рамках полного перехода #76.

## What Changes

- Ввести ровно три текущие операции: discipline удержание, автоматическое полное закрытие accepted snapshot и сторно; не разрешать arbitrary paid/deadline input.
- Считать доступный бюджет по legacy financial object через все accepted snapshots: `max(0, accepted accrued − global signed closures)`, не вычитая `closed_before` второй раз.
- Сериализовать конкурирующие операции по одному financial object, хранить operation UUID/fingerprint/replay result и атомарно записывать closure, event и receipt.
- Сторно оформлять единственным отрицательным append-only событием, связанным с исходной записью; историю не переписывать.
- Оставить Yii/HTTP адаптеру только parsing, current authorization/CSRF и mapping результата; удалить заменённые SQL/transaction helpers из rapid adapter.
- Не менять `PremiumCalculation`, нормы/формулы #66, распределение по людям, publication/acceptance snapshot или UI.

## Capabilities

### New Capabilities

- `otiz/settlement-owner`: атомарная фиксация выплат, удержаний и сторно с межсрезовым A02 budget, replay и append-only history.

### Modified Capabilities

Нет.

## Impact

Затронуты `app/Otiz`, additive canonical MariaDB migration, Yii command/controller composition и временный `rapid-pilot/Otiz.php`. Финансовое основание подтверждено текущей моделью как legacy `object_id`: один native installation case читает оперативные факты, но premium/closure history и snapshot object адресуются устойчивым financial object через срезы. Рабочие данные и формулы не мигрируются и не пересчитываются.
