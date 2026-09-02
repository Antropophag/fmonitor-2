# Independent test review — INSTALLATION-COMPLETION-SCHEMA-001

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **CHANGES_REQUESTED**

The reviewer did not author or edit the specification, tests, production code,
RED evidence or OpenSpec artifacts. This review record is the only edit.
Gate 4 is not authorized against the reviewed test bytes.

## Controlling contract and reviewed artifacts

The owner approval anchors the normative bytes at
`c63ed10eb22d69ed7e86274a3008e6e991204166e44cb2ad9e8b00d1be686181`.
The current executable-spec hash is
`c6f3cf995a81d214559d4078696f82d6d2cfaa1123120cb91775fc5c6b5c5448`;
the approval record explains the bounded post-approval administrative status
transition and no normative mismatch was found.

Reviewed hashes:

```text
240148c3c9fb15d82d34a312a6396c0f53640511b40e3e2574844d34c46dd6d7  tests/InstallationProcess/installation_completion_schema_001_test.php
dc10dfafcb8be9a52f757821ff347c6fc86373b7d1cd50665d7d2b7046bb83b0  tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
1331bc60607cd8a6c7f7e87a0bcb1d837a727c90e00465185dfd789c2ebbb682  tests/Support/installation_completion_runtime_router.php
c6f3cf995a81d214559d4078696f82d6d2cfaa1123120cb91775fc5c6b5c5448  specs/INSTALLATION-COMPLETION-SCHEMA-001.md
4c47e0577ba63a51489ba0c3cecb47ff20c08a6bf1e238a399b00f29e73f4f46  docs/operations/installation-completion-schema-owner-approval.md
9e3fc99ba89150f97c25eecef7892406bf5e3dc65deddeb573b7cd0742cee95f  docs/operations/installation-completion-schema-red-evidence.md
2c34703b9601f5225123b930fc22e05e30a57bf2806689ebc83a5a457f5acae1  openspec/changes/canonicalize-installation-completion-schema/specs/deployment/canonical-installation-completion-schema/spec.md
```

## Required findings

### 1. The exact-family success fixture is internally unable to become GREEN

`icrSeed()` creates no completed checklist operations. The reviewed completion
POST therefore reaches the existing public command with progress `0` and must
return `409`, while the test accepts only `200` or `303`. After runtime DDL is
removed, this becomes a second failure unrelated to the approved readiness
behavior.

The same exact-mode branch snapshots all prefixed rows before the requests and
then requires the snapshot to remain identical. A valid `record_pto` POST at
85% must instead append one root fact and return `303`. Consequently a fixture
corrected only to reach the valid command would still fail its final no-DML
assertion. The test must seed independently calculated 85% progress, require
the exact successful response, and assert the one expected append while proving
that schema and unrelated rows/counters are unchanged. Missing/drift variants
must retain the zero-DML assertion and use the same otherwise-valid request so
the `503` demonstrably precedes completion DML.

This is a test defect, not an additional intended RED: the retained evidence
identifies only request-time DDL as runtime RED and §5E explicitly preserves
observable DML behavior on an exact family.

### 2. Required HTTP response metadata is not asserted

`icrUnavailable()` never checks `Cache-Control: no-store` for queue, card,
checklist or completion failures. For HEAD card/checklist it checks an empty
wire body but not the declared `Content-Length` matching the GET body, which is
an explicit §5F requirement. A production implementation can therefore omit
both mandatory headers and still pass this matrix.

Add exact, duplicate-sensitive header assertions, including no-store on all
failure seams and GET/HEAD content-length equivalence. Keep the existing
Retry-After, body, no-redirect and queue-reference checks.

### 3. The claimed exact structural fingerprint is materially incomplete

`icsAssertFinal()` reduces columns to name/type/nullability, indexes to
name/uniqueness/column, and constraints to counts. It does not assert column
defaults, auto-increment/generated state, generation expressions, character
charset/collation, index sequence details/subparts/order/type/visibility, exact
FK names and ordered local/referenced identities, or normalized identities of
the three CHECK expressions. Those fields are queried by `icsState()` but are
never compared to test-owned expected values on first creation. Repeat equality
cannot detect an implementation that consistently creates the wrong schema.

Examples that can falsely pass include a changed `details` default, missing
AUTO_INCREMENT, renamed or retargeted FKs with the same count, extra/duplicate
CHECK semantics with a compensating missing CHECK, changed character-column
collation, or an index with incompatible metadata not represented in the
reduced array. Replace the reductions/counts with a complete test-owned
manifest and semantic CHECK normalization matching §3.

### 4. Conflict sensitivity does not cover the approved family contract

The conflict matrix mutates only a root extra column, root extra index, root
table collation, and extra columns on both members. It never independently
proves correction-member drift, nor missing/changed columns, defaults,
generated state, character metadata, engine, index identity/order/type/
visibility, FK identity/reference/actions, and CHECK missing/changed/extra/
duplicate cases. The acceptance contract explicitly requires any such drift on
either configured member to conflict before a missing sibling is created.

Add a mutation matrix over both members and all fingerprint dimensions. Each
case must begin from a fresh exact fixture, assert the binary-sorted conflict
list and compare schema, rows and AUTO_INCREMENT before/after. At minimum, the
correction FK/CHECK mutations must prove the exact self-chain manifest cannot
be weakened while retaining the same constraint counts.

### 5. Database-default collation and bootstrap coverage are partial

