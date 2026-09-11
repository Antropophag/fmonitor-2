# Independent Gate 3 test review — YII2-CANONICAL-MIGRATIONS-001

- Date: 2026-09-11
- Reviewer: independently tasked agent `/root/gate3_migrations`
- Specification/test author: root agent
- Frozen package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T124131Z-4e83898205/package.json`
- Exact source digest: `c6508399d555a5f43dcbf36c0554fcc145f47dd049e614d012cb39e84e07eb4b`
- Base commit: `a567818d8355c719047f9396f044a4b4a3b442d3`
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T124131Z-4e83898205/snapshot/source.patch`
- Snapshot patch SHA-256: `2e849aa4f44aa84c4d77f278856544419a3a73f2ee629f5b6357e3f490a45e21`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T124131Z-4e83898205/verification-plan.json`
- Verification plan SHA-256: `3f4fbe09f7b071a1cab564c367eceb4056dcfef98af46a13b165222e238ccc2e`
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the specification, OpenSpec artifacts, verification
input or tests. No production code, specification or test was changed during
review. This record is the reviewer's only candidate change.

## Findings

### G3-1 — HIGH — the normative public command contradicts its own accepted invocation

`specs/YII2-CANONICAL-MIGRATIONS-001.md:15` says the command accepts exactly the
route with no command options. The same contract at lines 6, 39 and the executable
tests consistently invoke `schema-migrate/run --interactive=0`. In particular,
`tests/Yii2/yii2_canonical_migrations_001_test.php:16,22,26` treats
`--interactive=0` as accepted while rejecting other options. The public seam is
therefore ambiguous before implementation: an implementation can obey line 15
and fail every planned production caller, or obey the tests and violate line 15.

Correction: state explicitly whether the standard Yii `--interactive=0` option is
the sole allowed option (and in which positions/forms), then align the contract,
OpenSpec scenarios, caller commands and closed-argument matrix. Rebuild the
package because a bound normative input changes.

### G3-2 — HIGH — upgrade, restart, incompatible-state and concurrency evidence does not exercise the new public seam

`specs/YII2-CANONICAL-MIGRATIONS-001.md:27-33,43` requires the existing schema
oracles to verify fresh/repeat/upgrade, preserved rows/history, restart,
incompatible states and concurrent invocation on the new Yii seam or a proven
identical shared adapter. The only new DB test,
`tests/Yii2/yii2_canonical_migrations_db_001_test.php:13-16`, compares fresh
outcomes and a shallow table/engine/collation inventory, then repeats the Yii
command. It does not create a predecessor, restartable, incompatible or contended
state, and does not compare row contents or ledger phases.

The mapped existing oracle still hard-codes `bin/fmonitor2-migrate.php` at
`tests/InstallationProcess/production_migration_runner_001_test.php:20`; the two
runtime lock tests call the canonical application/lock helpers rather than
`bin/yii`. Their GREEN records establish the old owner but cannot prove that the
new controller reaches that owner for the difficult states. At Gate 2 no shared
adapter exists, so equivalence cannot yet be assumed. A plausible controller that
handles fresh/repeat but bypasses the lock, selects a wrong suffix, or repairs an
incompatible state would pass the submitted RED suite after minimal caller edits.

Correction: parameterize or add bounded seam tests so the exact upgrade,
row/history, restartable, incompatible and real concurrent-process fixtures run
through `php bin/yii schema-migrate/run`; independently assert ledger/schema/data
facts and bounded lock output. If alias equivalence is used to share the long
oracle, prove both launchers enter the same adapter and retain at least one
end-to-end Yii case for each material state family.

### G3-3 — HIGH — failure and redaction coverage omits required branches

`tests/Yii2/yii2_canonical_migrations_001_test.php:15-26` covers one refused TCP
connection, a partial invalid-input matrix and one missing password. It does not
prove that an explicitly empty password is accepted as required by spec line 17;
does not cover empty user or the other missing required variables; does not drive
charset negotiation failure; and has no controlled unknown-`Throwable` case for
the exact `SOFTWARE_ERROR`/exit 70/no-details contract at lines 15 and 19. The
legacy runner's charset/failure tests do not exercise Yii bootstrap/controller
output, where warnings, exception rendering or usage text can leak.

Correction: add deterministic injection/fixture paths at the public Yii process
seam for connect versus charset failure and unknown `Throwable`; assert the exact
single JSON line, sysexit, empty stderr, no canary coordinates/password/prefix,
exception class/message/trace or SQL, and no mutation. Complete the required and
empty-value configuration matrix, including the allowed empty password.

### G3-4 — HIGH — production caller and transitive package closure is only a shallow lexical check

The design promises lexical caller inventory and runtime package/load trace
(`openspec/changes/yii2-canonical-migrations/design.md:26,33`), while A5 requires
the reachable startup/load set to exclude rapid-pilot, demo, manifests,
web/session/jobs composition and Yii's migration ledger. However,
`tests/Deployment/yii2_canonical_migrations_package_001_test.py:8-18` checks only
one Compose value, one Make recipe, four files for two substrings, and Dockerfile
text. It performs neither repository caller discovery nor a transitive runtime
load trace. It also does not verify that web/worker/scheduler never invoke
migrations, that the built package contains all reachable Composer/config/class
files, or that no Yii migration ledger is loaded. A hidden caller or indirect
`rapid-pilot`/demo include would pass.

Correction: construct an independently enumerated caller inventory and assert all
production callers resolve to the canonical Yii route (with explicit retained
verifier exceptions). Execute the packaged command under a deterministic load
trace/autoload probe and assert complete allowed/forbidden closure, including no
Yii migration component/ledger and no migration invocation from web, worker or
scheduler. Test package presence/executability rather than Dockerfile substrings.

### G3-5 — MEDIUM — the verification mapping collapses six normative acceptances

`openspec/changes/yii2-canonical-migrations/verification-input.json:18-41` and
the frozen plan use synthetic acceptance ID `A1-A6` for six separately named
normative sections. This prevents the plan from showing which test owns each
acceptance and helped conceal the missing A3/A4/A5 branches above. A single list
of seven tests is not full traceability when several tests exercise only the old
seam.

Correction: map A1 through A6 individually to their real public seam and owning
tests (without falsely assigning a test to behavior it does not exercise), then
regenerate and review the Quality Graph plan.

## Assessment of the submitted evidence

All seven records are bound to exact source
`c6508399d555a5f43dcbf36c0554fcc145f47dd049e614d012cb39e84e07eb4b`
with no source drift. The three REDs are genuine missing-behavior failures:

- `1789130459787782000-d6754609b4e94aa89b55161250290a14`: Yii returns its
  pre-existing `CONFIGURATION_INVALID` envelope instead of reaching the migration
  database boundary.
- `1789130459803648000-298daaecbfc04f88a7eb56095c39004b`: legacy fresh
  migration succeeds through version 24 while the Yii route remains unavailable.
- `1789130459784637000-e284987dcebd42759d1b42e4162bd99a`: Compose still calls
  `bin/fmonitor2-migrate.php`.

The four GREEN records confirm the legacy production runner, internal lock and
parallel-runner behavior, and harness stage behavior. They are useful regression
oracles, but their GREEN status is not evidence that the not-yet-existing Yii
seam preserves those behaviors. Each record reports fixture `UNKNOWN` and notes
that external mutable services require explicit fixture identity; this does not
turn the runs red, but Gate 4/5 evidence must identify the database fixture or CI
environment before those runs can support a deterministic final claim.

The CLI expected JSON, exit codes and fresh alias oracle are independently stated
and sensitive to the immediately missing route. Cleanup uses unique database
names and a `finally` block. Those strengths do not close the missing behavioral
families or package closure above.

## Exact evidence records

- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789130459787782000-d6754609b4e94aa89b55161250290a14.json`
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789130459803648000-298daaecbfc04f88a7eb56095c39004b.json`
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789130459784637000-e284987dcebd42759d1b42e4162bd99a.json`
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789130459797866000-4c7041dd71a54ca9a3f8f1b69443b7da.json`
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789130459789350000-b97d2db9c70641348f53795fc6b7fb8c.json`
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789130459787689000-663e6f2bb0934ba3877f4303d5a9cee0.json`
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789130404378493000-ced92db94b104529ac94d004d98edd1c.json`

