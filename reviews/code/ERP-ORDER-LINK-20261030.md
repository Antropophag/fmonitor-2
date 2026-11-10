# ERP customer/production order relationship — independent code review

Reviewer: `/root/otiz_ui_review`, not an author of code or tests.
Verdict: **APPROVED**, 2026-10-30. Base: `dbc5de11`.

## Finding

No blocking finding. The old equality between customer and production order numbers
was not an identity relationship and omitted real order `59740.25`. The replacement
uses the authoritative document relation
`prod.ДокументОснование = sale.Ссылка` inside `EXISTS`.

`EXISTS` is appropriate here: it proves production-order presence without multiplying
the storage rows used by `MAX`. The query retains the bounded positional `IN`
parameters, fully qualified ERP objects, grouping by customer order number, canonical
date conversion, sentinel-to-NULL behavior, and the independent shipment-stage type,
deletion and date filters. It adds no fallback, cache, schema change or ERP write.

The generated-query relational regression executes the actual SQL and proves that
different production numbers linked by document identity include `59740.25`, multiple
linked production rows do not duplicate the result, a coincidental same-number row
linked to another document is excluded, and MAX/sentinel behavior remains stable.
Reviewer rerun: relational regression PASS; PHP lint and `git diff --check` PASS.
The existing source-scope/atomic-chunk test is recorded PASS in `source-green.log`;
the reviewer could not repeat its DB portion because no local MariaDB listened on the
default port. Read-only live ERP evidence returns readiness `2026-08-10`, full shipment
`2026-08-21`, and first shipment `2026-07-27` for `59740.25`.

Full local suite/CI was not run under the owner-authorized short production cycle.

## Exact runtime binding

- `b434e9e205d4fd0e4fbb071e9f976e4fc007a793391be3e7e65f2d7e0442badb` `app/InstallationProcess/MariaDbErpEquipmentFactsSource.php`