All success fixtures hard-code `utf8mb4_unicode_ci`. They do not prove that a
different safe applicable utf8mb4 database default is selected and explicitly
applied to both tables, rather than the implementation always hard-coding the
test collation. Only a non-utf8mb4 default is rejected.

The runtime matrix tests bootstrap only for drift. Sections 5E/5F require
bootstrap to avoid DDL on an exact family and to fail closed for both missing
and drifted family states. Add an alternate safe utf8mb4-default success case,
plus exact DML-only bootstrap success and missing-family bootstrap failure;
separate expected fixture/product DML on success from schema immutability.

### 6. Prefix rejection omits ordinary invalid ASCII input

The runner test proves 26 ASCII bytes and non-ASCII input are rejected before
DB access, but does not cover an ASCII character outside
`[A-Za-z0-9_]`. Add a punctuation/whitespace case with the same unreachable-DB
fixture and exact exit/stdout/stderr contract so validation cannot degrade into
ASCII-only length checking.

## Positive observations

- The RED is real and deterministic: the canonical runner reaches and applies
  v1-v9, then returns schema version 9 rather than approved v10; the runtime
  fixture reaches authenticated ObjectQueue and fails on completion runtime
  DDL under a DML-only principal.
- Tests use the public runner/migration and real request-reachable consumers;
  no production implementation was edited by the RED author.
- Root-only preservation includes Unicode rows, ids and AUTO_INCREMENT;
  reverse partial, decoy isolation, binary conflict ordering and the principal
  correction-chain rejection shapes are present.
- The router does not replace production DB/readiness/response behavior. JSON
  checklist operation/sync exclusion matches §5F.

## Verification evidence

```text
php -l tests/InstallationProcess/installation_completion_schema_001_test.php
No syntax errors detected in tests/InstallationProcess/installation_completion_schema_001_test.php

php -l tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
No syntax errors detected in tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php

php -l tests/Support/installation_completion_runtime_router.php
No syntax errors detected in tests/Support/installation_completion_runtime_router.php

php tests/InstallationProcess/installation_completion_schema_001_test.php
exit 255: clean runner expected schemaVersion 10, actual schemaVersion 9

php tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
exit 255: exact authenticated GET queue expected operational, actual 503 with
redacted 12-hex reference

openspec validate canonicalize-installation-completion-schema --strict
Change 'canonicalize-installation-completion-schema' is valid

git diff --check -- <three reviewed test files>
exit 0, empty output
```

Gate 2 must correct the tests, capture fresh intended RED evidence at the new
hashes, and obtain a fresh independent Gate 3 review. Production must remain
unchanged until that approval.

---

# Independent test rereview — corrected Gate 2 artifacts

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **APPROVED**

This additive rereview supersedes the `CHANGES_REQUESTED` verdict above only
for the corrected hashes listed here. The reviewer did not edit tests,
production, specification, RED evidence or OpenSpec artifacts. Gate 4 is
authorized against exactly these reviewed test bytes; any test expectation
change returns the slice to Gate 2 and fresh independent review.

## Reviewed hashes

```text
1cc7dedd417e1ed874d42c30eee12763a31fd934052735073038c3f08c4db6a7  tests/InstallationProcess/installation_completion_schema_001_test.php
e4f34ebe7d290d7e41f6d51ac46f90e30f2823dd9b64820dc2f2af9ff54eb00f  tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
1331bc60607cd8a6c7f7e87a0bcb1d837a727c90e00465185dfd789c2ebbb682  tests/Support/installation_completion_runtime_router.php
878db6a6d99f9e470e45a33730f2f0f49ba04273a8fc9b67d211e4a94a8dbe87  docs/operations/installation-completion-schema-red-evidence.md
c6f3cf995a81d214559d4078696f82d6d2cfaa1123120cb91775fc5c6b5c5448  specs/INSTALLATION-COMPLETION-SCHEMA-001.md
4c47e0577ba63a51489ba0c3cecb47ff20c08a6bf1e238a399b00f29e73f4f46  docs/operations/installation-completion-schema-owner-approval.md
2c34703b9601f5225123b930fc22e05e30a57bf2806689ebc83a5a457f5acae1  openspec/changes/canonicalize-installation-completion-schema/specs/deployment/canonical-installation-completion-schema/spec.md
```

The owner approval still anchors normative hash `c63ed10e…`; the current
`c6f3cf99…` hash is the previously reviewed bounded administrative status
transition. No normative drift was found.

## Closure of required findings

### Exact runtime success and fail-closed command reachability

The runtime fixture now inserts all 41 independently enumerated montage item
completion operations, whose approved weights reach 85%. Exact-family requests
require statuses `[200, 200, 200, 200, 200, 303]`; the PTO command must append
exactly one root fact with case `71`, type `pto_act`, approved date, empty
details and actor `901`. The test separately proves stable root DDL and exact
non-mutation of every unrelated prefixed table.

Missing and drift variants use the same authenticated, CSRF-valid, 85%-ready
command but require `503` before any fact append, redirect, schema repair or
other prefixed mutation. This removes both mutually exclusive assertions from
the first revision and makes authorization/readiness/DML precedence observable
at the public request seam.

### HTTP metadata

Every unavailable response now requires exactly one
`Cache-Control: no-store`. Card, checklist and completion retain exact
content-type, Retry-After, body and no-redirect assertions. HEAD card/checklist
must expose a declared Content-Length equal to the corresponding GET byte
length while returning an empty wire body. Queue retains the bounded lowercase
12-hex opaque reference contract.

### Exact metadata and mutation sensitivity

