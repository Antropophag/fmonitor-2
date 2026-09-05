# Object-detail schema task 2.0 — requirement-by-requirement evidence accounting

Date: 2026-09-05. Reviewer: separately tasked agent
`/root/otiz_v12_gate5`. Current repository HEAD inspected:
`2563590121eeadb7cc59d83049e1d6d422515243`.

This is append-only reconciliation of existing evidence for OpenSpec task 2.0.
It is not a new code approval, does not rewrite a retrospective run as an
original RED, and does not change the task checkbox. Importer behavior, full
integration, parent completion and launch readiness are outside this verdict.

## Verdict

**EVIDENCE_COMPLETE for task 2.0's data-free schema-engine boundary.**

No behavioral requirement in task 2.0 lacks a legitimate forward
RED → independent Gate 3 → minimal GREEN → independent Gate 5 lineage when the
requirements are accounted through the shared named-lock and verification
engine behavior. The later real DDL-denial and causal two-creator tests add
independent direct coverage. They remain honestly classified as supplementary
and are not promoted into the original implementation history.

## Deterministic partial-CREATE failure and recovery

The forward observer tranche specifies a fault after the real details CREATE,
before the quarantine CREATE. Its independent reader proves the durable
details-only state, lock ownership/release and fictional row preservation; a
fresh ordinary retry must create only quarantine.

- Forward RED test commit: `cc02608f5b71323434ce4a2765d5a7b692a5cd92`.
  The missing verification API is the intentional first failure; the reviewed
  prospective body contains the exact post-details interruption and recovery
  assertions.
- RED evidence:
  `docs/operations/object-detail-schema-observer-red-v2-2026-09-05.md`,
  SHA-256 `4c34d50aabc274fb6b618e102cd11b4628caa23d3505fb21cc5daf00eca956b2`.
- Independent Gate 3: commit
  `d8f98831d5601ad2f616bdcc1f5cb3b1267ac03e`, record
  `reviews/tests/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001-v8.md`, SHA-256
  `32e37e81c90163c41f2e9dad428cc394a4b420d416a21e793bdd93db6477eed1`.
- Minimal GREEN implementation: commit
  `d741286e11d988d87efd3422908b96d8ce2148b3`.
- Independent bounded Gate 5: commit
  `a7bc7648a1ef94ea8bcc923b653a028417c27785`, record
  `reviews/code/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001-observer-v1.md`, SHA-256
  `fe75e7a19cf00f623e0d1bdb5959d0cf25be7cba75cb6eaaa0e5b330fbe73bf0`.

The real isolated-privilege denial test later confirms the same durable partial
state and retry through an actual denied second CREATE in exception mode. Its
commit `cfabd3904959995933634bc07e51f2519b648a50`, evidence SHA-256
`087eeb6a441a88c6a423b81c4aa799f30ccd79967a367328a6dc0d8f649c29c8`,
and independent review commit
`0872b56d217487dfdf8d3fc24bf5f42a166b8a9d` are explicitly
`APPROVED_AS_ADDED_COVERAGE`, not an original forward RED.

The separate native-false privilege path did expose a real defect after that
supplementary test: a denied CREATE was incorrectly followed by a success phase
event. It has its own valid forward corrective sequence:

- RED `dc4b701cee160166edad31ad27c592e4bf20a980`, evidence SHA-256
  `66ad66292f9e7872b288387502062fd77383ac89244f7c971de78b502a4ebf31`;
- independent Gate 3 `1e7e43a4375faab3c08d07236c2d40fc54e20c0a`, review SHA-256
  `97b32375b0f031ea35e1627df9cd9cf19a5e6f3fa02fdf965224812998246f35`;
- minimal correction `b7bc649cb6d918dffa947ff0068bddfe17d31d5b`;
- GREEN record at `fd0487410d595f506adee7c3457698bf2a10c34e`, SHA-256
  `8cc31c70a40b208f96c46b5ba31ff3c378a146673a5d6b1d96b291eb1af651eb`;
