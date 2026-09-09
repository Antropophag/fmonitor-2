# Code review: CHANGE-VERIFICATION-001

- Reviewer: `/root/final_review`, independently tasked; did not author the
  specification, tests, implementation, policy, or root integration changes.
- Review date: 2026-09-09.
- Exact base and reviewed `HEAD`:
  `d9811cdd5e4521457a1d61277069fe3d0793f3fe`.
- Candidate: uncommitted working-tree bytes identified below; this review record
  is not part of the reviewed candidate.
- Public seam: `python3 tools/delivery/change-verification.py plan|check|run`.
- Verdict: **APPROVED** for Gate 5. Full exact-source CI remains the recorded
  integration obligation and is not supplied by this focused review.

## Complete findings and corrections

The first Gate 5 pass returned **CHANGES_REQUESTED** with two blocking findings:

1. The shipped policy assigned the unit category command to a governance test
   and assigned the HTTP boundary test to integration although inventory marked
   it governance. Aggregate categories could mask both wrong bindings. The
   correction validates every registered category command against its exact
   category and every boundary test against a category explicitly required by
   that boundary. The policy now uses inventory-consistent commands and boundary
   categories, and an isolated regression proves cross-path masking fails closed.
2. The generic `tests/**` boundary selected all categories, so a governance-only
   tooling test scheduled the DB-backed runtime storage check before Gate 2. The
   policy now gives tooling/test changes bounded DB-free governance obligations,
   while HTTP, persistence, and OTIZ money retain their concrete auth, storage,
   and snapshot checks. `make test` remains in the integration phase.

Review of that correction found a third blocker: a mapped registered integration
acceptance was rejected when its category was not already selected by the source
boundary. The planner now adds the exact inventory category of each existing
mapped acceptance test; future unregistered RED test paths remain supported.

The final real-plan pass found a fourth blocker: an effective changed registered
test contributed only its generic boundary category and was not itself scheduled.
The planner now adds that test's exact inventory category and runtime argv, with
normal command de-duplication. The actual #78 plan consequently runs the changed
inventory verifier and remains DB-free.

Each behavioral correction followed an independently reviewed supplemental RED;
the exact pre-implementation hashes and RED output are preserved in
`reviews/tests/CHANGE-VERIFICATION-001.md`. No findings remain.

## Reviewed source

| File | SHA-256 |
| --- | --- |
| `.quality-graph/verification-policy.json` | `83c751d71c9225a5a330fc0494b3d0c0188800cdcbf4535e67611f6ac2317675` |
| `specs/CHANGE-VERIFICATION-001.md` | `2b2f87ffad995f63cb55f9f21c39768d5e17c87618236a36d02507f0351ecc90` |
| `tests/Verification/change_verification_001_test.py` | `34b68f0b4553cf747e867ae368359baef3c6058831b48c3b6db491e6bad7ae61` |
| `tools/delivery/change-verification.py` | `cbf030e867064e33dcb0646b270c361728581f234bd73ff94e1f081cd31b8449` |
| `tools/delivery/change-verification.md` | `274d8e5167bebce395ea8bb8afd0eb2ec8027d043fd49bb3755f2dc0d8b139f9` |
| `openspec/changes/compute-change-verification-obligations/verification-input.json` | `3244545455e0547e64aadd4c80a2d2687d470c80d102c1736df09fa7a0e39077` |
| `openspec/changes/compute-change-verification-obligations/proposal.md` | `e93cb151844c4cbbd1467df50fde29c733dfdddc7044b1fa9a7a57ee0f2fd180` |
| `openspec/changes/compute-change-verification-obligations/design.md` | `d032e683b2aad4b04bb767d34bb79258959a302fb7bcd606a6467b550b3c3e9e` |
| `openspec/changes/compute-change-verification-obligations/tasks.md` | `2ea71a2dbe5b07c3de5a7670111c67d421ea2d75360f39195869198e3aa9d2ee` |
| `openspec/changes/compute-change-verification-obligations/specs/verification/change-obligations/spec.md` | `9c350736495cd1f29acaa043241f97a28a9dafd70b90fcd45dd997b04c0487c7` |
| `reviews/tests/CHANGE-VERIFICATION-001.md` | `bd4ef2170f1ed25e36dbf7b38c4e5825d8a8e736877f5a97a4b9c512696b6070` |
| `AGENTS.md` | `508f66ae035a9c701bb961a92db9b48a9829fe4c76d1f9f55c2c45a4526a3dd9` |
| `docs/development-process.md` | `4b71f843bfe582d0b37953867946e0eb5211d93757829761ed0d409a4a759f95` |
| `docs/operations/current-delivery-goal.md` | `9dfba46de312b2f7ed87b7830859be2a529487b36c031f9c1bdd941dc35c787c` |
| `docs/operations/delivery-goal-history-through-2026-09-09.md` | `201117ead4654f382d41b19142358f26992524473530f679daad6918aa3a9fea` |
| `tools/delivery/handoff-template.md` | `4acab484fd6a6929cf609779f53852f1eafe9a5df1da2a99ae7ca2d554715ce9` |
| `openspec/config.yaml` | `771983aeba6103ddfbefcd8ea594d79367789ed9e2003cb85e14ead91de32cc7` |
| `tests/Verification/verification_inventory_001_test.py` | `49749cc885ba00ffb02bcd8e2f7fb8d3fcf7228522c502443ef23b017e3b6b56` |
| `tools/verification/categories.json` | `bdf98ec14f92ff1af40ef5bd2496500ea9aaf0051b731b35091131b4d080d415` |
| `tools/verification/suites.tsv` | `0f22aa9963825bb01554a72b373bdb169a666f37a3acddc3fae0f543d94a6770` |
| `reviews/code/DELIVERY-CONTEXT-078.md` | `9c3a3a5a132d4cc24898b99325c01044405c06283db8abb08e8cc0aea3a60755` |

