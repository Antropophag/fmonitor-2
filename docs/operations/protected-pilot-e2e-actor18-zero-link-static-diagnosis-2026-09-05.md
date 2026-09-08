# Protected E2E actor-18 zero-link failure — static diagnosis

- Date: `2026-09-05`
- Auditor: `/root/importer_authority_review`, read-only
- Current HEAD: `f594c4fa62b6e02fb85915c27242c11a7bccdc82`
- Source evidence: existing prepared verification log and current tracked source
- Disposition: **VALID PILOT FIXTURE; CONFIGURED RENDERER CONTRACT MISMATCH**

No protected test/spec/dependency or production file was edited. No test,
harness or server was run for this diagnosis.

## Exact failure mechanism

The protected test first asserts that actor 18 receives HTTP 200 for
`GET /pilot/objects`. It then evaluates:

```xpath
count(//tbody/tr//a[@href='/pilot/objects/4512' and contains(normalize-space(.),'4512')])
```

The existing log reports expected 1, actual 0 at that XPath assertion.

Static source establishes the full path:

1. `PilotHttpApplication` authorizes `/pilot/objects` through
   `ProductionLocalObjectListAuthorization` and exact `objects.read`. A denial
   would return 401/403/503 before rendering. The observed 200 proves that
   admission completed.
2. `ProductionPilotHttpDependencies::objectList()` supplies
   `MariaDbObjectListReader`. Its default `read()` calls `readPage(1, 'all')`.
   The `all` origin adds no provenance filter.
3. The fixture has exactly one process case: case 1 references legacy object
   4512 and state `needs_assignment_order`. The reader does not filter on case
   state or current date. It joins the legacy row and accepts positive matching
   identity, nonblank address/entrance/registration number, valid planned start
   `2026-10-05`, and valid adjusted finish `2026-12-20`.
4. If the optional three-table provenance family is absent, the reader uses its
   legacy/demo-fixture path and still includes the row. If that family is fully
   present, origin `all` still does not exclude a coherent demo row. No source
   predicate shown by the fixture makes the result empty.
5. The configured production renderer currently emits nonempty objects as
   `<ul class="fm2-queue-list"><li ...><a href="/pilot/objects/4512">...`.
   It emits no `tbody` or `tr`. Therefore the exact link can be present in the
   HTTP body while the table-scoped XPath returns zero.

The failure is thus not actor authority, date cutoff, case state, object ID,
source membership or empty reader output. It is the mismatch between the
configured renderer's current list markup and the protected assertion's
approved table axis.

## Fixture eligibility

Object 4512 is not an obsolete/non-pilot fixture:

- it has an explicit imported `fm2_installation_cases` row;
- it is not opened (`needs_assignment_order` and no opening fields);
- planned start `2026-10-05` is on/after the product cutoff 2026-10-01;
- address, entrance, registration number and finish date are all present;
- `PILOT-OBJECT-LIST-001` independently uses object 4512 with the same identity
  and date family as an included imported object.

Neither current PRODUCT nor pilot scope supports dropping it from the queue.
Changing the protected expected count to zero, changing 4512 to a pre-cutoff
object, or adding a state/date omission heuristic would contradict existing
approved membership.

## Authority disposition

The production mismatch is independently supported by approved contracts:

- `PILOT-OBJECT-LIST-001` permits a semantic list or table and requires one
  accessible exact link for every valid imported case;
- `PILOT-UI-SHELL-001` specifies the configured queue as the approved dense
  six-column table composition;
- the owner-approved and Gate-3-reviewed RBAC fixture requires actor 18's
  admitted response to retain that inherited object-list representation and
  explicitly expects the table-scoped link before downstream journey actions.

Accordingly the failure can proceed as a production configured-renderer
correction under the existing approved behavior, using its required delivery
gates. It does not need a new protected Gate 1 merely to make the current
renderer satisfy the already approved table/list representation. The protected
test and its dependencies remain untouched.

This diagnosis is not implementation authorization or a Gate verdict. Before a
production change, the responsible slice must verify the exact currently
approved `PILOT-UI-SHELL-001` table columns/classes and existing renderer tests,
demonstrate/reuse valid RED and obtain required independent review. A proposed
change that alters membership, business state, routes or protected assertions
would exceed this diagnosis and return to Gate 1.

The result does not make the old manual-registration golden journey current or
ready. Once this first renderer failure is removed, the protected test will
continue into its known stale/manual-registration and original-first dependency
boundary, which remains governed by the separate planning audit.

## Exact evidence mapping

| Evidence | Current fact |
|---|---|
| `tests/InstallationProcess/pilot_e2e_flow_001_test.php:139–145` | legacy 4512, dates 2026-10-05/2026-12-20, imported case 1, `needs_assignment_order`, actor 18 grant fixture |
| `pilot_e2e_flow_001_test.php:152–153` | status 200 succeeds, then table-scoped exact-link count fails 1→0 |
| `app/PilotHttp/PilotHttp.php:296–303` | `read()` uses origin `all`; case-state/date cutoff are not filters; valid imported legacy row is projected |
| `app/PilotHttp/PilotHttp.php:326` | local authorization precedes list read/render; successful path returns 200 |
| `app/PilotHttp/ObjectListView.php:8–11` | configured renderer uses `ul/li`, not `table/tbody/tr` |
| `PRODUCT.md` and pilot spec | imported unopened objects with planned start from 2026-10-01 are in pilot |
| prepared log | exact 200-response DOM assertion reports expected 1, actual 0 |

## Exact SHA-256

```text
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
147227bde8b9afe126ee374417a9c7f5a3bac84c5e13b10d7dc1b1d9a525ee1f  specs/PILOT-E2E-RBAC-FIXTURES-001.md
a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6  tests/InstallationProcess/pilot_e2e_flow_001_test.php
66806572db401c1fc5c7087ff7cc0b20e72e4f06203040b6cbc76ee3a1326826  app/PilotHttp/PilotHttp.php
d2b98ae8103feabbc3511e4f5394dd580c790a74f64c66d0f8f9e6d4acfb069b  app/PilotHttp/ObjectListView.php
b77e9672237088a767d7e7f123b802aa6abc09d111d26df7aeae799eed96be4a  app/PilotHttp/ProductionPilotHttpEntrypointFactory.php
a4306af9b9ec458d2cd2343db27dd8d6dc44bd07cf9a9203a8e395a16dbc5772  docs/operations/protected-pilot-e2e-blocker-planning-audit-2026-09-05.md
f5ab9e710c1b881d6f6369999c22d52199cc2064a41b8a85ef261f40c73ad623  /tmp/fmonitor2-verify-d1a5d09-prepared.log
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
```

## Final disposition

Object 4512 should be present. Actor 18 is already admitted. The zero XPath
count is caused by configured production `ul/li` markup where the approved
protected check requires the inherited six-column table axis. Correct the
renderer through the existing approved behavior gates; do not alter the
protected fixture, infer a new membership rule, or treat the later legacy
manual-registration journey as ready.
