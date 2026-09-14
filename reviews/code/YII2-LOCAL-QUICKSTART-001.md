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