## Gate decision

Gate 3 is **CHANGES_REQUESTED**. Return to Gate 1 for the command-contract
ambiguity and to Gate 2 for public-seam, failure/redaction, caller/load-closure
and traceability corrections. Gate 4 is not authorized by this review. No Gate 5
decision is made.

---

# Independent Gate 3 correction review — 2026-09-11 15:59 Europe/Moscow

- Reviewer: independently tasked agent `/root/gate3_migrations`
- Correction author: root agent
- Corrected frozen package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T125037Z-543cb1a86b/package.json`
- Corrected exact source digest: `b5ee1fa0f5f795724c9fb9a69251f3b645ad965925bd68ae0ffa2dab1a4f995e`
- Base commit: `a567818d8355c719047f9396f044a4b4a3b442d3`
- Corrected snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T125037Z-543cb1a86b/snapshot/source.patch`
- Corrected snapshot patch SHA-256: `8167f867b8f4f721fc58ce6b15e9d87626ac6bafb338edb849e0548524a003b0`
- Correction delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T125037Z-543cb1a86b/delta.patch`
- Correction delta SHA-256: `9d13a6343d1559bf57a1f1b001d3bf40471f4572ede9f24225ded4af77f398a2`
- Corrected verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T125037Z-543cb1a86b/verification-plan.json`
- Corrected verification plan SHA-256: `dbee15f35ce2fdb53e20df02a76aac395cc74e12247a5896f2a9379eaa397432`
- Verdict: **CHANGES_REQUESTED**

