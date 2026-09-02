# Quality Graph: первичные свидетельства и минимальная схема внедрения

Дата исследования: 2026-09-02.

Исследован официальный репозиторий `alchemmist/quality-graph` на release commit
`caf5366a04ca01b230f1df5585d0fbd9693d7bef` (`v0.1.7`). Все ссылки ниже ведут к этому
неизменяемому commit, если отдельно не указано иное.

## Вопрос

Как использовать Quality Graph как минимальный fail-closed CI/governance layer FMonitor 2,
не перенося в него repository-owned проверки и не подменяя обязательные SSD/TDD review gates;
какую provenance/lineage он уже проверяет и чего в его контракте не хватает для FMonitor 2?

## Короткий вывод

Quality Graph подходит как тонкий слой оркестрации и GitHub-публикации над существующими
командами. Его собственная migration guide прямо требует сначала вызывать те же Make targets и
report producers, оставить прежний CI включённым, сравнить оба контура на representative PR и
только после доказанной parity менять required checks и удалять дублирующую workflow-механику
([migration guide](https://github.com/alchemmist/quality-graph/blob/caf5366a04ca01b230f1df5585d0fbd9693d7bef/docs/migration.md)).

Минимальный безопасный контур для FMonitor 2 — узлы, вызывающие существующие публичные команды
репозитория, плюс отдельная repository-owned проверка delivery evidence. Quality Graph не следует
делать владельцем правил SSD/TDD: его graph schema знает команды, зависимости, profiles и blocking
policy, но не знает спецификацию, RED-доказательство, автора теста, независимого reviewer, reviewed
commit или lineage между ними
([graph-v0 schema](https://github.com/alchemmist/quality-graph/blob/caf5366a04ca01b230f1df5585d0fbd9693d7bef/schemas/graph-v0.schema.json)).
Эту недостающую семантику должен проверять существующий/новый Make target FMonitor, а Quality Graph
только запускать его и fail closed публиковать результат.

## Что Quality Graph фактически делает

`quality-graph.yml` — human-edited декларация, из которой `qg generate` создаёт две GitHub Actions
workflow и `.quality-graph/manifest.json`; `qg validate` заново компилирует их в памяти и падает при
невалидной декларации, отсутствии generated file или его устаревшем содержимом, ничего не
перезаписывая. Список compiler-owned путей выдаёт `qg generated-files`
([installation](https://github.com/alchemmist/quality-graph/blob/caf5366a04ca01b230f1df5585d0fbd9693d7bef/docs/installation.md)).

Узел содержит ровно один `run` или `uses`, может иметь `needs`, event projection, profile,
timeout, один adapter результата и blocking/approval policy. Неизвестные поля, циклы,
самозависимости, неизвестные зависимости, небезопасные пути, mutable runtime ref и конфликтующие
adapters отклоняются
([configuration](https://github.com/alchemmist/quality-graph/blob/caf5366a04ca01b230f1df5585d0fbd9693d7bef/docs/configuration.md)).
Следовательно, `run: make …` остаётся repository-owned контрактом; generated workflow не является
местом для бизнес-логики или ручного редактирования.

Поддержаны четыре формы результата:

- отсутствие `results` / exit code;
- native Quality Graph JSON;
- SARIF;
- JUnit XML.

Отсутствующий, malformed, oversized или выходящий из workspace report становится отдельным
`adapter` failure; упавшая команда не превращается в success из-за passing report
([adapters](https://github.com/alchemmist/quality-graph/blob/caf5366a04ca01b230f1df5585d0fbd9693d7bef/docs/adapters.md),
[adapter implementation](https://github.com/alchemmist/quality-graph/blob/caf5366a04ca01b230f1df5585d0fbd9693d7bef/packages/core/src/quality_graph_core/adapters.py)).
Это важная fail-closed граница: для существующих FMonitor-команд без стабильного отчёта достаточно
exit-code adapter; JUnit/SARIF стоит объявлять только там, где команда гарантированно создаёт файл.

## Fail-closed CI и trust boundary

PR workflow исполняет недоверенный код с read-only permissions, без secrets и с отключённым
persisted checkout credential. Отдельный `workflow_run` publisher исполняет trusted код default
branch, не checkout-ит PR-код и рассматривает загруженные artifacts как недоверенные данные
([security model](https://github.com/alchemmist/quality-graph/blob/caf5366a04ca01b230f1df5585d0fbd9693d7bef/docs/security.md)).

Перед чтением JSON publisher проверяет имя и принадлежность node, GitHub artifact SHA-256,
ограничения размера/числа файлов, ZIP traversal, symlink, затем точное совпадение repository, PR,
head SHA, workflow run, attempt и graph digest. Неизвестный node или любое несовпадение вызывает
`ArtifactError`
([artifact validation source](https://github.com/alchemmist/quality-graph/blob/caf5366a04ca01b230f1df5585d0fbd9693d7bef/packages/github/src/qg_github/artifacts.py)).
Если финальный dashboard нельзя собрать либо отсутствует результат ожидаемого node, aggregate check
становится failed
([publication source](https://github.com/alchemmist/quality-graph/blob/caf5366a04ca01b230f1df5585d0fbd9693d7bef/packages/github/src/qg_github/publication.py)).

Blocking severity по умолчанию — `error`; approvals применимы только к `quality` failure, но не к
`command`, `adapter`, `protocol`, cancellation или infrastructure failure. Approval-команды
авторизуются по collaborator role, создают append-only bot-owned ledger record и перезапускают
failed jobs; Markdown/checkbox сам по себе не является authority
([result protocol](https://github.com/alchemmist/quality-graph/blob/caf5366a04ca01b230f1df5585d0fbd9693d7bef/docs/result-protocol.md),
[administrator commands](https://github.com/alchemmist/quality-graph/blob/caf5366a04ca01b230f1df5585d0fbd9693d7bef/docs/commands.md)).
Для обязательных независимых review gates FMonitor node approval поэтому следует оставить
выключенным: административное suppression не должно превращаться в SSD/TDD approval.

## Встроенная provenance и её предел

Result v0 требует следующий workflow provenance:

```text
repository       owner/repository
pullRequest      optional positive integer
headSha          40- or 64-hex commit identity
workflowRunId    non-negative integer
runAttempt       positive integer
graphDigest      64-hex SHA-256-shaped digest
```

Result также требует stable `nodeId`, status, summary, metrics, findings, annotations,
diagnostics, controls и notes; неизвестные поля запрещены, а `failureKind` должен присутствовать
ровно для failed/cancelled результата
([result-v0 schema](https://github.com/alchemmist/quality-graph/blob/caf5366a04ca01b230f1df5585d0fbd9693d7bef/schemas/result-v0.schema.json)).
Graph digest вычисляется как SHA-256 canonical JSON expanded manifest до добавления самого digest
([compiler source](https://github.com/alchemmist/quality-graph/blob/caf5366a04ca01b230f1df5585d0fbd9693d7bef/packages/github/src/qg_github/compiler.py)).

Это доказывает: «результат этого node относится к этому PR/head/run/attempt и этой скомпилированной
топологии». Это **не** доказывает SSD/TDD lineage: «какая утверждённая spec породила какой тест,
какой RED-run проверен каким независимым reviewer, какой commit затем прошёл GREEN и code review».
Result v0 не имеет generic `inputs`, `outputs`, content hashes, reviewer identity или signed edge
model. Это вывод из закрытых (`additionalProperties: false`) graph/result schemas, а не заявленная
upstream возможность.

## Минимальная недостающая lineage для FMonitor 2

Repository-owned evidence checker должен валидировать как минимум один immutable receipt на slice;
названия полей здесь — рекомендация FMonitor, не upstream Quality Graph schema:

```yaml
schema_version: 1
spec_id: HARNESS-QUALITY-GRAPH-001
spec:
  path: specs/HARNESS-QUALITY-GRAPH-001.md
  sha256: <content digest утверждённой executable spec>
test_red:
  test_paths: [<repository-relative path>]
  tested_commit: <git sha>
  command: <repository-owned command>
  evidence_path: docs/operations/<red-evidence>.md
  evidence_sha256: <digest>
test_review:
  path: reviews/tests/<review>.md
  sha256: <digest>
  reviewer: <stable human/agent identity>
  verdict: APPROVED
  reviewed_spec_sha256: <same spec digest>
  reviewed_test_commit: <same tested commit or explicit descendant rule>
green:
  implementation_commit: <git sha>
  command: <repository-owned command>
  evidence_path: docs/operations/<green-evidence>.md
  evidence_sha256: <digest>
code_review:
  path: reviews/code/<review>.md
  sha256: <digest>
  reviewer: <identity distinct from authors under repository policy>
  verdict: APPROVED
  reviewed_commit: <implementation commit or exact permitted descendant>
lineage:
  - from: spec
    to: test_red
  - from: test_red
    to: test_review
  - from: test_review
    to: green
  - from: green
    to: code_review
```

Проверка должна запрещать отсутствующие/неизвестные поля, несуществующие или выходящие из repo
пути, hash mismatch, неправильный verdict, несовпадающие spec/commit references, нарушенный порядок
ancestor/descendant и совпадение author/reviewer там, где требуется независимость. Она должна
выходить ненулевым кодом и, при необходимости richer UI, дополнительно выпускать native Result с
семантически стабильными finding IDs. Quality Graph при этом не дублирует проверку lineage, а лишь
связывает её outcome с workflow provenance.

## Минимальная конфигурация внедрения

Первый вариант должен быть намеренно скучным: один или несколько nodes вызывают уже существующие
Make targets, а отдельный governance node вызывает repository-owned evidence checker. Например:

```yaml
version: 0
provider:
  name: github
  configuration:
    default-branch: main
    runtime:
      action: alchemmist/quality-graph@caf5366a04ca01b230f1df5585d0fbd9693d7bef

profiles:
  default:
    runner: ubuntu-latest
    setup:
      - uses: actions/checkout@<reviewed-immutable-commit>
        with:
          persist-credentials: "false"
          fetch-depth: "0"

nodes:
  delivery-evidence:
    title: SSD/TDD delivery evidence
    run: make delivery-evidence-check
    policy:
      blocking: true
      approvals:
        findings: false
        files: false
        node: false

  verify:
    title: Repository verification
    needs: [delivery-evidence]
    run: make fresh-test-verify
    policy:
      blocking: true
      approvals:
        findings: false
        files: false
        node: false
```

Это только shape, не готовая FMonitor-декларация: runner должен иметь Docker/Compose и достаточно
ресурсов для MariaDB/e2e, а upstream допускает в profile только read/none permissions. Deployment,
secrets и credential-bearing jobs должны оставаться вне графа
([configuration](https://github.com/alchemmist/quality-graph/blob/caf5366a04ca01b230f1df5585d0fbd9693d7bef/docs/configuration.md),
[migration guide](https://github.com/alchemmist/quality-graph/blob/caf5366a04ca01b230f1df5585d0fbd9693d7bef/docs/migration.md)).

## Representative PR и доказательство parity

Безопасная последовательность:

1. Зафиксировать inventory старого harness: команды, setup/services, dependency/parallelism,
   artifacts/reports, permissions/secrets и имена required checks.
2. Добавить pinned CLI/provider/runtime и generated files, но не менять branch protection и не
   выключать старый механизм.
3. На bootstrap PR проверить `qg generate`, `qg validate` и generated diff. Полный aggregate
   dashboard на этом PR не является достаточным доказательством: trusted publisher читает topology
   из base branch, поэтому новый/переименованный node на первом graph-changing PR не становится
   trusted topology до merge
   ([quickstart limitation](https://github.com/alchemmist/quality-graph/blob/caf5366a04ca01b230f1df5585d0fbd9693d7bef/docs/quickstart.md),
   [upstream issue #23](https://github.com/alchemmist/quality-graph/issues/23)).
4. После попадания bootstrap в default branch открыть отдельный representative probe PR. Для
   требования «не мержить PR» probe достаточно оставить открытым: сравнение не требует merge.
5. Сравнить не только green/red, но точные команды, exit codes, setup/teardown, dependency order,
   skipped/cancelled semantics, report/findings counts, fork permissions, artifacts и required-check
   names. Включить минимум passing PR и намеренно failing revision; missing/malformed evidence и
   missing result artifact обязаны давать red aggregate.
6. Сохранить parity report с PR/run URLs, head SHA, graph digest, старым и новым outcome каждого
   check. До полного совпадения не удалять старые workflow/scripts и не менять required checks.
7. Только отдельным последующим решением сделать `Quality Graph` required, затем убрать лишь
   superseded workflow orchestration. Make targets, executable specs, RED/GREEN evidence и
   независимые review records остаются repository-owned.

Эта последовательность повторяет официальную migration guide, включая требование хотя бы одного
representative PR и предупреждение, что зелёный replacement сам по себе не доказывает parity
([migration guide](https://github.com/alchemmist/quality-graph/blob/caf5366a04ca01b230f1df5585d0fbd9693d7bef/docs/migration.md)).

## Версии и ограничения, которые нельзя скрывать

- На момент исследования latest official tag —
  [`v0.1.7`](https://github.com/alchemmist/quality-graph/releases/tag/v0.1.7), release commit
  `caf5366a04ca01b230f1df5585d0fbd9693d7bef`; четыре package manifests содержат версию `0.1.7`
  ([CLI manifest](https://github.com/alchemmist/quality-graph/blob/caf5366a04ca01b230f1df5585d0fbd9693d7bef/apps/qg/pyproject.toml),
  [GitHub provider manifest](https://github.com/alchemmist/quality-graph/blob/caf5366a04ca01b230f1df5585d0fbd9693d7bef/packages/github/pyproject.toml)).
- При этом README/installation/quickstart на том же commit всё ещё показывают package `0.1.2` и
  прежний runtime SHA. Поэтому install/runtime pins нельзя механически копировать из snippets;
  выбранный release set надо отдельно проверить и зафиксировать exact version + dereferenced tag
  commit. Несоответствие документации и release manifests — наблюдаемый upstream риск.
- Upstream прямо называет продукт functional pre-release: package, graph v0 и result v0 не имеют
  backward-compatibility guarantee; CLI, provider и Action runtime должны быть одним exact release
  set, mutable refs и широкие version ranges не поддержаны
  ([compatibility policy](https://github.com/alchemmist/quality-graph/blob/caf5366a04ca01b230f1df5585d0fbd9693d7bef/docs/compatibility.md)).
- Минимальный Python — 3.12. Generated output compiler-owned; поддержанный formatter contract
  ограничен указанными upstream версиями/правилами
  ([compatibility policy](https://github.com/alchemmist/quality-graph/blob/caf5366a04ca01b230f1df5585d0fbd9693d7bef/docs/compatibility.md),
  [installation](https://github.com/alchemmist/quality-graph/blob/caf5366a04ca01b230f1df5585d0fbd9693d7bef/docs/installation.md)).
- Report limit — 10 MiB; result collections и summary также имеют schema limits. Большой полный
  FMonitor log должен оставаться отдельным artifact/evidence, а native result — содержать bounded
  summary/findings и ссылки/идентичности
  ([adapters](https://github.com/alchemmist/quality-graph/blob/caf5366a04ca01b230f1df5585d0fbd9693d7bef/docs/adapters.md),
  [result-v0 schema](https://github.com/alchemmist/quality-graph/blob/caf5366a04ca01b230f1df5585d0fbd9693d7bef/schemas/result-v0.schema.json)).
- `workflow_run` publisher и GitHub-specific permissions означают, что этот provider не является
  локальной заменой GitHub governance. Локальная истина должна оставаться в Make targets и
  machine-verifiable repository evidence.

## Команды оператора

Для pinned repository installation набор команд таков; `<VERSION>` и `<RELEASE_COMMIT>` должны
быть взяты из одного проверенного release set:

```bash
uv add --dev quality-graph-cli==<VERSION> quality-graph-github==<VERSION>
uv run qg init --default-branch main \
  --runtime-action alchemmist/quality-graph@<RELEASE_COMMIT>
uv run qg generate
uv run qg generated-files
uv run qg validate
uv run qg result validate <result.json>
```

`qg validate` следует включить в repository verification, чтобы declaration/generated drift был
blocking. `qg generate` является явной mutation-командой и не должен молча исполняться вместо
валидации в CI.
