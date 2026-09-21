# Change: показать статус отгрузки в очереди стройконтроля

## Why

Issue #16 требует различать первую и полную отгрузку без открытия карточки. Issue #12 уже доставил authoritative current projection из ERP.

## What Changes

- Расширить read projection очереди двумя nullable датами отгрузки.
- Показать один приоритетный компактный доступный индикатор и пояснение.
- Не показывать положительное состояние при отсутствии подтверждённых shipment dates.

## Impact

Только Yii2 очередь стройконтроля, её CSS/assets и focused HTTP test. Записи, ERP sync и process lifecycle не меняются.