First-create assertions now compare complete ordered column metadata including
defaults, auto-increment/generated state, expressions and character metadata;
complete index identity/order/subpart/collation/type/visibility; engine and
table collation; absence of root domain constraints; exact named and ordered
FK columns, targets and actions; and exactly the three normalized CHECK
identities. Expected manifests remain test-owned literals rather than values
obtained from the future implementation.

Fresh mutation fixtures cover both family members across missing columns,
changed defaults/generated/character metadata, engine/table collation, index
order/subpart/type/visibility, missing/renamed/retargeted/action-changed FK, and
missing/changed/extra/duplicate CHECKs. Each requires the exact sole conflict
name and byte-equivalent before/after family state including AUTO_INCREMENT.
The existing two-member conflict also retains binary sorting and family-wide
zero-mutation preflight sensitivity.

### Collation, bootstrap and prefix boundaries

An independent `utf8mb4_general_ci` database proves that a safe applicable
database default is selected and explicitly reflected by both exact tables;
the latin1 database remains a zero-mutation public failure. Exact, missing and
drift bootstrap cases now distinguish expected fixture/product DML from
completion-family schema/history: exact succeeds and publishes one manifest,
while both incompatible states return the exact redacted exit-70 result with
no manifest or mutation. Invalid ASCII punctuation joins the 26-byte and
non-ASCII unreachable-DB cases, preventing a length/ASCII-only validator from
passing.

## Remaining contract assessment

Clean/repeat output, v1-v10 ordering, 25-byte success, populated root-only
lossless upgrade, reverse partial, decoys, exact/partial conflict ordering and
correction-chain rejection remain intact. Root Unicode bytes, ids and next
AUTO_INCREMENT are compared directly. Correction constraints reject duplicate
ordinal, branch, cross-root predecessor, gap, absent predecessor, zero,
malformed NULL shape and blank reason without changing accepted history.

The HTTP adapter continues to invoke production ObjectQueue, production local
card/checklist entrypoint and CompletionFlow without replacing their database,
readiness or response behavior. JSON checklist operation/sync exclusion is
traceable to §5F. Tests are isolated by fresh database/prefix/process and do not
contact production systems.

## Independent verification

```text
php -l tests/InstallationProcess/installation_completion_schema_001_test.php
No syntax errors detected in tests/InstallationProcess/installation_completion_schema_001_test.php

php -l tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
No syntax errors detected in tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php

php -l tests/Support/installation_completion_runtime_router.php
No syntax errors detected in tests/Support/installation_completion_runtime_router.php

php tests/InstallationProcess/installation_completion_schema_001_test.php
exit 255: exact public runner expected terminal v10 and applied v1-v10;
actual healthy runner terminal v9 and applied v1-v9

php tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
exit 255: exact authenticated 85%-ready GET ObjectQueue expected 200;
actual 503 with bounded opaque reference under the DML-only principal

openspec validate canonicalize-installation-completion-schema --strict
Change 'canonicalize-installation-completion-schema' is valid

git diff --check -- <corrected tests, router and RED evidence>
exit 0, empty output
```

Both failures are deterministic intended RED for the two missing approved
behaviors, not environment, credentials, predecessor, authorization or fixture
failure. The corrected tests have sufficient false-pass sensitivity for Gate 3.
Minimal GREEN may begin without editing these approved artifacts.

---

# Independent Gate 3 rereview — Gate 4 setup corrections

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **APPROVED**

This additive rereview supersedes the preceding approval only for the corrected
test/evidence hashes below. It is limited to setup/isolation corrections exposed
when public v10 first became executable. The reviewer did not edit tests,
production, specification, evidence or tasks.

## Reviewed hashes

```text
b70ef939e67152afd4059f419464d3d5c3cc5644448e415ca76348b1759e118d  tests/InstallationProcess/installation_completion_schema_001_test.php
fadb91a9047d962ea5b6e3f0b8443ac1607027fae78cfa9ff0365572b60fd2da  tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
1331bc60607cd8a6c7f7e87a0bcb1d837a727c90e00465185dfd789c2ebbb682  tests/Support/installation_completion_runtime_router.php
db666451b803806e9311bbf6b771f6ee0d4a47118ae85702067a3c32316c2c74  docs/operations/installation-completion-schema-red-evidence.md
```

The normative spec, owner approval and expectations are unchanged. All prior
findings and the complete false-pass sensitivity approved above remain closed.

## Schema fixture isolation

MariaDB FK constraint names are schema-global. The corrected test releases each
exact two-table family after its complete mutation, conflict-result and
before/after preservation assertions, so later cases can reuse the normative FK
names without setup collision. `icsDropFamily()` removes the dependent
correction table before its root. `icsConflictCase()` performs cleanup in
`finally`, including its optional decoy target, so mutation/setup/assertion
failure cannot leave a partial family that contaminates another case.

Cleanup cannot make a conflict pass: every case captures the full family state
after mutation, calls the public migration seam, compares the exact conflict
result, then compares the same complete state including rows and
AUTO_INCREMENT. Only after both assertions does normal cleanup run. On any
throw the outer random database is also dropped in the top-level `finally`.

Root-only preservation compares the populated Unicode root, ids and next id
before cleanup. Reverse partial compares its entire before/after state before
cleanup. Multi-member binary ordering and decoy byte preservation are likewise
asserted first. Each metadata mutation starts from a freshly created exact
family; no predecessor state is inherited from an earlier mutation. Constraint
cleanup order is FK-safe and no production table or external database is
targeted.

