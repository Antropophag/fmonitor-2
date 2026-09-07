# Quality Graph после первого VERIFY_OK — локальная инвентаризация

Дата: 2026-09-08. Это read-only исследование локального Git graph, сохранённое как
план продолжения. Fetch, push, PR/comment, workflow dispatch, CI publication,
merge и branch-protection mutation не выполнялись.

## Исходное состояние

Локальный Quality Graph ref:
`f07548135fe930e7a8fb9bb97271c9f05a8ebfc1`. Сравниваемый exact verification
candidate: `ccc8dfbfd24765f509ae7e95113fb7ea8f0ee6b4`. Общий merge-base:
`2bff0a0e6baaab61679321001c57cbc916609295`; от него QG lineage содержит105
commit, candidate линия до указанного SHA —1112. Ref не является ancestor candidate.

Текущий HEAD не содержит основных QG-файлов. Их источник следует читать через
`git show f07548135fe930e7a8fb9bb97271c9f05a8ebfc1:<path>`:

- `specs/QUALITY-GRAPH-GOVERNANCE-001.md`;
- `openspec/changes/integrate-quality-graph-governance/{proposal.md,design.md,tasks.md}`
  и `specs/delivery/quality-graph-governance/spec.md`;
- `quality-graph.yml`, `.quality-graph/manifest.json`,
  `.quality-graph/generated-publisher-v0.1.7.yml`;
- `.github/workflows/quality-graph.yml`, `quality-graph-push.yml`,
  `quality-graph-publish.yml`;
- `tools/delivery/check-evidence.php`, `check-quality-graph.php`,
  `quality_graph_publisher.py`;
- `tests/Verification/quality_graph_governance_001_test.php`,
  `quality_graph_publisher_001_test.php`, `quality_graph_toolchain_001_test.php`,
  `quality_graph_publisher_provenance_001_test.py`,
  `quality_graph_runner_security_001_test.php`;
- `pyproject.toml`, `uv.lock` для exact v0.1.7 toolchain.

Минимальный controlled import состоит именно из перечисленных spec/OpenSpec,
graph/manifest/generated publisher, трёх workflows, трёх repository checkers,
пяти verifier files и pinned Python toolchain. Два существующих файла нельзя
заменять ref-версиями целиком: в текущем `Makefile` нужно добавить только QG
phony/help и public targets с вызовом graph checker из `architecture-check`, а в
текущем `.gitignore` — только `.venv/` и `.quality-graph/.cache/`. Текущие verify,
deployment и test seams сохраняются.

Это требует полноценной reconciliation QG lineage с exact post-VERIFY candidate,
а не простого включения workflow-файлов.

## Уже разрешено владельцем после первого literal VERIFY_OK

Controlling prerequisite записан в
`docs/operations/current-delivery-goal.md`: сначала полный `make verify` с literal
`VERIFY_OK` на exact SHA, затем подготовленная CI-интеграция Quality Graph.

Действующие approvals на QG ref:

- `docs/operations/quality-graph-governance-owner-approval-2026-09-02.md` —
  пройти RED, независимые Gates3/5 и representative unmerged PR;
- `docs/operations/quality-graph-governance-v05-owner-approval-2026-09-02.md` —
  Git-derived exact test/implementation sets и immutable metadata binding;
- `docs/operations/quality-graph-custom-publisher-owner-decision-2026-09-03.md` —
  repository-owned `workflow_run` publisher с pinned upstream `watch`/`publish`,
  только `actions: read`, `contents: read`, `checks: write`, без checkout,
  `issue_comment`, command/approval job;
- v0.6 specification и independent Gate1:
  `docs/operations/quality-graph-governance-gate1-rereview-v6.md`.

После prerequisite разрешено интегрировать pinned Quality Graph v0.1.7 lineage,
создать ровно один bootstrap CI PR и выполнить representative unmerged-PR
validation. Повторно спрашивать эти продуктовые решения не требуется.

## Незакрытая последовательность

1. Reconcile 105-коммитную QG lineage с exact post-VERIFY candidate. Проверить
   актуальные repository commands, graph paths, action/package pins и generated
   parity; не переносить исторические hashes/receipts как доказательство нового HEAD.
2. Вернуть final changed tests в честный RED и fresh independent Gate3. Последние
   focused approvals `reviews/tests/QUALITY-GRAPH-GOVERNANCE-001-v34.md`, `v35.md`
   и `QUALITY-GRAPH-GOVERNANCE-001-publisher-provenance-v2.md` покрывают отдельные
   metadata/runner/publisher slices, не whole current integration.