The reviewer inspected the retained prior findings, the correction delta, the
complete reconstructed corrected snapshot and every evidence record. The
reviewer changed no production, specification, OpenSpec, task or test file. This
appended correction review is the only change made by the reviewer.

## Previous finding resolution

### G3-1 resolved — the accepted option grammar is now exact

`specs/YII2-CANONICAL-MIGRATIONS-001.md:15` now identifies the single accepted
form `schema-migrate/run --interactive=0`, requires that form for repository
production callers, and rejects alternate values, duplicates and ordering. The
closed argument matrix at
`tests/Yii2/yii2_canonical_migrations_001_test.php:26` matches it.

### G3-2 resolved — the complete schema oracle and real lock run through Yii

`tests/Yii2/yii2_canonical_migrations_db_001_test.php:3-5` selects the Yii
entrypoint before loading the full production migration oracle. The oracle's
`pmrRun()` at `tests/InstallationProcess/production_migration_runner_001_test.php:11-30`
therefore runs its configuration, charset, fresh/repeat, predecessor/upgrade,
preserved-row, conflict, recovery/restart and partial-failure cases through
`bin/yii schema-migrate/run --interactive=0`. The new real-process lock test at
`tests/Yii2/yii2_canonical_migrations_concurrency_001_test.php:5-16` owns the
exact canonical database lock, expects exit 75 and no tables while contended,
then expects success after release. This closes the new-seam gap and is sensitive
to lock bypass.

### G3-3 partially resolved — configuration and database failures are complete; software failure is still absent

The corrected CLI matrix at
`tests/Yii2/yii2_canonical_migrations_001_test.php:19-25` covers every missing
required variable, all specified empty/invalid values, and proves an explicit
empty password crosses validation. The parameterized full oracle exercises
connect and successful-handshake/charset rejection through Yii, asserts exact
JSON/sysexits/empty stderr, redacts input and proxy-error canaries, and proves no
DDL/DML follows charset failure.

However, no corrected test creates a controlled unknown `Throwable` at the Yii
adapter boundary or expects the normative
`[70, {"ok":false,"reason":"SOFTWARE_ERROR"}, empty stderr]` result. The only
post-connection unexpected failure at
`tests/InstallationProcess/production_migration_runner_001_test.php:491` is a
known migration DDL failure and expects the owner's distinct `MIGRATION_FAILED`
reason. Source-string checks in the ownership test cannot prove exception
mapping or redaction. Thus the original G3-3 correction explicitly requesting
unknown-`Throwable` class/message/trace/SQL canaries remains incomplete.

Correction: introduce a deterministic test-only fault at the shared adapter's
application invocation boundary, exercise it through the isolated `bin/yii`
process, and assert exit 70, exactly one `SOFTWARE_ERROR` JSON line, empty stderr,
no success, and absence of exception class/message/trace and SQL canaries.

### G3-4 partially resolved — caller inventory and load tracing exist; alias/package closure remains incomplete

`tests/Deployment/yii2_canonical_migrations_package_001_test.py:23-38` now
inventories legacy references under the stated production roots and checks that
selected ordinary runtimes do not invoke migrations. Lines 40-54 run a PHP
included-file trace and exclude rapid-pilot, demo, jobs, PilotHttp and Yii's
MigrateController while requiring the new migration adapter/controller. This is
a material correction to the former four-file lexical check.

Two acceptance requirements are still not proven:

1. A5 requires the retained `bin/fmonitor2-migrate.php` alias to return identical
   stdout/stderr/exit/schema/ledger for identical inputs. The corrected full DB
   oracle selects only Yii, and no test invokes both launchers against equivalent
   fixtures. `tests/Yii2/yii2_canonical_migrations_001_test.php:27-31` merely
   searches alias source text; an alias that names the route but mutates options,
   output or exit status would pass.
2. The package test traces the checkout's local PHP runtime and checks Dockerfile
   substrings. It does not inspect or execute the built runtime image/package, so
   missing copied files/extensions/vendor lock content or an image-only include
   cannot be caught. This does not satisfy the requested package
   presence/executability correction or A5's production-runtime-image condition.

Correction: retain a bounded direct-versus-alias process comparison using the
same independently constructed success and failure fixtures and assert exact
observable and persisted equivalence. Inspect or execute the built runtime
artifact (the eventual exact-source CI may provide the environment), assert the
command is executable with Composer/Yii/mysqli and its required files present,
and apply the transitive forbidden-load assertions there.

### G3-5 resolved — traceability is explicit

