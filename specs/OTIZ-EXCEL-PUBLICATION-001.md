# OTIZ-EXCEL-PUBLICATION-001

## Простыми словами

Действующий Yii2 ОТиЗ публикует расчёт по формуле Excel и показывает использованные источники. Черновиков может быть несколько; они не считаются выплатами. Подтверждённые выплаты учитываются в следующем расчёте. Новых правил замещения черновиков или нового жизненного цикла выплат эта задача не вводит.

## Граница

Существующие SnapshotPublication::buildAndPublish/accept/read/history и OtizSettlement::completeSnapshotPayments остаются публичными операциями. Используются существующие transaction, receipt, authorization и settlement locks. Legacy rapid-pilot screens/adapters, новый entitlement registry, cross-period supersession и переработка reversal lifecycle исключены. История опубликованных снимков и выплат сохраняется без миграции сумм.

## Нормативное поведение

1. Каждый новый snapshot имеет rules_version `premium-calculation-v2-excel`; builder вызывает PremiumCalculationV2 по OTIZ-EXCEL-CALCULATION-001 с inputs по OTIZ-EXCEL-INPUTS-001. Существующий publication fingerprint `otiz-build-v1:<reportDate>` сохраняется: replay возвращает прежний ID без повторного чтения источников. Authorization/date/UUID и OPERATION_CONFLICT сохраняются.
2. paidBefore складывается только из signed paid_cents подтверждённых closure rows с closed_on<=reportDate; черновики и принятые неоплаченные snapshots не входят. Payment evidence хранит каждую полную исходную closure row как SHA256 canonical JSON (unescaped Unicode/slashes), locator `fm2_pilot_otiz_payment_closures/<id>`, label «Принятое закрытие ОТиЗ». Discipline/deadline не подменяют выплаченную сумму. Сохраняются действующие отдельные правила дисциплинарных удержаний и live settlement budget.
3. Сохраняются полные operandEvidence, sourceEvidence, paymentEvidence, formulaTrace, exclusion/blocker list и allocationVersion. `content_hash` зависит от версии, operands, sources, сумм и ordered allocations, а publication manifest продолжает защищать сохранённый результат. Изменение источника при неизменных totals меняет content_hash.
4. Object pool_cents=formula pool; closed_before_cents=paidBefore; accrued_cents=paidBefore+pool (существующий cumulative settlement target); fund_cents=fund; remaining_cents=remainingFund. distributed_cents=max(pool-net discipline at report,0); undistributed=pool-distributed. Largest remainder распределяет distributed целыми копейками с binary tab tie-break. Header totals — суммы pool,paidBefore,distributed. При blocked input premiumCalculation=null, полный blockers/sourceEvidence сохраняется. Blocked input остаётся видимым, не распределяет деньги и не принимается (BLOCKERS). Missing original deadline/corrupt certificate никогда не подменяются датой.
5. Существующая атомарная публикация header/objects/allocations/issues/receipt сохраняется. Ошибка второго объекта или receipt откатывает весь результат. Native inputs и payment evidence читаются в одном согласованном read view. Старые снимки не переписываются при новом расчёте или исправлении справки.
6. A02: ещё не выплаченный расчёт старой formula version нельзя принять/оплатить как текущий — STALE_CALCULATION; требуется новый расчёт. Read/export старых результатов и replay подтверждённой старой операции сохраняются. Расчёты одной новой версии не аннулируют друг друга только потому, что создан ещё один черновик. При оплате существующий global confirmed ledger предотвращает повторную выплату одного cumulative target. Не меняются авторизация, receipt replay, successful no_change и append-only reversal.

## Независимые примеры

Fund10000,progress100%,10days: A9000 и B9000 до выплаты. AcceptA/pay9000. AcceptB/payment даёт no_change, поскольку его cumulative target уже покрыт подтверждённой выплатой. Новый C: paidBefore9000,remaining1000,penalty100,pool900,target9900. После выплаты C900 новый Dpool90. Черновики A/B/C сами по себе не добавляют paidBefore.

Fund5/no overdue/equal tabs2,10,1: allocation ordered binary tabs1,10,2 amounts2,2,1; сумма ровно5. Discipline100 при fund10000/Kss9000 не входит в paidBefore: pool9000,distributed8900.

## Yii2 и XLSX

Форма справки требует PDF, дату справки и новый срок (причину при исправлении). Технические sourceLabel/sourceLocator не вводятся пользователем: Yii adapter сохраняет sourceLabel «Справка о переносе срока» и sourceLocator `yii-upload/<requestId>` как доказательство принятого upload. Приложение по-прежнему получает полный provenance command.

Действующие calculate/accept/payment/export показывают v2 trace: «Выплачено ранее», «Остаток до штрафа», «Штраф за просрочку», pool. Видны исходный плановый срок, справка и использованный newDeadline/PDF revision, дата ПТО либо «ПТО отсутствует». Даты источников видны в ISO формате; XLSX содержит revision source locator и числовые cells для amounts в рублях. XLSX сохраняет rules_version и соответствующие значения/источники. STALE_CALCULATION отображается как HTTP409 с понятным предложением «новый расчёт». Действующие CSRF, role и method protections сохраняются.

Root tests покрывают recurrence через реальные publication/payment seams, старую version, отсутствие фактов при отказах, atomic failure, provenance binding, копейки и действующий native mobile browser certificate→calculate→accept→pay→calculate. Никаких legacy rapid-pilot compatibility flows. Focused local checks и independent Gates3/5; один полный exact-source CI без локального full suite.
