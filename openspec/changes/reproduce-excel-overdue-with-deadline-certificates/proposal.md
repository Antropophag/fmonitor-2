## Why

Текущий native-расчёт ОТиЗ расходится с подтверждённой рабочей Excel-книгой: он применяет штраф до вычета прежних выплат, не имеет документированного переноса срока и читает дату ПТО из устаревающего snapshot распоряжения. После завершения #17 владелец поручил отдельным PR воспроизвести утверждённую последовательность расчёта и дать сотрудникам/Руководителю ФКР один проверяемый способ загрузить обязательную PDF-справку о переносе.

## What Changes

- Ввести append-only справку о переносе срока: сотрудник или Руководитель ФКР загружает passive PDF, дату справки и новый срок через одну публичную application operation; исправление создаёт новую неизменяемую версию.
- Добавить exact capabilities `deadline_certificate.write` и `deadline_certificate.read`: write по умолчанию только активным `fkr_operator|manager`, read этим ролям и `otiz_specialist`; инженеры стройконтроля и администратор не получают доступ неявно.
- При публикации выбирать текущую известную принятую версию справки независимо от её документной даты; без справки использовать доказуемый исходный Excel `T`. Field evidence сопоставляет `T` с raw `plan_finish_date`, но owner/timing его фиксации ещё требует решения. Неполное или противоречивое evidence блокирует расчёт.
- Читать актуальную дату акта ПТО из native completion root/correction chain и использовать её даже тогда, когда она позже report date, как подтвердил владелец.
- Выпустить новую версию расчёта: календарные дни, `kss=max(0,10000-100*daysLate)`, сначала вычесть прежние выплаты из начисления за прогресс, затем применить штраф к остатку, всю арифметику вести в копейках.
- Распределять все копейки пула методом наибольших дробных остатков с постоянным tie-break, сохраняя точную сумму allocations.
- Сохранять в новом snapshot точные версии правил, operands и provenance справки/ПТО; прежние опубликованные и выплаченные snapshots не менять. Невыплаченный новый расчёт использует только новую версию.
- Ввести object-level entitlement identity и один payment application seam: принятие нового snapshot supersede-ит прежнее невыплаченное основание того же объекта даже при одинаковой formula version; payment сериализуется по объекту и учитывает closures across snapshots.
- Добавить additive canonical schema v24 для certificate history, attempts, entitlement events и private-file references вместе с recovery/backup доказательствами.
- Глобальный Excel-переключатель `$EU$2` не реализовывать до отдельного решения владельца. Эквивалент возможен только как явное ограниченное waiver с полномочием, причиной, сроком и аудитом; этот условный slice помечен `NEEDS_GRILL`.
- `NEEDS_GRILL`: выбрать source policy для исходного `T`: current object-card capture каждого snapshot, immutable selection/application capture или отдельное подтверждение ФКР. Это блокирует только original-deadline seam/schema, а не certificate/calculator/payment contracts.
- `NEEDS_GRILL`: разрешить конфликт повторного cumulative Excel-расчёта с interval rule при отсутствии нового прогресса (`fund100, Kss0.9, paid90 → literal pool9`). Это блокирует recurring-snapshot money acceptance, но не certificate sub-contract.

## Capabilities

### New Capabilities

- `otiz/deadline-transfer-certificates`: атомарная публикация, исправление, чтение и скачивание версионированной PDF-справки о переносе срока.
- `otiz/excel-overdue-calculation`: versioned operands, утверждённая Excel-последовательность просрочки и консервативное распределение всех копеек.

### Modified Capabilities

Нет.

## Impact

Изменяются native OTIZ input/calculation/publication/acceptance/payment seams, карточка объекта и HTTP adapter, private artifact storage, capability catalogue, canonical schema/recovery manifests и focused browser/HTTP/DB tests. `rapid-pilot` только вызывает application/read seams и отображает результат. Не входят production import/backfill справок, Excel `Vn`, переписывание старых snapshots/closures, внешний перевод денег и глобальный waiver `$EU$2`.
