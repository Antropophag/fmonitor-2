# ERP customer/production document relation correction

Owner reported missing shipment for59740.25; root diagnosed/repaired read selection in existing authorized shortcode-review-prod mode. Bounded established source bug: root authored tests+onequery change; independent finalreview required. No sourceERP writes or schema changes. Actual ERP shows customerorder59740.25 with2productionorders linked by ДокументОснование but none withmatchingnumber; dates exist readiness2026-08-10/fullshipment2026-08-21/firstshipment2026-07-27. Oldauthoritativeaggregate returned0 andnormalizerdiscardedstage-onlyrow.

Correction: productionpresence EXISTS on prod.ДокументОснование=sale.Ссылка, existing bounded IN parameters, qualifiedtables, GROUPBYsale.Номер, MAXdates/sentinelnull preserved. Avoid multiplicative production joins; no relianceon coincidental ordernumber equality. Stable spec ERP-EQUIPMENT-FACTS-001 source-contract corrected from primary ERP evidence; othersemantics unchanged.

RED independentrelationalfixture executes generatedSQL: oldquery returnswrong-numberdecoyORPHAN andmisses59740.25. GREEN afterfix coversdifferentproductionnumbers, multiplelinkedproductionrows/storageMAX, false-numbermatch exclusion andsentinelnull. Existing source-scope/atomicchunk test PASS. Exact newSQL verified readonly onrealERP returns one59740.25 with expected dates. Primary evidence `~/.local/share/fmonitor-2/erp-order-link-20261030/` and prior diagnosis shipment-59740-20261030. FullCI/ordinary gates owner-deferred, no fullsuite/nooverallGREEN claim.

Baseline host/PHP/worker source allsame captured; deployment updated all3 and the existing sync owner/queue completed before the production read verification below.

Production live verification is retained in `~/.local/share/fmonitor-2/erp-order-link-20261030/after.jsonl`. Customer order `59740.25` projects object `1447` with readiness `2026-08-10`, first shipment `2026-07-27`, and full shipment `2026-08-21`. ERP jobs `2482`, `2479`, and `2476` each completed on the first attempt. This confirms the corrected document relationship on the live read path; the evidence contains no ERP write.