`reviews/code/DELIVERY-CONTEXT-078.md` already independently approved the
bounded process-document candidate. This review additionally checked its links
to the planner, OpenSpec guidance, and inventory integration. Usage implementation
contents were outside this review; only their inventory registration and their
appearance in the real plan were considered.

## Conformance

- The canonical plan unifies declared, committed, staged, unstaged, untracked,
  renamed/deleted paths; binds resolved base/HEAD, content and governing-source
  digests; and reconstructs the document on `check`, so source, policy, acceptance
  spec, inventory, graph, input, path/status/content, base, and plan tampering fail
  before child execution.
- Acceptance mappings are unique and nonempty, support multiple acceptance IDs
  per spec, bind every acceptance spec, reject unsafe paths and unsupported
  runtimes, and permit a future RED test before the file exists.
- Shipped policy classification is mutually exclusive and fail-closed. HTTP,
  persistence, and money boundaries yield concrete repository tests. Exact
  inventory-category checks cannot be satisfied by an unrelated changed path.
- Focused execution uses argv without a shell, checks freshness first, stops on
  the first child failure, preserves the child's RED status, and neither manages
  DB lifecycle nor executes the integration-phase `make test`.
- The compact process and OpenSpec configuration make the plan mandatory before
  Gate 2 without treating it as acceptance approval, RED evidence, independent
  review, or an upstream Quality Graph capability. Append-only delivery history
  and full exact-source CI remain explicit.

## Verification evidence

- `python3 tests/Verification/change_verification_001_test.py` -> 10 tests, OK.
- `python3 tests/Verification/verification_inventory_001_test.py` -> 15 tests, OK.
- `python3 -m py_compile tools/delivery/change-verification.py` -> exit 0.
- `openspec validate compute-change-verification-obligations` -> valid.
- Post-approval status initially reported all then-declared tasks complete; the
  metadata addendum below supersedes that status by explicitly tracking full CI
  as pending task 3.1.
- `git diff --check` -> exit 0.
- Real documented CLI sequence using base
  `d9811cdd5e4521457a1d61277069fe3d0793f3fe` and the reviewed verification input:
  `plan` with stdout redirected, compact saved-plan display, and `check` all exit
  0. The plan requires only `governance` focused coverage and contains the usage
  contract, planner contract, and changed inventory contract plus integration
  `make test`; it contains no DB command.
- `python3 tools/delivery/change-verification.py run --plan
  .local/verification/plan.json --phase focused` -> exit 0: usage aggregate OK,
  planner 10 tests OK, inventory 15 tests OK.

The saved plan becomes stale when this review record changes the working tree;
the integrator must regenerate it for the final candidate. Full exact-source CI
is still required before production integration.

## Metadata closeout addendum

Verdict: **APPROVED** with no findings for the bounded metadata update.

`tasks.md` adds only task 3.1, explicitly pending one full exact-source CI run;
it corrects the earlier planning-complete status without changing implementation
or focused verification behavior.

The paired document-review pilot uses the same base, five frozen file hashes,
six review criteria, requested and observed `gpt-5.6-sol / low`, and completed
APPROVED outcomes with no findings in both arms. The exported totals agree with
the report: long 405,187 input / 2,933 output / 10 responses / 9 tools / 123,201
ms; short 179,019 input / 2,062 output / 6 responses / 5 tools / 82,798 ms. Both
aggregates report one completed session, zero skipped usage records, zero skipped
lines, and no legacy counter resets.

The interpretation is appropriately bounded to one completed paired document
review. It separates cached input and reasoning as subsets, disclaims billing,
pricing, statistical, full-PR, implementation-slice, and cross-model conclusions,
and records differing tool choices and machine load. It truthfully leaves #78
open for broader completed implementation-slice/model comparison and leaves full
exact-source CI pending.

Reviewed metadata SHA-256:

- `openspec/changes/compute-change-verification-obligations/tasks.md`:
  `2ea71a2dbe5b07c3de5a7670111c67d421ea2d75360f39195869198e3aa9d2ee`
- `docs/operations/token-optimization-78-pilot.json`:
  `adb73818c14a3add9b48066377590f1b7133aa5ea6c5320bee13b9b55ea6411d`
- `docs/operations/token-optimization-78.md`:
  `b74cf09451ed1bfab177aca667c79c595afd86f8e3c2cc32e2cac2fe90607757`

Validation: strict JSON parse and `git diff --check` both exit 0. No production
implementation or test expectation changed in this closeout.
