# Correction — protected E2E actor-18 zero-link authority diagnosis

- Recorded: `2026-09-05T14:06:58Z`
- Author: `/root/importer_authority_review`
- Current HEAD at final source inspection: `2563590121eeadb7cc59d83049e1d6d422515243`
- Corrects:
  `docs/operations/protected-pilot-e2e-actor18-zero-link-static-diagnosis-2026-09-05.md`
- Disposition: **PRIOR PRODUCTION-DEFECT/AUTHORIZATION CONCLUSION WITHDRAWN**

This is an append-only correction. No protected test/spec/dependency or
production file was edited or run.

## Material correction

The prior diagnosis correctly identified the immediate morphology mismatch:
the protected XPath searches under `tbody/tr`, while current configured
`ProductionObjectListRenderer` emits the valid object link inside `ul/li`.
It correctly determined that actor 18 already passed authorization and that
fixture object 4512 satisfies the source reader's identity/date/case inputs.

Its authority conclusion was false. Current approved `PILOT-UI-SHELL-001`
section 5 does not require a six-column table. It requires exactly a semantic
`ul` or `ol` collection with one `li` and one canonical link per object, and
explicitly forbids native `table` and `.shlz-table-wrap`. Current
`ObjectListView.php` follows that morphology. No production table-renderer
correction is authorized by the current approved UI-shell/list contracts.

Withdraw these statements from the prior record:

- that `PILOT-OBJECT-LIST-001` supports “semantic list or table” as the current
  selected configured composition;
- that `PILOT-UI-SHELL-001` specifies an approved dense six-column table;
- that the zero count is an independently authorized production renderer defect;
- that production may be changed to satisfy the protected table XPath without
  a protected Gate 1 reconciliation.

The current approved behavior is the opposite: semantic list required, native
table forbidden.

## Exact remaining factual diagnosis

The source/fixture mapping remains:

1. Actor 18 receives 200, so `objects.read` admission has succeeded.
2. Case 1 explicitly references object 4512 in `needs_assignment_order`.
3. Legacy 4512 has nonblank registration/address/entrance and valid planned
   dates 2026-10-05 through 2026-12-20, within the pilot cutoff.
4. `MariaDbObjectListReader::read()` uses origin `all`; no state or current-date
   predicate excludes this row.
5. The configured renderer emits the object link under `ul/li` as required by
   current approved UI-shell section 5.
6. Protected test line 153 searches only
   `//tbody/tr//a[@href='/pilot/objects/4512' ...]`, so it returns zero even when
   the conforming semantic-list link exists.
7. Protected test line 156 later requires six table headings and repeats the
   table-scoped link assertion. Those assertions are incompatible with the
   current approved semantic-list/no-table contract.

Thus object 4512 remains a valid pilot fixture, but the protected test's table
axis is stale. The failure is not evidence of a production rendering defect.

## Source of the mistaken six-column claim

The six-column expectation exists in the current protected
`pilot_e2e_flow_001_test.php` and in evidence/review prose written for that
protected E2E line. The draft `PILOT-E2E-FLOW-001` section 7 also proposes an
expanded queue with process status and next-step columns. That draft is not the
current approved list/UI-shell authority: its own header marks the target E2E
blocked, and it retains superseded manual-registration behavior.

Current approved `PILOT-UI-SHELL-001` lineage selected semantic list explicitly.
The code-review and Gate-3 records for `PILOT-OBJECT-LIST-001` state that the
native table was the former regression and that the approved correction was
`ul|ol` with no `table`/`.shlz-table-wrap`. A later protected E2E review's phrase
“exact six-column DOM” cannot override that approved specification or create
owner approval for the conflicting table expectation.

## Authority and next step

Protected E2E remains blocked. Do not change the conforming production renderer
to a table, and do not edit the protected XPath or headings under the existing
RBAC Gate 3 as though they were implementation details. The representation
conflict requires a fresh protected Gate 1 reconciliation before changing the
protected test or any production presentation:

- retain current approved semantic-list membership/order/facts and no-table
  rules unless the owner explicitly approves a new presentation policy;
- remove or supersede stale table/six-column assertions in a coherent future
  protected E2E contract only after exact review and approval;
- separately reconcile the draft E2E process-status/next-step queue expansion
  with current list/UI-shell scope and original-first journey;
- do not infer approval from the current failure, old E2E review prose or the
  stale manual-registration E2E draft.

No broad product re-question is warranted. The current semantic-list policy is
already approved. The eventual owner package should ask only for approval of a
coherent protected E2E amendment if it changes the protected assertions/scope;
it must not silently reverse current UI-shell truth.

## Exact current evidence

```text
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
c97bcb3df97362a19efc9dda6ab6ac2a8224fa0d0a3a42721d7eca8b511e3cfd  specs/PILOT-E2E-FLOW-001.md
147227bde8b9afe126ee374417a9c7f5a3bac84c5e13b10d7dc1b1d9a525ee1f  specs/PILOT-E2E-RBAC-FIXTURES-001.md
a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6  tests/InstallationProcess/pilot_e2e_flow_001_test.php
66806572db401c1fc5c7087ff7cc0b20e72e4f06203040b6cbc76ee3a1326826  app/PilotHttp/PilotHttp.php
d2b98ae8103feabbc3511e4f5394dd580c790a74f64c66d0f8f9e6d4acfb069b  app/PilotHttp/ObjectListView.php
7eaa54f8658fc7bd6db77631b9fdf4020600700fa9f849396218e90a3f222b83  docs/operations/protected-pilot-e2e-actor18-zero-link-static-diagnosis-2026-09-05.md
```

## Final corrected disposition

The valid 4512 link is rendered in the approved semantic list; the protected
test looks only in a forbidden table structure. The current production renderer
must not be changed to satisfy that stale XPath. Protected E2E requires fresh
Gate 1 reconciliation, and the old manual-registration golden target remains
blocked and non-current.