The corrected verification input and plan map A1 through A6 separately to their
actual seams and owning tests. The new plan digest is bound above, includes the
four required verification categories and lists no missing tests.

## Corrected evidence assessment

All six records are bound to exact corrected source
`b5ee1fa0f5f795724c9fb9a69251f3b645ad965925bd68ae0ffa2dab1a4f995e`
with no source drift. The five RED records fail for missing production behavior,
not setup:

- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789131006926510000-f3ebfd0858234645b496dc4643825453.json` — Yii does not reach the database boundary.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789131006946193000-d7551f1290a148f4bf1426e35e3d68cc.json` — the Yii migration adapter is absent.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789131006940087000-5f5949887ccd4e1c8ba551ceee83050e.json` — the full oracle reaches the unavailable Yii route and observes the old envelope.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789131006940301000-2568977effae459d8eb218e650846d41.json` — the Yii route does not expose the canonical contended-lock outcome.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789131006954764000-b5f3f6832ca144c9b7c8036732641aa1.json` — Compose still invokes the legacy script.

The GREEN harness-stage record is
`/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789131006950434000-b231ab3f233143dda7fd2521aa1090d3.json`.
It confirms the unchanged migration stage regression. Evidence remains marked
with fixture `UNKNOWN`; that is not treated as approval or final GREEN for the
external database/package environment.

## Correction gate decision

Gate 3 remains **CHANGES_REQUESTED**. G3-1, G3-2 and G3-5 are resolved; G3-3
and G3-4 are only partially resolved. Return to Gate 2 for the bounded unknown
software-failure/redaction test, runtime alias-equivalence test and real packaged
artifact closure. Gate 4 is not authorized by this correction review. No Gate 5
decision is made.

---

# Independent Gate 3 third review — 2026-09-11

- Reviewer: independently tasked agent `/root/gate3_migrations`
- Correction author: root agent
- Frozen package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T125512Z-34f24b3eb4/package.json`
- Exact source digest: `413566ac64922635f44d601de669f5fa82c39090735cba1040ee8acc3ecaa9d2`
- Base commit: `a567818d8355c719047f9396f044a4b4a3b442d3`
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T125512Z-34f24b3eb4/snapshot/source.patch`
- Snapshot patch SHA-256: `271e83c6923128ca8f4f7ec908c2ccac5e3d551aa67ea99901e4b05da3d9d8c0`
- Correction delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T125512Z-34f24b3eb4/delta.patch`
- Correction delta SHA-256: `7dd3393c8e2f81e2e5217da46d94b64ecaa92c30c3f7365da152fe237b9e68df`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T125512Z-34f24b3eb4/verification-plan.json`
- Verification plan SHA-256: `8e9c7df0b862374849942dcd0669fe92ecc92291713b9723b803eac33fe7d041`
- Verdict: **CHANGES_REQUESTED**

The complete third snapshot and delta were reviewed against all earlier findings.
G3-1, G3-2 and G3-5 remain resolved with no observed regression. The added tests
address the correct remaining areas, but G3-3 and G3-4 are still incomplete.

## Remaining findings

### G3-3 — HIGH — the controlled Throwable fixture is unreachable with its database inputs

`tests/Yii2/yii2_canonical_migrations_001_test.php:15` defines `$valid` with
`FMONITOR_DB_PORT=1`; lines 16-17 deliberately prove those inputs fail at the
database connection boundary. Lines 27-30 then reuse the same `$valid` inputs
for the auto-prepended throwing catalogue. The prescribed composition retains
the existing one-mysqli lifecycle, which connects and negotiates charset before
selecting/running the catalogue. Consequently the injected
`ProductionPilotMigrationCatalogue::migrations()` exception is not reached: the
observable result remains `DATABASE_UNAVAILABLE`/69, not `SOFTWARE_ERROR`/70.

This is a broken test arrangement rather than an intended missing-behavior RED.
The captured record
`/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789131293497391000-00fbaa29f75e4fb2b55c0a9d2591c161.json`
stops at the earlier first assertion and therefore does not demonstrate the new
fault path.

Correction: run the injected catalogue fault with a reachable disposable test
database and valid credentials (with a unique database/prefix and cleanup), or
inject the fault at a deterministic adapter dependency after successful
connection. Prove the canary exception actually executes before asserting exit
70, the single `SOFTWARE_ERROR` JSON line, empty stderr and redaction.

### G3-4a — HIGH — alias equivalence still lacks the required failure case

`tests/Yii2/yii2_canonical_migrations_alias_001_test.php:8` invokes each launcher
only once against a separate empty database and compares the successful fresh
result/state. This is good durable success equivalence, including exact DDL,
ledger and rows, but the prior correction explicitly required equivalent success
and failure fixtures. An alias that delegates success correctly but changes
configuration/database/software failure output or exit status would pass.

Correction: add a bounded identical-input failure comparison (for example a
redacted database-unavailable or controlled software-failure case) and assert
exact stdout/stderr/exit plus unchanged durable state for both launchers.

### G3-4b — HIGH — the built image is executed, but its transitive load closure is not inspected

`tests/Deployment/yii2_canonical_migrations_package_001_test.py:58-64` now builds
the production Dockerfile, checks required files in the image and executes the
image's migration command. This resolves artifact presence, executability,
Composer/Yii startup and the closed missing-configuration outcome. However, the
included-file trace and forbidden-set assertions at lines 40-54 still execute
the host checkout. The built-image run at line 63 records only stdout/stderr/exit;
it cannot detect an image-only `rapid-pilot`, demo, jobs, PilotHttp or Yii
MigrateController include. Thus the specifically requested application of the
transitive forbidden-load assertions to the built artifact remains absent.

Correction: run the included-file trace inside the built image (using a
test-owned mounted/output path or safe image-local output), retrieve it, and
apply the required and forbidden closure assertions to those image paths. Keep
the current image cleanup.

## Evidence assessment

All seven records are bound to exact source
`413566ac64922635f44d601de669f5fa82c39090735cba1040ee8acc3ecaa9d2`
with no source drift. Six are recorded as intended RED and the unchanged harness
stage is GREEN:

- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789131293497391000-00fbaa29f75e4fb2b55c0a9d2591c161.json`
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789131293519309000-e9e0f958984c4ff897fe009a34133b5e.json`
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789131293490079000-644df3fdd6fa4493bf81bb93e55c217e.json`
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789131293506134000-de32461bda994e28af4bc8d13e55e7dd.json`
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789131293484372000-0b9413ded7154fc9a17d8c845ab58f10.json`
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789131293529711000-ec06e589aaac489e905cc6098b7bf480.json`
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789131293558237000-6782ef567bbe4234b0e554c195ad2ab5.json`

