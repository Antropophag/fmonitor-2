# Selection must preserve existing assignment projections

Date: 2026-09-05. Author: `/root`.
Inspected base: `7bdc89a6d15c2ca02e641aaaa6399356c61a57c9`.

## Finding

`MariaDbInstallerDirectoryReader` requires `o.status='registered'` but selects
`MAX(version_no)` over every order for the case, without a status filter in
that subquery. Adding a newer prepared/selected order can therefore make the
existing registered order fail the directory predicate, even though its
members and intervals are unchanged.

`MariaDbInspectionCaseDirectory` instead selects the latest registered order.
These two existing projections would disagree after a newer unregistered
selection. Neither predicate is accepted here as the future original-based
applicability rule; that belongs to its own lifecycle contract.

## Read-only diagnostic

At the inspected SHA a MariaDB SELECT-only CTE used two fictional orders:
`(id=81, case=4512, version=1, status=registered)` and
`(id=82, case=4512, version=2, status=prepared)`.
The only member was `(order=81, installer=7001, action=assign,
valid_from=2026-09-01, valid_to=NULL)`, evaluated on 2026-09-05.

Copying the directory's exact status/max-version/action/date predicate into
the CTE yielded:

```json
{"current_directory_count":"0","unchanged_registered_member_count":"1"}
```

No tables/files/users were created or changed. This is diagnostic evidence,
not a qualifying Gate 2 test, production failure replay or approval to fix code.

## Required selection acceptance

Persisting a new selection MUST preserve not only interval rows but also
observable current assignments, availability counters and inspection actor/
installer attribution until the separately approved applicability transition.
An initial-only fixture would miss this issue. The executable Gate 1/RED matrix
must contain an existing applicable order followed by a newer unaccepted
selection and prove all relevant public projections remain unchanged.

Do not implement an isolated `MAX(registered)` fix from this record: it would
retain legacy status semantics and does not by itself implement original-based
applicability. Preserve history; reconcile the owning lifecycle seam first.

Exact SHA-256:

```text
d1dd2e1a4041ec6380beaba4e2b017646ee404832a76252565751439de56ce69  app/PilotHttp/MariaDbInstallerDirectoryReader.php
6d096c0737ad9197375f92019a3d1103e0941c219648b742f3fe385a5045096c  app/InspectionEvidence/MariaDbInspectionCaseDirectory.php
5cd931da1ff1bcd356ba2177a3edd0bb56b6aeee1d3f4accb477bd79cbc4a26a  app/InstallationProcess/MariaDbInstallationProcessEnvironment.php
```