The generated-column mutations were also corrected to MariaDB-applicable forms
without changing what they prove: generated state remains distinguishable on
both root and correction members. FK action/retarget mutations split drop/add
where MariaDB requires it; their expected conflict is unchanged.

## Runtime fixture fidelity

Each runtime prefix now invokes the real public canonical runner through v10.
The test no longer attempts a second test-owned CREATE over the canonical
family. Thus:

- `exact` uses the public v10 family byte-for-byte;
- `missing` begins from that same family and deliberately drops only the
  correction member;
- `drift` begins from that same family and deliberately adds one incompatible
  correction column.

RBAC/local identity, the independently enumerated 85% checklist facts and the
otherwise-valid PTO POST are seeded only after successful canonical migration.
Snapshots are taken after the deliberate state transition. Missing/drift still
require every public consumer to fail with exact headers/body before DML and
require the full prefixed state to remain identical. Exact still requires the
five successful reads plus `303`, exactly one approved PTO append, unchanged
root DDL and no unrelated row/schema/counter mutation.

Bootstrap exact/missing/drift uses the same public-v10-first construction. Its
temporary home/wrapper paths are removed in `finally`; the runtime user and
random database are dropped in outer `finally`, including on the current
expected RED. There is no remaining duplicate schema creator or cleanup path
capable of fabricating readiness.

## Independent execution

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/installation_completion_schema_001_test.php
PASS: INSTALLATION-COMPLETION-SCHEMA-001 migration and chain matrix
exit 0

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
exit 255: exact GET queue observable status
Expected: 200
Actual: 503
```

The schema run proves that cleanup no longer hides or interrupts any exact
manifest, preservation, conflict, decoy, prefix, collation or correction-chain
assertion. The runtime run proves v10 migration, exact-family construction,
RBAC and 85% setup all complete before the public ObjectQueue assertion. Its
remaining failure is the approved behavioral RED: production exact-family
runtime is not yet operational under the DML-only principal. It is not setup,
cleanup or fixture fabrication.

Gate 3 is **APPROVED** for the four exact hashes above. Gate 4 may continue
without editing these tests; any further test expectation or setup change
requires another independent rereview.

---

# Independent Gate 3 rereview — final runtime fixture corrections

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **APPROVED**

This additive approval supersedes the preceding runtime-test approval only for
the exact hashes below. The reviewer did not edit tests, wrapper, production,
specification, evidence or tasks.

## Reviewed hashes

```text
b70ef939e67152afd4059f419464d3d5c3cc5644448e415ca76348b1759e118d  tests/InstallationProcess/installation_completion_schema_001_test.php
f2099aefddeb11c64b3f2f52d133dc6695fd369fae83463092e3e5cc9e4e7868  tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
1331bc60607cd8a6c7f7e87a0bcb1d837a727c90e00465185dfd789c2ebbb682  tests/Support/installation_completion_runtime_router.php
986146019ef542e8fb3e62267d5399c03f932b657343f01a22475238f8b24fc2  tests/Support/inspection_planning_bootstrap_wrapper.php
1ffdb14b05b5e6b4f70f48b920de09a7ab5d3a02c7c1e6671a8dcd0d0e06d7c8  docs/operations/installation-completion-schema-red-evidence.md
```

All previously approved schema expectations, HTTP outcomes, metadata/conflict
sensitivity, prefix/collation cases and mutation snapshots remain unchanged.

## Closure of runtime prerequisites

The test now supplies the already-landed, non-completion facts required to make
each HTTP request meaningful:

- exact canonical local user, active assigned role and the three route facts
  used by queue/card/checklist admission;
- one minimal classification provenance fact for operational case `71` and
  legacy object `4512`;
- a working installation case with valid RFC3339 opening audit;
- one registered initial assignment order and one employed installer snapshot;
- all 41 independently enumerated checklist completion operations, reaching the
  approved 85% command prerequisite;
- the configured public `FMONITOR_SHLZ_CSS_PATH`, rather than the previously
  unused SHLZ root variable.

These fixtures satisfy upstream readers; they neither create completion tables
nor imitate readiness. Both completion members still originate exclusively
from the real public canonical v10 runner. Missing and drift are then derived by
one explicit test-admin DROP or ALTER of that public family.

The order `missing → drift → exact` makes every hostile HTTP assertion execute
before a later behavior can stop the test. Missing and drift each traverse the
complete public sequence: queue GET, card GET/HEAD, checklist GET/HEAD and an
otherwise-valid completion POST. Every response must have its exact 503 body,
content type, no-store, Retry-After where specified, HEAD length and no
redirect; the complete prefixed schema/row/counter snapshot must remain
identical. Cleanup occurs only after those assertions.

Exact then proves the positive controls are not an always-fail fixture:
ObjectQueue, card GET/HEAD and checklist GET/HEAD all return `200`; the valid PTO
POST returns `303` and appends exactly one fact with exact case/type/date/
details/actor values. Root DDL and every unrelated prefixed row/schema/counter
remain unchanged. This closes the prior provenance, CSS, order, installer and
opening-audit setup failures without weakening completion behavior.

## Bootstrap wrapper and RED fidelity

The guarded wrapper still replaces only the three exact configuration literals
in a copied production bootstrap and rejects zero/multiple replacement matches.
It now symlinks the real `CompletionFlow.php` alongside the production
bootstrap's other real dependencies. The application directory and all rapid
pilot dependencies remain real production sources; no readiness, response,
exception or database result is stubbed.

The wrapper sandbox is unique per case and removed in `finally`; symlinks are
unlinked rather than traversed. Prefix tables are dropped only after assertions,
with FK checks restored in `finally`; the random runtime DB and DML-only user
are removed by the outer cleanup on both success and failure.

No diagnostic instrumentation, exception-body exposure, tagged production
branch, test hook, fake migration or server-log probe remains in the reviewed
files. Static scan found none of the temporary diagnostic markers described in
the evidence.

Bootstrap cases also run `missing → drift → exact`. The first current failure
is now:

```text
missing bootstrap exact redacted failure
Expected: exit 70, {"ok":false,"reason":"MIGRATION_FAILED"}, empty stderr
Actual:   exit 255, empty stdout, uncaught RuntimeException on production
          docker-bootstrap.php:49 (Schema migration failed)
