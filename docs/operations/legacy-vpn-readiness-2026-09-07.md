# Legacy FMonitor VPN readiness — 2026-09-07

Status: **reachable host, database read path unavailable**.

The corporate VPN route reached the configured legacy SSH host and the existing
SSH identity authenticated. The host enforces a restricted command and forwarding
policy: a no-op remote command was rejected and a short-lived local forwarding
attempt was administratively prohibited. Direct TCP connectivity to MariaDB on
the VPN host was refused. The existing read-only inspected application
configuration points to a host-local database endpoint, so it cannot be used
from this workstation without an allowed tunnel or database endpoint.

No legacy rows, personal data, documents, users, secrets or DSNs were printed or
copied. No source or target write and no Bitrix call occurred. Aggregate dry-run
counts are therefore **unavailable**, rather than inferred from stale evidence.

The prepared aggregate method uses cutoff `DATE(workdatestart) >= 2026-10-01`.
“Not opened” requires an empty/zero `factworkstartdate` and, when present,
`workstarted` empty or zero. “No checklist history” excludes an object on any
match in `fm_install_checklists_values_log` (including value `0` retractions),
`fm_install_checklists_values`, `fm_install_checklist_files`, or attribution
history linked through `fm_install_checklists_values_installators_log`. Only
counts are selected; eligible object rows are not returned.

Required operational input: either a read-only MariaDB host/port reachable over
the VPN with the existing database identity, an SSH policy allowing local
forwarding to the host-local database, or a server-side read-only aggregate query
facility. Credentials themselves must remain outside the repository.

## Correction and validated direct route

The earlier SSH endpoint was the configured Git SSH endpoint. Its restricted
command/forwarding behavior does **not** establish database-host availability.
After the owner supplied a dedicated read-only database identity, the documented
corporate database hostname was reached directly on MariaDB port 3306 over the
VPN. A short-timeout connection, `SELECT 1`, and a consistent read-only
transaction succeeded. Credentials and DSN were not printed.

Aggregate result at cutoff `2026-10-01`:

- all legacy objects: 2,782;
- planned start on/after cutoff: 170;
- unopened by `factworkstartdate` plus `workstarted`: 170;
- excluded for any checklist value/log/retraction, photo, or attribution history: 22;
- eligible dry-run candidates: **148**.

This was an aggregate read only. No candidate rows were returned or copied and no
target import was performed.

## Owner correction — planned date removed

The owner subsequently removed the planned-start cutoff: overdue, missing-date,
and future objects are treated alike when no work actually began. The earlier
148 count is therefore a superseded dated subset and is not the import count.

The current aggregate predicate is solely:

1. no actual opening (`factworkstartdate` empty/zero and `workstarted` empty/zero);
2. no checklist activity: no append-only value log (including retractions), no
   nonzero current mark, no checklist photo, and no current or historical
   installer attribution.

Validated read-only aggregate over all 2,782 objects:

- opened: 257;
- unopened: 2,525;
- unopened with checklist activity: 2,143;
- eligible without opening or checklist activity: **382**.

Current checklist rows with value zero were checked separately. There are zero
unopened objects for which such rows exist without any log, photo, or attribution
history. Thus default-zero current rows caused no false exclusion in this source
snapshot. The current predicate nevertheless uses only nonzero current values as
activity; history tables remain authoritative for completed then retracted marks.

## Private source snapshot

A later consistent read-only capture for the manual stand found one additional
unopened object with checklist activity. At capture time the live counts were
2,525 unopened, 2,144 excluded for activity, and **381 eligible**. This newer
capture supersedes the earlier transient 382 count.

The private 0600 snapshot is stored outside the repository at
`~/.local/state/fmonitor2/manual-pilot-20260907/source-snapshot.json`. It contains
only the agreed object identity/location fields, raw planned dates, six technical
fields with display dictionary values, per-object negative eligibility proof, and
the normalized checklist template. It contains no users, personnel history,
documents, payments, or control-engineer user mapping.

Snapshot verification: 381 objects; 8 checklist sections; 42 definitions; items
1–41 total 85%; document item 42 totals 15%; full file SHA-256
`c64e8e8813c9e982ff3ca378a5dda79991bbfff31943ff79a776a2b9aafc6184`.