The new alias RED is legitimate because the direct Yii route is absent; the
package RED is legitimate because Compose still calls the legacy path. Those
early failures do not exercise the later failure-equivalence or built-image
trace assertions. Fixture identity remains `UNKNOWN` in the records and is not
treated as final environmental approval.

## Third gate decision

Gate 3 remains **CHANGES_REQUESTED**. Correct the unreachable Throwable fixture,
add direct/alias failure equivalence, and apply transitive load closure inside
the built image. Gate 4 is not authorized. No Gate 5 decision is made.

---

# Independent Gate 3 fourth review — 2026-09-11

- Reviewer: independently tasked agent `/root/gate3_migrations`
- Correction author: root agent
- Frozen package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T125832Z-f60ebeeb75/package.json`
- Exact source digest: `67cec77ff500675fe31c97d4e3ddfe52539d468c38256550e57065a61821b08e`
- Base commit: `a567818d8355c719047f9396f044a4b4a3b442d3`
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T125832Z-f60ebeeb75/snapshot/source.patch`
- Snapshot patch SHA-256: `83441f8ed09b0c7d9acf94b32fdd6c6e02123de13a467c0417790d6a1fbcba73`
- Correction delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T125832Z-f60ebeeb75/delta.patch`
- Correction delta SHA-256: `cd537246e3f7acfa0c160748619ae5fff90055065e544eb6cb7764a9d07971cd`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T125832Z-f60ebeeb75/verification-plan.json`
- Verification plan SHA-256: `2fafbd8927e706180d73492bd587a22b2842ca336251dfeec1bb9eb806527bf8`
- Verdict: **APPROVED**

The reviewer inspected only the three remaining findings and checked the full
frozen candidate for regressions in G3-1, G3-2 and G3-5. No production,
specification, OpenSpec, task or test file was changed. This appended verdict is
the reviewer's only change.

## Final finding resolution

### G3-3 resolved — controlled unknown Throwable is reachable and fail-closed

`tests/Yii2/yii2_canonical_migrations_001_test.php:27-37` now creates a unique
reachable disposable MariaDB database, supplies its real test credentials to the
Yii process and auto-prepends the throwing catalogue. The connection and charset
boundary can therefore succeed before
`ProductionPilotMigrationCatalogue::migrations()` throws the private canary.
The test independently expects exit 70, exactly one `SOFTWARE_ERROR` JSON line,
empty stderr, absence of exception class/message/SQL/path/database canaries and
no created tables, with owned database cleanup in `finally`.

The same test retains the complete required/missing/invalid input matrix,
explicit empty-password acceptance, exact option grammar, database-unavailable
outcome and redaction assertions. Reclassification from unit to DB/integration
in both verification inventories correctly reflects its reachable database
fixture.

### G3-4a resolved — alias equivalence covers success, durable facts and failures