```

This is a genuine behavioral RED. Public v10 has run, the deliberate missing
member is present, the guarded production bootstrap and real CompletionFlow are
loaded, and readiness detects the incompatibility. Production fails only to map
that failure into the approved redacted operator contract. Once that mapping is
implemented, the unchanged drift case must pass the same contract and exact
bootstrap must succeed under the DML-only principal. The exact case remains
sensitive to the latent request-time legacy `CREATE TABLE IF NOT EXISTS`: it
grants no DDL and requires exit 0, one ready manifest, stable completion DDL and
unchanged completion history. No test-specific grant or bypass can satisfy it.

## Independent verification

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/installation_completion_schema_001_test.php
PASS: INSTALLATION-COMPLETION-SCHEMA-001 migration and chain matrix
exit 0

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
exit 255 at missing bootstrap exact redacted failure:
expected exit 70 / MIGRATION_FAILED / empty stderr;
actual exit 255 / empty stdout / uncaught Schema migration failed

php -l <schema test, runtime test, router, wrapper>
all four: No syntax errors detected

git diff --check -- <five reviewed Gate 2 artifacts>
exit 0, empty output
```

The execution point proves all missing/drift HTTP assertions and all exact
queue/card/checklist/POST assertions completed before the bootstrap RED. The
remaining test branches are reachable sequentially as production behavior is
made GREEN and retain exact state/response sensitivity.

Gate 3 is **APPROVED** at the five hashes above. Minimal GREEN may continue
without changing these artifacts; any further fixture or expectation edit
requires another fresh independent review.

---

# Independent Gate 3 rereview — normalized helper correction

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **APPROVED**

This additive rereview covers only the Gate 4 test-helper correction. The
reviewer did not author or edit tests, production, specification, evidence or
tasks. Current production was observed separately only to run the public seam;
this record is not a code-review verdict.

## Reviewed hashes

```text
98f9dceb0c35c15a90e0c6349854a6299dfe8727dd8249df5133f2b654579e1a  tests/InstallationProcess/installation_completion_schema_001_test.php
6b7ce3dc924218780d8f3726cf24104d51cdb41726cd66de0726e4d53ba8a850  docs/operations/installation-completion-schema-red-evidence.md
e4f3f0b542e98365371c4342d43de2c1b27eeebbb105bb9c298452d47b9900cc  reviews/code/INSTALLATION-COMPLETION-SCHEMA-001.md
```

## Exact helper separation

The helper roles are now explicit and non-overlapping:

- `icsCreateRawCorrections()` contains the independent approved raw CREATE DDL
  and intentionally leaves MariaDB's generated three-part supporting index;
- `icsNormalizeCorrections()` reads the caller session value, disables checks
  only for the test-owned DROP INDEX, and restores the exact incoming value in
  `finally`;
- `icsCreateCorrections()` composes those two test-owned helpers and therefore
  creates the approved normalized final table used by ordinary fixtures.

The generic helper's result is not trusted by assertion alone. Clean, alternate
collation, reverse, multi, decoy and chain cases continue through the complete
test-owned column/index/FK/CHECK/engine/collation manifest or behavior matrix.
An extra reserved index would fail those existing exact index assertions.
Neither helper calls a production migration/definition helper or reads expected
metadata from production code.

## Mutation isolation

Root-member mutations begin with fully normalized root/correction tables.
Correction-member mutations begin with raw correction DDL only because MariaDB
cannot rebuild several FK/CHECK variants after the supporting index has already
been removed. The test applies exactly one declared hostile mutation and then
performs its own normalization before capturing `before`. Thus the state passed
to production has the approved final index inventory plus only that case's
declared difference.

The adjusted FK cases add a changed-action, renamed or retargeted extra FK
instead of depending on an unnormalizable intermediate replacement. This
remains sensitive to exact FK count/name/target/action and avoids mixing the
reserved supporting-index drift into unrelated FK assertions. Every generic
case still requires the sole deterministic conflict and complete zero-mutation
snapshot; cleanup remains after assertions and releases global FK names.

## Dedicated reserved states

`reserved_crash_` is the only case that calls raw CREATE without normalization.
It independently requires exactly three MariaDB statistics rows named
`fk_completion_correction_previous`, then requires the approved conflict and
byte-exact zero mutation.

`reserved_hostile_` starts from the public migration's normalized exact family,
then explicitly adds the same composite reserved index. It separately requires
the deterministic conflict and zero mutation. These two cases cannot collapse
into the generic helper or confirm each other: one proves the actual
CREATE-before-DROP state; the other proves hostile drift after a known exact
public result.

## Session state and prior coverage

The `FOREIGN_KEY_CHECKS` success/failure matrix remains unchanged and passes:
clean `0→0`, clean `1→1`, and denied-normalization failure `0→0`. The test-owned
normalizer uses the same transparent session discipline, so fixture creation
does not leak session state into later assertions.

