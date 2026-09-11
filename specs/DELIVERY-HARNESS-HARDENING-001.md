# DELIVERY-HARNESS-HARDENING-001

## Простыми словами

Delivery harness должен доказывать не только локальную корректность частей, но и
полный результат публичной команды: вывод, exit status, сохранённую запись,
совместимость следующего шага и изоляцию worktree. Состав verification suite
нельзя поддерживать несколькими несвязанными ручными списками. Срез не меняет
продуктовые правила, не добавляет Gate 6 и не запускает mutation testing продукта.

## Actor, source oracle, authorization and public seams

Владелец поручает issue #90. Root владеет scope, specification и tests; отдельный
executor реализует только прошедший Gate 3 контракт; независимые reviewers решают
Gates 3 и 5. Источники oracle: подтверждённые дефекты PR #89/#91 и таблица issue
#90. Публичные seams: `python3 tools/delivery/harness.py`,
`tools/delivery/change-verification.py`, repository hooks и canonical verification
commands. Harness не получает полномочий merge/deployment, не пишет domain facts и
не изменяет `rapid-pilot/`.

## R1 — Complete public runner contract

Один table-driven subprocess contract MUST совместно проверять stdout/stderr CLI,
shell exit, JSON outcome, canonical retained record, `exit_code`,
`raw_child_returncode`, `cli_exit_code` и отсутствие source side effects:

| Child | Output | Outcome | CLI exit |
|---|---|---|---|
| `0` | normal | `GREEN` | `0` |
| non-zero | normal failure | `REGRESSION_FAILURE` | same non-zero |
| non-zero | exact expected RED marker | `INTENDED_RED` | same non-zero |
| `0` | line-start `SETUP_FAILURE:` | `SETUP_FAILURE` | non-zero |
| `0` | line-start `UNKNOWN:` | `UNKNOWN` | non-zero |
| signal/timeout | any | `INTERRUPTED` | non-zero |
| `0` | domain identifier/prose containing `UNKNOWN` | `GREEN` | `0` |

Control markers match only at a line start after optional horizontal whitespace and
before `:` or line end. SETUP_FAILURE/UNKNOWN/signal take precedence over expected
RED. Child provenance is never replaced with a synthetic failure code. Records and
full streams are append-only below an external, private evidence home; a run MUST
NOT mutate source or another run's record. Timeout/signal cleanup is bounded.

## R2 — End-to-end seam interoperability and worktree isolation

Executable tests MUST invoke real public commands and consume their returned
artifacts for these chains:

- `prepare -> returned plan -> check -> refresh -> run`;
- `hook -> active binding -> context -> downstream plan`;
- `bounded agent entry point -> harness-only CI routing/result`;
- `Gate 3 evidence -> reviewer package`.

Plans are accepted only from the worktree's trusted external packages/state area;
lexical traversal, symlink escape and arbitrary external paths fail before commands
run. Evidence MUST match mapped acceptance argv/source/environment and the expected
Gate outcome. Incomplete packages remain `NOT_REVIEWED`.

Active binding, refreshed plan and live state MUST be keyed by canonical worktree
realpath, while dispatcher authorization may remain keyed by Git common directory.
Preparing B MUST NOT overwrite A; state/resume for each worktree returns only its
own binding. Repeated same-input prepare in one unchanged worktree is idempotent in
meaning and does not conflict with another worktree.

## R3 — Product/agent verification boundary

Adding or removing a product suite MUST update one canonical machine-readable roster
from which dependent composition is derived, or trigger a deterministic consistency
failure across inventory/categories/suites, verification plan and CI composition.
The bounded roster check MUST reproduce PR #91 before starting the product matrix:
adding an E2E suite by the supported registration path while leaving dependent
composition stale is RED; synchronization is GREEN and includes every suite exactly
once.

Literal assertions MAY remain for independent ordering, mandatory category,
uniqueness and coverage invariants, but MUST NOT be a second manually maintained
copy of the complete roster. Agent delivery-harness tests MUST NOT appear in product
`suites.tsv` or `categories.json`. An explicit bounded agent entry point MAY list
only harness/tooling tests and MUST reject or omit product DB, migration, PDF,
runtime and E2E commands. A harness-only pull request MUST route to that entry point
and skip the product unit/integration/e2e/governance matrix; a mixed or product diff
remains fail-closed on the normal product route.

## R4 — Deterministic orchestration fault sensitivity

A bounded test MUST prove its baseline GREEN and then independently make the suite
RED for each fault: non-GREEN CLI return changed to zero; downstream outcome guard
disabled; arbitrary external plan accepted; global binding replaces worktree key;
UNKNOWN parsed as substring; unrelated same-source evidence accepted; suite added
to only one registry. Faults operate only on disposable orchestration copies or
explicit test seams and MUST NOT mutate the application or repository source.

## R5 — Gate 3 observable-dimensions contract

For an acceptance whose `seam_kind` is `public_cli` or `infrastructure`, the
verification input MUST contain exactly these dimensions: `stdout`, `stderr`,
`exit_status`, `retained_evidence`, `filesystem_effects`, `idempotence`,
`failure_semantics`, `caller_interoperability`, `worktree_concurrency_isolation`,
`verification_registry_synchronization`. Each dimension is either `covered` with
one or more mapped test paths, or `not_applicable` with a nonempty reason. Unknown,
missing, duplicate/unmapped evidence or malformed status fails `check` and `prepare`
before Gate 3. Non-infrastructure acceptances remain backward compatible.

This extends the existing Gate 3 package/checklist and does not create an approval
state, new reviewer, or Gate. Preparation remains `NOT_REVIEWED`; only an independent
Gate 3 reviewer may return APPROVED.

Для единственного pre-implementation Gate 3 этого schema change разрешён bootstrap
input прежней формы: он MUST включать полный target verification input с dimensions
в review sources, явно называться `*-gate3`, не может использоваться для Gate 5 и
не считается соответствующим R5. Его disposable review-only planner MUST оставить
в package только mapped acceptance commands и MUST исключить product full/category
commands; этот bootstrap-код не переносится в candidate. После появления parser
support любой новый или
Gate 5 infrastructure package MUST использовать только полный target input; bootstrap
исключение прекращает действовать. Это позволяет независимо одобрить RED до изменения
production parser, не превращая отсутствующую функцию в setup/environment failure.

## Verification examples

Expected values above come from issue #90, not implementation. Tests use temporary
Git repositories/worktrees, private evidence homes and child programs with explicit
outputs/exits. They preserve foreign files as sentinels, clean only exact owned paths,
and use bounded deadlines. The unchanged real harness and canonical verification
composition remain GREEN; every named injected fault yields a specific RED.