`tests/Yii2/yii2_canonical_migrations_alias_001_test.php:8-18` executes the
legacy alias and direct Yii route against independently created equivalent empty
databases. It compares exact fresh stdout/stderr/exit and complete `SHOW CREATE`
plus ordered rows for every table, including the canonical ledger. It then
compares identical configuration-invalid and database-unavailable results and
asserts failure canaries remain redacted. Both failure cases occur before a
reachable mutation boundary; the owned success databases are removed in
`finally`.

### G3-4b resolved — the built production image owns the load trace

`tests/Deployment/yii2_canonical_migrations_package_001_test.py:58-75` builds
the actual production Dockerfile with a unique tag, checks executable and class/
Composer presence inside the image, runs the migration command there, then runs
an image-local PHP included-files trace. The retrieved trace is checked against
the same forbidden rapid-pilot/demo/jobs/PilotHttp/Yii-MigrateController set and
the required Yii migration bootstrap/controller/adapter set. The unique image is
removed in `finally`.

### Previously resolved findings remain resolved

The exact `schema-migrate/run --interactive=0` grammar and rejection matrix are
unchanged (G3-1). The full fresh/repeat/upgrade/rows/history/restart/conflict
oracle remains parameterized through `bin/yii`, and the real Yii process lock
test remains present (G3-2). A1 through A6 remain individually mapped in the
bound plan, which reports all required categories and no missing tests (G3-5).

## Fourth-package evidence

All seven records are bound to exact source
`67cec77ff500675fe31c97d4e3ddfe52539d468c38256550e57065a61821b08e`
with no source drift:

- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789131493180891000-99c81895d66946fba70d0f9a907690ff.json` — intended RED, missing Yii migration boundary.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789131493181197000-2e02ab41ab7f40728dac15c58ba42906.json` — intended RED, missing adapter/owner composition.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789131493232619000-f3b95e10a0d048e8928d79884e58c759.json` — intended RED, full Yii migration oracle sees the absent route.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789131493234597000-28608dc1d2844076bb13774c85358c55.json` — intended RED, absent Yii lock outcome.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789131493215861000-b2530ec3c82840cfb80ee864987eeeca.json` — intended RED, direct Yii route differs from the working alias.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789131493277254000-6e86d2e4c6b24e94ba19a05ca0956ff6.json` — intended RED, production Compose still names the legacy caller.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789131493255503000-ba62530cd3af4a9bb17311a288a350c2.json` — GREEN unchanged harness-stage regression.

The intended REDs fail at the first missing production behavior rather than
setup. Later assertions are independently constructed and sensitive to the
plausible regressions identified in the earlier reviews. External fixture is
still recorded as `UNKNOWN`; this approval is Gate 3 test-design approval only
and does not treat UNKNOWN as final environment GREEN.

## Fourth gate decision

Gate 3 is **APPROVED** for frozen source
`67cec77ff500675fe31c97d4e3ddfe52539d468c38256550e57065a61821b08e`.
All five original findings and the three remaining third-pass corrections are
closed. Gate 4 may proceed against this exact reviewed candidate. This is not a
Gate 5 decision and does not approve CI, deployment or merge.

---

# Independent Gate 3 post-implementation test-delta review — 2026-09-11

- Reviewer: independently tasked agent `/root/gate3_migrations`
- Frozen package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T131643Z-31af35a515/package.json`
- Exact source digest: `49007c89a7edefd1e1d7cecce0e58a030004f0f37f4339c12c5ee579e20ef788`
- Base commit: `a567818d8355c719047f9396f044a4b4a3b442d3`
- Snapshot patch SHA-256: `499eb1ff92a98cdc4c57ce60603e145d36cfcae06b9a32c7975d16606eff8ac9`
- Delta patch SHA-256: `924cf9a0e851cec6cace5491efd3301668e9e32a893354ef799ae9f4d13922e9`
- Verification plan SHA-256: `1e38120c96caa3759756548b76380f64df0831887bc3cb9b0a57f90e55bc0e03`
- Verdict: **DELTA APPROVED**

The post-approval test changes preserve the accepted expectations:

- `tests/Yii2/yii2_canonical_migrations_001_test.php:7-16` now launches through
  `/usr/bin/env -i` with an explicit environment, preventing inherited DB values
  from invalidating missing-variable and redaction cases while retaining the
  same public process seam and expected outcomes.
- `tests/InstallationProcess/production_migration_runner_001_test.php:493`
  avoids appending the legacy ignored positional argument when the parameterized
  runner selects the closed Yii route. The legacy oracle still receives its
  historical ignored argument; the empty-password/prefix expectation is
  unchanged.
- `tests/Verification/harness_canonical_migration_stage_001_test.php:176-181`
  updates only the fake PHP caller expectation to the production Make command
  `bin/yii schema-migrate/run --interactive=0`; its orchestration, repeat and
  non-destructive assertions remain unchanged.