All previously approved clean/repeat, root-only preservation, reverse partial,
metadata mutation, multi-conflict sorting, decoy isolation, prefix/collation,
correction-chain rejection and append-only history assertions remain in the
same executable and were reached in the full passing run.

## Independent verification

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/installation_completion_schema_001_test.php
PASS: INSTALLATION-COMPLETION-SCHEMA-001 migration and chain matrix
exit 0

php -l tests/InstallationProcess/installation_completion_schema_001_test.php
No syntax errors detected

git diff --check -- <schema test and RED evidence>
exit 0, empty output
```

Gate 3 is **APPROVED** for test hash `98f9dceb…`. The helper correction removes
the contradictory generic fixture without weakening the dedicated reserved
index or any earlier matrix sensitivity. Any further test/setup/expectation
change requires another fresh independent review.

---

# Independent Gate 3 rereview — Gate 5 correction RED

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **APPROVED**

This additive review covers only the correction tests added after Gate 5
`CHANGES_REQUESTED`. The reviewer did not author or edit the test, production,
specification, RED evidence or tasks. All prior approved expectations remain.

## Reviewed hashes

```text
bc2f08980a81271702b301d46bc7b5f95eb4f9bea4357e0bd84063b4ea2cbf13  tests/InstallationProcess/installation_completion_schema_001_test.php
517523affdd2dbb4b77c84384c315efaebb29d96ea6d5ca675d88c2724fdadcc  docs/operations/installation-completion-schema-red-evidence.md
e4f3f0b542e98365371c4342d43de2c1b27eeebbb105bb9c298452d47b9900cc  reviews/code/INSTALLATION-COMPLETION-SCHEMA-001.md
c6f3cf995a81d214559d4078696f82d6d2cfaa1123120cb91775fc5c6b5c5448  specs/INSTALLATION-COMPLETION-SCHEMA-001.md
```

## Reserved-index/crash-state coverage

The first fixture creates the root and executes the test-owned approved raw
correction DDL without running production normalization. MariaDB itself creates
three `information_schema.STATISTICS` rows under the supporting-index name
`fk_completion_correction_previous`; the test requires exactly those three
parts before invoking the public migration seam. This is the real
CREATE-before-DROP interruption state identified by Gate 5, not a fabricated
array or production-derived manifest.

The expected result is exact and independently grounded in approved §3/§4:
`applied=false`, `schemaVersion=10`, reason
`SCHEMA_MIGRATION_CONFLICT`, and only the binary-exact correction table name.
The complete before/after family snapshot includes properties, columns,
indexes, constraints, rows and AUTO_INCREMENT, so production cannot silently
normalize/repair the crash state and still pass.

The second fixture begins with the public migration's normalized exact family,
then the test administrator explicitly adds the same reserved-name composite
index over `(previous_correction_id, root_fact_id, previous_version_no)`. It
requires the same deterministic sole conflict and byte-exact zero mutation.
Together the cases distinguish a blanket name filter from a true exact index
manifest and prevent an always-conflict implementation through the existing
clean/repeat/root-only positive controls.

Both families are removed only after result and snapshot capture. Correction is
dropped before root, releasing the two schema-global FK symbols for subsequent
cases. If an assertion throws, the outer random-database `finally` provides
complete isolation for the next run.

## FOREIGN_KEY_CHECKS session restoration

The success matrix uses a separate fresh database/session and independently
sets `@@SESSION.FOREIGN_KEY_CHECKS` before the public seam:

- `0 → 0` after clean creation and supporting-index normalization;
- `1 → 1` after a second clean creation.

The failure matrix uses a fresh database and a random limited principal granted
only `SELECT, CREATE`. With session checks set to `0`, root/correction CREATE is
allowed but normalization ALTER is denied. The test first proves an actual
`mysqli_sql_exception` occurred at that reachable normalization step, then
requires session state `0` after unwinding. Thus an implementation that skips
the ALTER, swallows its error or unconditionally sets checks to `1` cannot pass.

The limited connection reads the restored session value before close. Its user
and database are then removed, preventing privilege/schema residue. The main
test database remains separate, so the failure fixture cannot supply tables or
constraint symbols to the reserved-index cases.

## Intended RED

Independent execution:

```text
make test-env-up
Container fmonitor2-test-test-db-1 Healthy

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/installation_completion_schema_001_test.php
exit 255
TestFailure: successful clean migration restores caller FOREIGN_KEY_CHECKS=0
Expected: 0
Actual: 1
```

The runner had a healthy MariaDB connection and completed clean schema creation;
the failure is exactly the Gate 5 ST2 behavior, not setup, credentials, prefix,
DDL permission or assertion ordering. Once session restoration is GREEN, the
same executable advances to the two reserved-index cases, which expose Gate 5
S1 without changing expectations.

Syntax and diff hygiene are GREEN:

```text
php -l tests/InstallationProcess/installation_completion_schema_001_test.php
No syntax errors detected