3. Reconcile post-review evidence envelope. Промежуточный Gate5 finding относился
   к более раннему checker, разрешавшему весь `docs/operations/`. Exact final ref
   уже использует literal allowlist в `tools/delivery/check-evidence.php`: только
   `quality-graph-governance-final-verification-2026-09-04.md` и
   `quality-graph-representative-pr-phase-a-2026-09-03.md`, плюс exact code-review,
   receipt-chain и OpenSpec tasks paths. Это fail-closed улучшение, но список
   привязан к старой линии и не включает будущую phase-B evidence. Для current
   lineage нужны честный RED, fresh Gate3 и минимальная GREEN current exact
   allowlist; произвольный directory/pattern allowance запрещён.
4. Создать immutable `delivery/evidence/<slice>/*.json` receipt для exact
   integrated implementation. Связать spec, exhaustive Git-derived test diff,
   RED, independent test review, GREEN, exhaustive implementation diff, exact
   reviewed commit и code review. Получить GREEN `make delivery-evidence-check`.
5. На exact reviewed commit плюс разрешённый evidence envelope выполнить
   `make quality-graph-validate`, `make architecture-check`, полный `make verify`
   и lineage checker. Historical focused GREEN и runs на других SHA не подходят.
6. На одном representative PR/head выполнить phase A parity старого harness и
   graph: positive case, forced repository-command failure, graph drift,
   missing/stale lineage, stale provenance, missing/extra/mismatched Result v0.
   `docs/operations/quality-graph-representative-pr-phase-a-2026-09-03.md`
   доказал только runner provenance и missing-receipt negative case; positive
   parity отсутствовала из-за RED base.
7. Выполнить publisher rejection matrix для wrong repository/PR/head/run/attempt/
   graph digest, omitted/unexpected/duplicate/expired artifacts и digest drift.
8. Получить fresh independent Gate5 на exact replacement implementation commit,
   затем проверить receipt самого slice. Оба существующих verdict —
   `reviews/code/QUALITY-GRAPH-GOVERNANCE-001.md` и
   `reviews/code/QUALITY-GRAPH-GOVERNANCE-001-v2.md` — `CHANGES_REQUESTED`.
9. Phase B возможна только после появления trusted publisher/topology в base
   branch. До этого она остаётся явно incomplete. После появления topology нужно
   доказать реальный `workflow_run` watch/publish, dashboard/check provenance и
   same-head isolation. Затем можно отдельно предложить required-check cutover.

Один возможный способ сохранить прозрачную ancestry — сначала провести bounded
controlled-import package, затем focused evidence-envelope correction. Это не
отдельное требование владельца и не обязательная декомпозиция. Координатор может
выбрать один вертикальный governance seam, если он обеспечивает реальный RED на
current candidate base, независимый Gate3 до реализации, GREEN после approval,
fresh Gate5 exact implementation commit и полный Git-derived receipt. Нельзя
подменять behavioral RED тестом, который лишь сравнивает hashes импортируемых
файлов и зеркалит реализацию.

Targeted проверки после авторизации соответствующих стадий:

- `php -l tools/delivery/check-evidence.php` и `check-quality-graph.php`;
- `php tests/Verification/quality_graph_governance_001_test.php`;
- `php tests/Verification/quality_graph_publisher_001_test.php`;
- `php tests/Verification/quality_graph_runner_security_001_test.php`;
- `uv run python tests/Verification/quality_graph_publisher_provenance_001_test.py`;
- после `uv sync --frozen` — `quality_graph_toolchain_001_test.php` и
  `make quality-graph-validate`;
- затем `make delivery-evidence-check`, `make architecture-check`, полный
  `make verify` и `git diff --check` на exact reviewed lineage.

## Ограничения

QG approvals не разрешают merge любого PR, включая PR10, branch-protection
changes, required-check cutover, удаление или optionalization старого harness,
parity waiver либо автоматизацию независимых approvals. Cutover и удаление старого
механизма требуют отдельного будущего proposal после phase A+B. Сохраняются запреты
на реальные Bitrix/import operations и ложные CI/production-readiness claims.

Запрет публикации, выданный прежнему read-only inventory агенту, не является новым
глобальным approval requirement. Глобальное правило задают owner approvals выше:
никакой CI publication до первого exact-SHA literal `VERIFY_OK`; после него работа
разрешена только в указанном unmerged bootstrap/parity scope.