All three affected paths are exercised by exact-source GREEN records in the
package. The changes correct test transport/caller setup rather than weakening
the normative JSON, sysexit, rejection, redaction, durability or replay
assertions. Gate 3 remains approved for these test deltas. This delta verdict is
not a Gate 5 decision.

---

# Independent Gate 3 lifecycle-closure test-delta review — 2026-09-11

- Reviewer: independently tasked agent `/root/gate3_migrations`
- Frozen package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T132057Z-a8ed43dd5f/package.json`
- Exact source digest: `7b87638990a4c7584dae0a6e99aec53354f6c6e02a3d99d00d7ec66925e8d47b`
- Base commit: `a567818d8355c719047f9396f044a4b4a3b442d3`
- Snapshot patch SHA-256: `ddbaf3be2c8deed8155718a403595de2e20f118d5fc74710d6e4e98ec9e5bdd5`
- Delta patch SHA-256: `0074851c896fba76a95d18058ceeba253069a13166e1963ec1bc1f29cb238a53`
- Verification plan SHA-256: `82c6cc22f583048fa8c1da484890a0f64ba9fe9d79a40c3029f78a13604ac95e`
- Verdict: **DELTA APPROVED**

The only executable-expectation change is at
`tests/Yii2/yii2_canonical_migrations_ownership_001_test.php:14-15`. It requires
the shared adapter to initialize the connection as explicitly unowned and to
close any constructed mysqli from an outer `finally`, including the interval
between successful construction and charset confirmation. This directly traces
to Gate 5 finding G5-1 and is sensitive to the reviewed defect without changing
the public JSON, sysexit, schema, catalogue, lock, alias or redaction contract.

The intended RED record is
`/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789132795545772000-eb8a32f7181b402da2b519f0370add84.json`.
It is bound to the exact source above with no drift and fails on the first new
assertion because `$connection = null` is absent. That is the requested missing
outer lifecycle, not a setup failure. The plan correctly changes only A2's
expected status to `INTENDED_RED`.

All other mapped tests are GREEN on the same source:

- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789132795551034000-2c49134c121c4553b9648a0761564c38.json`
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789132795563546000-ab3db2b85df24207a5f15eff3b49e476.json`
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789132795559424000-c45d20f358254b04b953f6f25cc49235.json`
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789132795563478000-05cd0ac9f21e4e1abd806d149b633090.json`
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789132795574370000-7de694c918fe423e97e0f54a01bb6938.json`
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789132795581699000-979f019ccbcf4359a99dda41977317d6.json`

Fixture identity remains recorded as `UNKNOWN`; it is not treated as final
environmental approval. This verdict approves only the lifecycle-closure test
delta. Production correction and Gate 5 rereview remain pending.

---

# Independent Gate 3 post-CI test-delta review — 2026-09-11

- Reviewer: independently tasked agent `/root/gate3_migrations`
- Frozen package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T141950Z-89cb7cf9e8/package.json`
- Exact source digest: `e7e6e0af7c0980161c808819d348a53effdfa1773f8c908bf244bbc70cc513e1`
- Base commit: `a567818d8355c719047f9396f044a4b4a3b442d3`
- Snapshot patch SHA-256: `31411b1e27301e50e3ac820f83074146b4ab778921d63eb8acb5c740338525b9`
- Delta patch SHA-256: `60b8798bc1fb38cb567356d3b91c4ba0d02d21ff87cc619523886923fd9d755d`
- Verification plan SHA-256: `a36625bdb9d7c1f12131fc0850d7b3046c5d1e3c10fd1b553671b3c1eda06f19`
- Verdict: **DELTA APPROVED**

The post-CI test corrections are limited to adjacent expectations exposed by
failed run `34606125088`:

- `tests/Runtime/production_runtime_contract_001_test.php:107` now expects the
  already normative production migration caller `bin/yii schema-migrate/run`
  instead of the retired direct legacy script. The separate one-shot and
  persistent-runtime assertions are unchanged.
- `tests/Verification/verification_ci_001_test.py:418` adds the registered
  canonical migration package test to the independently enumerated E2E suite.
  It does not remove or relax any prior inventory item.

Both expectations are direct consequences of approved A5 and the registered
verification category. Their exact-source records are GREEN:

- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789136338841543000-7fc45c9e766e4458b65e1a2d449159cd.json`
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789136339532281000-2c1e5272d94a4ea4bf2649cbbee72ce0.json`

The remaining approved migration and adjacent tests are also GREEN on the same
source, including the jobs console contract. No normative expected value,
public seam, rejection, durability, redaction or lifecycle assertion changed.
Gate 3 remains approved for this delta. This is not a Gate 5 or CI-success
decision.

---

# Independent Gate 3 final post-CI test-delta review — 2026-09-11