git diff --check -- <test and RED evidence>
exit 0, empty output
```

Gate 3 is **APPROVED** for test hash `bc2f0898…`. Minimal corrective GREEN may
begin against these exact expectations. Any further test/setup/expectation
change requires another fresh independent review.

---

# Independent Gate 3 rereview — full bootstrap DDL inventory fixture

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **APPROVED**

This additive approval supersedes the preceding runtime-test approval only for
the exact hashes below. The reviewer did not edit tests, production, wrapper,
specification, evidence or tasks.

## Reviewed hashes

```text
b70ef939e67152afd4059f419464d3d5c3cc5644448e415ca76348b1759e118d  tests/InstallationProcess/installation_completion_schema_001_test.php
afc8e46fe7f7e014a0dc8f84f48f9ed4a3c846cbf8aca3bf23be5febac4fc1a2  tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
1331bc60607cd8a6c7f7e87a0bcb1d837a727c90e00465185dfd789c2ebbb682  tests/Support/installation_completion_runtime_router.php
986146019ef542e8fb3e62267d5399c03f932b657343f01a22475238f8b24fc2  tests/Support/inspection_planning_bootstrap_wrapper.php
999dec95fc5ae41ab57914f692fad4c767d63886f65ce94e9e54ffdcc310c2d2  docs/operations/installation-completion-schema-red-evidence.md
```

All previously approved schema, HTTP, preservation, header, conflict and
mutation expectations remain unchanged.

## Bootstrap prerequisite inventory

Before invoking the DML-only production bootstrap, the fixture now creates the
current generation sentinel with its exact columns, primary key, engine,
charset/collation and one valid singleton row. It also calls the real
`RapidPilotOtiz::bootstrap()` once through the admin setup connection, producing
the complete current seven-table OTIZ family, including the payment-closure
unique reversal index.

These are prerequisite schemas only. Neither setup operation creates, changes,
repairs or reads the two completion-family members. Completion root and
correction tables still come solely from the public canonical v10 runner;
missing/drift remain a deliberate one-member DROP/ALTER after that runner.
`icrState()` snapshots the sentinel, OTIZ tables, completion family and every
other prefixed table together, so a hostile bootstrap cannot mutate these
prerequisites invisibly.

The sentinel row is intentionally pre-existing. Therefore the observed DML-only
failure at production `CREATE TABLE IF NOT EXISTS ...generation_sentinel` proves
that runtime emitted prohibited DDL; it cannot be explained by an absent table
or incomplete setup. Once that CREATE is removed, the existing sentinel UPSERT
is permitted DML and the unchanged exact test continues to the subsequent
inventory.

The full-bootstrap success expectation remains sensitive to the later runtime
DDL in production order:

- the existing canonical process capability table makes the explicit DROP
  observable under a principal without DROP;
- legacy DROP remains prohibited rather than being granted away;
- every pre-existing exact OTIZ table makes each `CREATE TABLE IF NOT EXISTS`
  observable under the DML-only principal, just like the sentinel;
- the current exact payment-closure index means no repair ALTER is needed in a
  correct bootstrap. If the table is not exact, bootstrap must fail as setup
  rather than repair it; the test provides no ALTER privilege or test bypass.

Thus the preseed does not skip the CREATE inventory: MariaDB requires CREATE
authority even for `CREATE TABLE IF NOT EXISTS` on an occupied compatible name,
as the independently reproduced sentinel failure demonstrates. The test grants
only SELECT/INSERT/UPDATE/DELETE and requires the entire production bootstrap to
finish with exit 0, so any reached CREATE/DROP or applicable ALTER remains a
hard failure.

## Positive and hostile controls

The HTTP phase is unchanged and executes before bootstrap:

- missing and drift complete queue, card GET/HEAD, checklist GET/HEAD and valid
  completion POST with exact 503/no-store/Retry-After/body/HEAD/no-redirect
  assertions and a full zero-mutation snapshot;
- exact completes queue/card/checklist with 200 and PTO POST with 303, appends
  exactly one expected fact, preserves root DDL and all unrelated state.

The bootstrap order remains `missing → drift → exact`. In this run both hostile
states passed the approved exit-70 redacted contract, published no ready
manifest and preserved the full prefixed state. Exact therefore reached the
first remaining runtime DDL only after proving the failure path does not mutate
or repair completion, sentinel, OTIZ or product data.

## Cleanup and instrumentation

Each prefix is unique. Prefix cleanup occurs after all assertions, disables FK
checks only around test-owned teardown and restores them in `finally`. Wrapper
home/root directories are unique and removed in `finally`; symlinked production
sources are unlinked, not traversed. The random DB and DML-only user are removed
by outer cleanup on current RED as well as success.

The wrapper remains a guarded three-literal configuration copy of the real
bootstrap with real application/rapid-pilot dependencies. Search and inspection
found no diagnostic branch, server-log hook, exception-response exposure,
instrumentation tag, fake readiness or fake DDL result in the reviewed files.

## Independent verification

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/installation_completion_schema_001_test.php
PASS: INSTALLATION-COMPLETION-SCHEMA-001 migration and chain matrix
exit 0

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
exit 255 at exact DML-only bootstrap success assertion:
Expected: exit 0, empty stdout/stderr
Actual: uncaught mysqli_sql_exception, CREATE command denied for
        boot_exact_fm2_pilot_generation_sentinel at docker-bootstrap.php:68

php -l tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
php -l tests/Support/inspection_planning_bootstrap_wrapper.php
both: No syntax errors detected

git diff --check -- <five reviewed Gate 2 artifacts>
exit 0, empty output
```

This is the intended behavioral RED for the first remaining bootstrap runtime
DDL. The earlier missing/drift failure mapping is now GREEN, and the exact
positive HTTP contour is GREEN. The unchanged final success assertion will
advance through and expose each downstream forbidden DDL until production
bootstrap is DML-only and publishes the ready manifest.

Gate 3 is **APPROVED** at the five hashes above. Gate 4 may continue without
editing these artifacts; any further fixture or expectation change requires a
fresh independent rereview.

