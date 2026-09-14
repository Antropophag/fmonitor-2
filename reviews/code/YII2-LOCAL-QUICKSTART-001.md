# Gate 5 code review — YII2-LOCAL-QUICKSTART-001

- Date: 2026-09-14
- Reviewer: independent agent `/root/gate5_quickstart`; authored none of the reviewed code, tests, specification, or Gate 3 record
- Fixed point: `origin/main` at `ab030de81cbe799d4f6d0aaf2476d191b0e37a4d`
- Reviewed commit: `d8ddffb363b8f8b43dc2fdfed5ea2c20c313471b`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T134117Z-e2e7954245/package.json`
- Candidate source: `37425d3021c910687b4b929ecb3c10707bc16784ae2499d8abb94f4c02e4e7ac`
- Executable source: `1b4203112094ad04790fb6a9f99b7d326fc0362c98e05315fe5a2eeb5bc75aaf`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T134117Z-e2e7954245/verification-plan.json`
- Verification plan SHA-256: `6a895a539b000e4f7a7234fecc14b589d68e0686747b21b55a91934de5536c62`
- Specification: `specs/YII2-LOCAL-QUICKSTART-001.md`
- Verdict: `CHANGES_REQUESTED`

## Source and evidence assessment

The worktree is clean and the harness state, package head, candidate source,
executable source and plan are mutually coherent. The six mapped focused commands
in the immutable package are GREEN. Separately authorized disposable execution on
commit `f35d4460` reported `PASS: YII2-LOCAL-QUICKSTART-001 real disposable
clean/repeat/down/reset`; the only later candidate delta is task-checkbox evidence
in `openspec/changes/canonical-yii2-local-quickstart/tasks.md`. That positive
evidence does not cover the unsafe configuration language boundary below.

PR, CI and deployment are `UNKNOWN`; none is treated as approval or GREEN. This
review performed no Docker, reset, publication, merge or deployment action.

## Standards

1. **HIGH — `.env` is interpreted by two incompatible grammars and secrets are
   interpolated into shell recipes.** `Makefile:26,35-45,73-84` first includes the
   file as GNU Make source, then passes it to Compose as dotenv. Make expands `$`
   and treats `#` as a comment while Compose dotenv has different literal/quoting
   rules. Thus an otherwise valid credential such as `abc#def` is changed before
   validation and before it reaches recipe commands; Make functions and shell
   metacharacters also cross an evaluation boundary. This violates the documented
   single `.env` and secret-safe configuration contract. Parse the file exactly
   once through a non-evaluating dotenv-aware helper and pass validated values to
   child processes without Make/shell interpolation.

2. **LOW — possible Data Clump / Duplicated Code.** `Makefile:9-45` repeats the
   17-field environment schema across declarations, exports, a positional loop and
   individual validation. Centralizing the schema in the same validation helper
   would reduce drift. This is a judgement-call smell, not independently blocking.

## Spec

1. **CRITICAL — configuration can execute or mutate meaning before the required
   pre-effect validation.** `specs/YII2-LOCAL-QUICKSTART-001.md:18,25,45,54`
   requires a regular configuration input, validation before persistent effects,
   and no secret exposure through arguments/output. Direct `-include` at
   `Makefile:26` executes Make syntax, and expansions at `Makefile:35-45` embed the
   resulting values into a double-quoted shell program. A crafted or merely
   punctuation-bearing value can be evaluated, change argv, or execute before the
   checks. The current safe-value matrix does not exercise this boundary. Replace
   Make inclusion with literal parsing/validation and add adversarial credentials
   proving zero pre-validation effects, unchanged values, and no output exposure.

2. **HIGH — reset does not reject production-like local identities.** A4 and the
   rejected cases at `specs/YII2-LOCAL-QUICKSTART-001.md:35-37,50` require a
   production-like identity to fail before deletion. `Makefile:38-39,106-107`
   accepts any syntactically valid `fm2-local-*`, including
   `fm2-local-production`, and then runs `down --volumes --remove-orphans`. The
   test rejects only `fmonitor2-production`, which fails the prefix check and does
   not prove this requirement. Add an explicit production/staging/live-like deny
   rule (or stronger bounded authorization identity) and matching zero-effect
   reset tests.

No scope creep was found. Canonical Compose routing, lifecycle ordering,
repeat/down preservation, exact DB grants, owner replay behavior, and preservation
of the production runbook otherwise align with the spec and mapped evidence.

## Gate decision

`CHANGES_REQUESTED`. Return the unsafe env boundary and production-like reset
case to the appropriate correction gates, regenerate exact-source RED/GREEN
evidence and package, then request a new independent Gate 5 review. Do not publish,
merge or deploy this candidate.