- Reviewer: independently tasked agent `/root/gate3_migrations`
- Failed CI run: `34609680703`
- Prepared root package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T145224Z-a9bd5584aa/package.json`
- Exact source digest: `01a5d0ec2859eac03b7f549e51ce94b99999bd5aaf892b2c901ad97f0010b43f`
- Head commit plus retained dirty test delta: `6eab260f4b9e0b492962c8ed0b2f0fe7fb637019`
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T145224Z-a9bd5584aa/snapshot/source.patch`
- Snapshot patch SHA-256: `f681456f8aa5c46628d355593529a2e5b60fe01502d7a0a3ec01e691e896b3f9`
- Verification plan SHA-256: `c3467110b9fcd78bbd835d1e4ce4fd9ac75b7da114667d243eb69431007884c8`
- Changed test SHA-256: `eba817a5d0f7c828d7bfe982452f73bcb21b5eaa51130ce1ea91479fa00c9362`
- Verdict: **DELTA APPROVED**

The sole delta in
`tests/Deployment/yii2_canonical_migrations_package_001_test.py:11-14`
removes the undeclared PyYAML dependency that caused
`ModuleNotFoundError: No module named 'yaml'` and reads the Compose source with
the Python standard library. Its regex is bounded from the exact top-level
`  migrate:` service header to the next top-level service header; it does not
accept a matching command from another service or an unrelated part of the
file. Within that bounded body it requires the exact literal command:

```text
command: ["php", "bin/yii", "schema-migrate/run", "--interactive=0"]
```

Independent sensitivity checks confirmed that replacing `bin/yii`, changing
`schema-migrate/run`, or changing `--interactive=0` makes the assertion fail.
The remaining Make caller, production-root inventory, forbidden runtime owners,
host/image load trace, image build/presence and closed output assertions are
unchanged. No expectation was weakened and no production dependency was added.

The current focused evidence is
`/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789138373409079000-648471dc63a54043b56ba36765861586.json`.
It reports GREEN, exit 0, source
`01a5d0ec2859eac03b7f549e51ce94b99999bd5aaf892b2c901ad97f0010b43f`
and `source_drift=false`; stdout is the expected package-test PASS and stderr is
empty. Fixture remains `UNKNOWN` and is not interpreted as full CI or deployment
approval.

Gate 3 is **DELTA APPROVED** for this exact test-only correction. The previous
Gate 5 approval does not automatically cover the new test bytes; any required
final candidate/source reconciliation and exact-source CI remain root-owned.

---

# Independent Gate 3 CI-flake test-delta review — 2026-09-11

- Reviewer: independently tasked agent `/root/gate3_migrations`
- Failed CI run: `34613115068`
- Prepared root package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T151114Z-f691764404/package.json`
- Exact source digest: `4702966713965f143d08fd15468a9d10101001338e086aa2b2d73c5e0f069f99`
- Head commit plus retained test/mapping delta: `3bf59cd6bdfaf3278c576a950515dba39ee63b24`
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T151114Z-f691764404/snapshot/source.patch`
- Snapshot patch SHA-256: `c57d223877aef65d145ceedd70a54b4b2a21e160b5fc26208b389c748144fcd6`
- Verification plan SHA-256: `13decd114c7e3a0f3c1e2c8b713db0e9314898242b8f3c5c4f09590327d99a90`
- Verdict: **DELTA APPROVED**

The executable delta in
`tests/InstallationProcess/pilot_http_auth_001_test.php:43` changes only the
deadline after the test has observed ready resources and written the release
marker: `microtime(true) + 10` becomes `microtime(true) + 30`. The independent
pre-ready deadline remains 10 seconds, so a process that never opens its
resources still fails promptly. The longer post-release bound accommodates the
observed shared-runner scheduling delay without converting the wait to an
unbounded or unconditional sleep.

All substantive expectations remain unchanged and execute after the marker:
the exact 404 response, empty reports, repeat-close no-op, disappearance of the
request-scoped mysqli connection, clean child exit/stdout/stderr, and owned
marker/process cleanup. A missing `resource-after` marker still fails after the
bounded deadline; the delta does not mask a deadlock or resource leak.

The verification input adds this adjacent test to the A6 focused inventory and
expects GREEN without removing any existing mapped command. The exact-source
record is
`/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789139474994672000-9b589b54bd294135997293dd9082fe97.json`.
It reports GREEN, exit 0, duration 11.414 seconds, source
`4702966713965f143d08fd15468a9d10101001338e086aa2b2d73c5e0f069f99`
and `source_drift=false`, with the expected PASS output and empty stderr. That
duration independently supports the diagnosed fixed-10-second scheduling flake.

Fixture remains recorded as `UNKNOWN` and is not treated as full CI or
deployment approval. Gate 3 is **DELTA APPROVED** for this bounded test-only
stabilization; final source reconciliation and a new exact-source CI result
remain root-owned.