---

# Independent Gate 3 rereview — circular OTIZ fixture removed

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **APPROVED**

This is the final additive Gate 3 approval for the exact artifacts below. The
reviewer did not edit tests, production, wrapper, specification, evidence or
tasks.

## Reviewed hashes

```text
b70ef939e67152afd4059f419464d3d5c3cc5644448e415ca76348b1759e118d  tests/InstallationProcess/installation_completion_schema_001_test.php
8427a39674cf8d4c0e710f164bca14487b88a6fb54815fca699fc5119064618d  tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
1331bc60607cd8a6c7f7e87a0bcb1d837a727c90e00465185dfd789c2ebbb682  tests/Support/installation_completion_runtime_router.php
986146019ef542e8fb3e62267d5399c03f932b657343f01a22475238f8b24fc2  tests/Support/inspection_planning_bootstrap_wrapper.php
372143da1328bbfe8c573c27ffb188585c384c22bace2dc68e3005eedd7434ad  docs/operations/installation-completion-schema-red-evidence.md
```

All prior schema, HTTP, bootstrap, conflict, header, prefix, collation,
preservation and cleanup expectations remain present.

## Independent bootstrap prerequisites

`icrSeedBootstrapSchemas()` no longer requires `rapid-pilot/Otiz.php` and never
calls `RapidPilotOtiz::bootstrap()`. The seven OTIZ prerequisites are now
test-owned literal DDL:

1. `fm2_pilot_otiz_snapshots` — 13 ordered columns;
2. `fm2_pilot_otiz_snapshot_objects` — 19 ordered columns;
3. `fm2_pilot_otiz_snapshot_allocations` — 14 ordered columns;
4. `fm2_pilot_otiz_snapshot_issues` — 11 ordered columns;
5. `fm2_pilot_otiz_snapshot_evidence` — 9 ordered columns;
6. `fm2_pilot_otiz_payment_closures` — 12 ordered columns and exact
   `unique_reversal(reverses_payment_closure_id)`;
7. `fm2_pilot_otiz_events` — 7 ordered columns.

Inspection against the previously deployed OTIZ manifests confirms exact
column order/types/nullability/defaults, primary and secondary keys, JSON
columns, enum values, engine and charset behavior. The explicit
`utf8mb4_unicode_ci` is the exact test database default that the former DDL
implicitly selected. Each created table is independently checked as InnoDB with
that collation and the expected column cardinality. The reversal index is
checked for uniqueness, ordinal, column, BTREE type and visibility.

The generation sentinel is also a test-owned exact literal with its four
columns, primary key and pre-existing singleton row. None of these literals
creates or alters either completion member; those still originate only from
the public canonical v10 runner.

## No self-confirming schema seam

The fixture does not use the production OTIZ schema creator. It uses the
application's read-only `MariaDbPilotLegacyObjectSchemaReadiness` only as the
public prerequisite seam that production bootstrap itself must use. The test
first proves the independent exact set is accepted, then creates two fresh
independent hostile families:

- drops the entire events table and requires read-only rejection;
- drops `unique_reversal` and requires read-only rejection.

Each case would fail if readiness always accepted, ignored a missing member, or
ignored the reversal invariant. Hostile families are removed only after the
rejection assertion. Gate-controlled literal hashes prevent a production
schema edit from silently changing the test fixture in the same implementation
step.

The real `Otiz.php` require in the isolated HTTP router is normal production
route wiring, not fixture construction or a schema oracle. The guarded
bootstrap wrapper continues to execute the copied real bootstrap with real
dependencies and only three exact configuration substitutions.

## Preserved behavioral matrix

Before bootstrap, missing and drift still complete all six HTTP exchanges with
exact fail-closed responses and full zero-mutation snapshots. Exact still proves
200 queue/card/checklist GET/HEAD, 303 completion POST, one exact PTO append,
stable completion DDL and unchanged unrelated state.

For bootstrap, missing and drift return exact redacted exit 70, publish no ready
manifest and preserve the whole prefixed state. Exact runs under the same
DML-only user, performs only permitted sentinel/identity/fixture DML, preserves
completion schema/history, publishes exactly one ready manifest and exits 0.
Consequently the test remains sensitive to reintroduction of sentinel, legacy,
identity, OTIZ or completion runtime DDL: no CREATE/ALTER/DROP privilege is
granted and no wrapper bypass exists.

## Cleanup and instrumentation

Fresh drift prefixes, HTTP prefixes and bootstrap prefixes are isolated. Their
tables are removed after assertions with FK checks restored in `finally`.
Wrapper/home directories are unique and removed in `finally`; the outer random
database and runtime user are removed on all exits. No temporary instrumentation,
diagnostic branch, exception exposure, server-log hook, fake migration or fake
readiness remains in the reviewed artifacts.

## Independent verification

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/installation_completion_schema_001_test.php
PASS: INSTALLATION-COMPLETION-SCHEMA-001 migration and chain matrix
exit 0

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
PASS: INSTALLATION-COMPLETION-SCHEMA-001 DML-only runtime matrix
exit 0

php -l tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
php -l tests/Support/inspection_planning_bootstrap_wrapper.php
both: No syntax errors detected

git diff --check -- <five reviewed Gate 2 artifacts>
exit 0, empty output
```

Gate 3 is **APPROVED** at the five exact hashes above. Both complete focused
executables are GREEN against current production without circular setup or
weakened assertions. Any subsequent test, wrapper or expectation change
requires another fresh independent review.