- independent combined engine Gate 5
  `07f58c7dd10700b3a951f792e94b972839dfe876`.

Thus both mysqli exception and native-false database-denial modes are covered,
while only the native-false defect correction is claimed as forward RED for its
specific behavior.

## Final-verification failure after both CREATEs

The same forward observer tranche closes the actual worker connection from the
`QUARANTINE_CREATED` observer phase. At that point both real CREATE statements
are durable and the final inspection is unavailable. The reviewed expectations
require no success result, independent proof of the complete family, eventual
lock freedom and an ordinary exact-repeat retry.

This requirement therefore uses the same exact forward sequence and hashes:
RED `cc02608f...`, Gate 3 `d8f98831...`, minimal GREEN `d741286e...`, and
independent bounded Gate 5 `a7bc7648...`. It is not inferred from the later
supplemental tests.

## Two-creator serialization and bounded timeout

The shared causal mechanism received forward gates before the direct
two-creator test was added. The held-lock test uses two distinct real MariaDB
connections. One connection owns the exact normative database/prefix lock; the
public migration on the other must exhaust the specified five-second
`GET_LOCK` timeout, return typed `DatabaseUnavailable`, leave the family and
decoy unchanged, and succeed after release.

- Forward RED `30657cd34b40041e78bcd0ce42622e9aff52a144`, evidence SHA-256
  `628ec08d7e140823c2805762fe5fbd5374371b441744ec0f61b03fd6ca814734`.
- Independent Gate 3 `b60216bfe4a8ccbc827977c5ea00ac6e6fde630b`, review SHA-256
  `7aed42c29d1544bcbafe130932f20b94b7818e9d3aed7db8180452ed500a303c`.
- Minimal lock implementation `5d47fde9112dcde059f592d4ce72c5965843cfa7`, followed by the
  architecture-preserving extraction and final tranche commit
  `145770a37019c4436c8cb9755222b1282ebf079f`.
- Independent Gate 5 commit
  `8da502f1f21320ec0d1c34eaa7c49dc6eb4277c5`, record SHA-256
  `10fb63ac98017df1d109219b3588620283a890cfebeded3c2dbf92d6c91e2bb2`.

That forward test is sensitive to the exact primitive which serializes two
migration callers: lock identity, cross-connection exclusion, the bounded wait,
no mutation while excluded, release, and ordinary retry. The implementation
review verifies that the same lock surrounds preflight, both CREATE statements,
observer phases, final verification and result formation.

The later causal test directly composes two migration creators plus a different
prefix worker. It proves A holds `LOCK_ACQUIRED`, B is independently visible
waiting on the same exact `GET_LOCK`, no family mutation occurs before A is
released, A returns two created tables, B returns an exact no-op, and the other
prefix is independent. Its test commit
`7f3536f56241f4a409fe35d267ceb451c4e2f395`, evidence SHA-256
`73e4593b216255570aeeec6d2886fc1205fbae4675b938560a6bac62b7a99f9d`,
and independent coverage-review commit
`62963e95e489da04ce615f774dd148716d381ea9` remain supplementary. The direct
two-creator test deliberately releases A before B's timeout; the timeout outcome
is already established by the forward held-lock public-seam test and is not
falsely attributed to this supplemental test.

## Combined engine review and boundary

The independent combined engine review at
`07f58c7dd10700b3a951f792e94b972839dfe876`, record
`reviews/code/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001-schema-engine-v1.md`, SHA-256
`02e1476203b4646a271f23e3047735ec1ced4de573cbad5b0145df4fe1a88fd8`,
reconciles all six schema tests and concludes that no v0.4 data-free
schema-engine mutation or recovery scenario remains wholly unproved.

The normative specification hash for every lineage above is
`be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40`.
Task 2.0 may therefore be accounted as evidenced within this boundary. This
does not account task 2.2 importer characterization/runtime no-DDL, consumer or
full-repository verification, final integration, or OpenSpec archival.