---

## Correction review — 2026-09-14

- Reviewer independence unchanged; no reviewed code, test or specification was authored by this reviewer.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T140633Z-4f872ebc73/package.json`
- Reviewed commit: `b9f2dd9b1fe43a7496a887977cbcb46452d3ca1f`
- Candidate source: `243646a634b313bcc886164651ea6c633106f25441ddeb82383032e71d73208d`
- Executable source: `8c3264348f7449f4bb42632f7206b62391d013737fe022596c335f7c94a87e92`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T140633Z-4f872ebc73/verification-plan.json`
- Verification plan SHA-256: `df361005f179cced4e000bbdfb352acc8e9d9b2cfc73b3176e4a27408552d592`
- Verdict: `CHANGES_REQUESTED`

### Prior findings disposition

1. **Resolved.** `tools/delivery/local-runtime-env` reads the file line-by-line
   without evaluating Make or shell syntax, centralizes the required schema, and
   substitutes selected argv values as array elements. Make no longer includes the
   dotenv file or embeds its secret values. The adversarial test proves a literal
   Make function is neither executed nor disclosed and arrives unchanged.
2. **Resolved.** The helper rejects tokenized `prod`, `production`, `stage`,
   `staging`, and `live` identities before command execution; focused tests cover
   `fm2-local-prod` and `fm2-local-production` with an empty effect trace.

The exact-source package is coherent and all six mapped records are GREEN.

### Remaining finding

1. **MEDIUM — invalid or missing `.env` is misreported as Docker unavailable by
   `make up`.** The rejected-case contract at
   `specs/YII2-LOCAL-QUICKSTART-001.md:45` requires `LOCAL_CONFIG_INVALID` for a
   missing/invalid input and separately reserves `LOCAL_DOCKER_UNAVAILABLE` for an
   unavailable Docker/Compose boundary. At `Makefile:31`, the helper and
   `docker info` are one command whose complete output is redirected; the `||`
   handler maps *any* helper failure to `LOCAL_DOCKER_UNAVAILABLE` and exit 69.
   A bounded invocation with no `.env` produced only
   `LOCAL_DOCKER_UNAVAILABLE` (Make exit 2), suppressing the helper's intended
   `LOCAL_CONFIG_INVALID`/64. Validate independently before the Docker probe, or
   preserve and classify the helper exit, and assert the exact diagnostic for
   missing/invalid configuration versus actual Docker failure.

No other correction finding was observed. The helper is Bash-3-compatible in the
reviewed constructs, preserves literal metacharacters through argv/environment,
and bounds reset before executing Compose. PR, CI and deployment remain `UNKNOWN`.
No Docker/reset/publication/merge/deployment action was performed.

### Correction decision

`CHANGES_REQUESTED`. Correct the error-classification ordering in `make up`, add
the focused negative assertion, regenerate exact-source evidence/package, and
return for a final bounded Gate 5 correction review.

---

## Final correction review — 2026-09-14

- Reviewer independence unchanged.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T142044Z-b444cbe914/package.json`
- Reviewed commit: `c2721204d34b43390d92ef707e70cf67b4de4c2e`
- Candidate source: `7134b92c17a51e4396b3ff97cd912efcea2e4a300ca90dc0578ad616d1d74c95`
- Executable source: `15a970c8a334ec75f675829cc083f8698b8be611976348cd65f25931e641c6e0`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T142044Z-b444cbe914/verification-plan.json`
- Verification plan SHA-256: `3bc1a8ed9ea49544e3e1972c9cc3ab439ed2f2ff2bb433b8e3d44fa27e7491d6`
- Verdict: `APPROVED`

### Finding disposition

Resolved. `make up` now invokes the helper's validation-only mode before the
Docker probe. Missing or invalid configuration exits through the helper with
`LOCAL_CONFIG_INVALID`, before any Docker event. Only a subsequent failed
`docker info` is mapped to `LOCAL_DOCKER_UNAVAILABLE`. The focused lifecycle test
independently exercises both branches, requires no later effects or readiness
claim, and retains secret-redaction assertions.

The correction is minimal and does not weaken the previously accepted literal
dotenv parsing, argv substitution, project-name deny policy, lifecycle routing,
idempotency, exact DB grants, bounded reset, or documentation separation. The
full candidate diff against `origin/main` has no remaining Standards or Spec
finding. `git diff --check` is clean.

All six exact-source mapped records in the immutable package are GREEN. This
review also reran the bounded lifecycle and architecture checks on the reviewed
worktree; both passed. PR, CI and deployment remain `UNKNOWN` and are not part of
this approval. No Docker/reset/publication/merge/deployment action was performed.

### Final Gate 5 decision

`APPROVED`
