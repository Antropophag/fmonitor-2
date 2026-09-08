# Workforce sync — next launch dependency, read-only characterization

Source eec882f274902c3d4842aa73d4665411b0a855aa. Script SHA
0ce1cb3b62418d64aeb05f8bba8a52cc6605011e75a1b5768df1f8f218c6e212.
Only repository sources/docs inspected. No configured Bitrix call, import, credential
read, deployment or runtime DB change. Previous August28 research is historical,
not proof of today's portal payload or workforce counts.

## Facts changing the next action

`rapid-pilot/hourly-bitrix-workforce.php` is still a direct SQL writer of runs,
observations/current/freshness. It does not call a native sync application seam.
Fetch lacks stable ID ordering/select allowlist, total-consistency/page bounds and
validated next progression. Publication has no run replay/global serialization or
explicit unknown-commit resolution. These are predecessor observations, not target
requirements or permitted migration shortcuts; PB-14 added to behavior inventory.

The script sets observedDate BEFORE HTTP and binds it as new employed_from; source
UF_EMPLOYMENT_DATE is not consumed. This contradicts the known-date meaning in
WORKFORCE-CATALOG-001 and explicit unknown-preservation in BITRIX-WORKFORCE-HISTORY-001.
The latter is EPIC/NOT EXECUTABLE/SUPERSEDED FOR GATE2; its broad old approval cannot
be reused as delivery/publication Gate1. Only schema/canonical runner were delivered.

Native selection currently requires employedFrom:string and a valid calendar date:
ASSIGNMENT-ORDER-COMPOSITION-SELECT-001 sections around319/357;
InstallerSnapshot, SelectionEligibility and MariaDbSelectionPortalQuery. Honest
null import therefore cannot silently be enabled by inventing a sync-date lower bound.

## Pending owner decision

Async question sent: allow new assignment from confirmed current status of a FULL
workforce snapshot when Bitrix does not provide employment start, keeping date unknown,
or require the confirmed start date? No answer yet. This is separate from already
pending original reapplication-date policy. Do not weaken selection/schema contracts
or fabricate dates while waiting. Freshness threshold is also not defined by the
workforce epic; hourly cadence alone does not establish a business age threshold.

## Safe next work

Decompose workforce ingestion under OpenSpec and independent gates, preserving the
full launch requirement. First verify the public API contract using official docs
and existing redacted evidence; no real portal call is authorized this session.
Delivery/normalization/publication/catalog-read must each have executable seams;
normalization must keep unavailable dates null. Unknown employment eligibility and
any projection identity-change ambiguity require explicit Gate1 closure before
corresponding business/schema behavior. Do not implement the entire non-executable
epic or rely on fictional happy paths as proof of live Bitrix readiness.

Parent application/opening and full original HTTP remain prerequisites on their
own critical path. Native original history/download dependency is now Gate5 approved.
No full VERIFY_OK or launch approval follows from this characterization.

## Public documentation check —2026-09-07

Official user.get documentation confirms scalar `sort=ID` and `order=ASC`,
`select` as an array, and fixed50-record pages with start offsets. The old epic's
`order[ID]=ASC` candidate must not be copied into an executable transport contract.
Unspecified sorting defaults to ascending ID: the earlier phrase about missing
stable ordering means the adapter does not explicitly request it, not proof that
Bitrix currently returns unsorted pages. Inaccessible/nonexistent selected fields
can be omitted without API error, so transport validation must distinguish missing
required delivery fields without silently inventing values.
[Official user.get](https://apidocs.bitrix24.ru/api-reference/user/user-get.html).

The official scope table includes EMAIL, UF_XING, UF_EMPLOYMENT_DATE and
UF_DEPARTMENT in user_basic, whose methods cannot add/update users. Separate
user.userfield scope concerns custom fields; no new broad permission is inferred
for the already listed system fields.
[Official User Scope](https://apidocs.bitrix24.ru/api-reference/user/user-scope.html).

This check used only public documentation, not the configured portal/webhook.
It supports planning an explicit delivery contract but does not prove actual current
payload completeness, credential scopes, staffing or live synchronization success.
