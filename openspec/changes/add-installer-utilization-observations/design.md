# Design

## Decisions

The stage-one `MariaDbInstallerUtilization` remains the only classification owner. A new analytics owner consumes its complete projection both for live summary and capture; dashboard/controllers never reimplement employment/current/upcoming rules.

An additive next-free migration creates an observation header and immutable member/detail table, registered in the canonical catalogue and recovery inventory. A local-date unique key plus one transaction gives retry/race safety. The captured detail stores minimal identities and reason DTOs, not a reference to mutable current rows.

The existing scheduler enqueues `installer-utilization.capture` once per MSK day at 03:17. Worker configuration explicitly allowlists the type and uses the normal DML principal. Missing days remain absent.

Dashboard reads live summary plus bounded historical observations; the detail route reads saved rows only. Both require `installers.read` in addition to existing dashboard access. Existing `shlz-ui` chart markup and local pilot CSS are extended; no JS chart engine is introduced.

The production symptom is diagnosed read-only against deployed configuration/data. Legacy object slots are not treated as official applied assignments: if native application facts are absent, the product must say that coverage is incomplete rather than infer freedom. The stage-one directory/card receive bounded local spacing/padding corrections; workforce status uses the stock label while integration source/timestamp stay internal and are not user-facing.

## Non-goals

Forecast, second installer chart, historical reconstruction, deployment, production writes, auto-allocation, assignment/PTO writers, financials, calendar, checklist and inspection surfaces.
