# Check-only publisher Quality Graph — предложение владельцу

Дата: 2026-09-08. Статус: **PROPOSED / НЕ РАЗРЕШЕНО / НЕ РЕАЛИЗОВАНО**.

Документ не меняет permissions, pins, workflows, remotes, PR или branch protection.
Phase B остаётся незавершённой до решения владельца, независимых reviews и реального
GitHub evidence.

## Подтверждённая несовместимость

Deployable workflow уже ограничен `actions: read`, `contents: read`, `checks: write`,
имеет только `workflow_run` и не делает checkout. Но pinned runtime
`alchemmist/quality-graph@caf5366a04ca01b230f1df5585d0fbd9693d7bef`, связанный с
`quality-graph-github==0.1.7`, не является check-only.

В pinned source завершённый `publish_workflow_run()` вызывает
`find_managed_comment()` → `upsert_managed_comment()` → `_publish_check()`, а затем
может менять labels. Offline transport evidence наблюдало
`GET /issues/37/comments` → запрещённый `POST /issues/37/comments` с HTTP 403 → ноль
check writes. Файл
`~/.local/state/fmonitor2/quality-graph-current-20260908/publisher-permission-denial-offline.json`,
SHA-256 `8e29fd9425b9c730d78533b045eddf4e8021abecea49bd53c549778e359948d7`.

Выдача `issues: write` или `pull-requests: write` расширила бы утверждённую границу.
На default branch publisher workflow также отсутствует: Contents API probe на base
`2bff0a0e6baaab61679321001c57cbc916609295` вернул 404. Реального phase B ещё не было.

## Конкретное предлагаемое решение

Разрешить **repository-owned inline check-only publisher**. Не ждать неизвестного
upstream release и не менять pin существующего action. Pinned runtime 0.1.7 остаётся
для PR graph collection, но publisher workflow его не вызывает.

Вся trusted publisher logic хранится как reviewable inline Python прямо в
`.github/workflows/quality-graph-publish.yml` на default branch и запускается обычным
`run` step на exact label `ubuntu-24.04`, используя только Python standard library.
Workflow:

- слушает только `workflow_run` workflow `Quality Graph` для `requested`,
  `in_progress`, `completed`;
- использует `concurrency.group` из exact workflow run ID и run attempt при
  `cancel-in-progress: false`, поэтому события одного запуска сериализуются до lookup
  и возможного POST;
- не содержит `uses:` и `actions/checkout`;
- читает event из `GITHUB_EVENT_PATH`, а base `quality-graph.yml` — через Contents API
  по trusted base SHA;
- содержит reviewed constants: SHA-256 exact bytes `quality-graph.yml`, graph digest и
  полный ordered набор node IDs; сравнивает полученные base bytes с этим SHA-256 и
  fail-closed при drift, не требуя внешнего YAML parser;
- через GitHub API читает PR/head, актуальные runs/jobs и Result v0 artifacts;
- не загружает и не исполняет PR code;
- имеет ровно `actions: read`, `contents: read`, `checks: write`;
- не обращается к comments, approvals, labels, reactions или command endpoints.

Executable topology действительно состоит из двух default-branch файлов:
`quality-graph.yml` и `.github/workflows/quality-graph-publish.yml`. Нет скрытого
третьего repository file, checkout или внешнего action pin. Exact workflow bytes
являются одновременно hosting и исполняемой реализацией. `ubuntu-24.04`, inline bytes
и graph constants фиксируются spec/tests; любое изменение инвалидирует reviews.
Inline publisher намеренно не парсит YAML и не вычисляет compiler graph digest:
reviewed build step до установки вычисляет digest и node set существующим pinned
compiler, а runtime принимает их только после совпадения SHA-256 полученных base YAML
bytes. Несовпадение bytes, constants или сгенерированного compiler evidence блокирует
установку либо публикацию.

## Единственные разрешённые записи и идемпотентность

Разрешены два write endpoint одного ресурса:

- `POST /repos/{repository}/check-runs` — создать check, если его ещё нет;
- `PATCH /repos/{repository}/check-runs/{check_run_id}` — обновить только ранее
  созданный publisher check.

Deterministic `external_id`:
`fmonitor-qg:<workflow-run-id>:<run-attempt>:<graph-digest>`. Перед записью publisher
читает checks exact head SHA и выбирает только имя `Quality Graph`, exact
`external_id` и creator текущего token context. Ноль совпадений означает POST, одно —
PATCH, несколько — fail-closed без записи. Check другого head/run/attempt/digest,
имени или creator не обновляется.

Повтор события обновляет тот же check. `requested`/`in_progress` дают
`status: in_progress`; `completed` переводит его в `completed`. Повтор terminal event
допускает только byte-equivalent PATCH того же conclusion/output. Terminal check
нельзя вернуть в progress или изменить conclusion; расхождение даёт fail-closed.
Сериализация охватывает lookup и write целиком; конкурентные одинаковые deliveries не
могут одновременно увидеть ноль matches и создать два checks.

Success возможен только после полного ожидаемого набора Result v0 и exact provenance:
repository, PR, head SHA, workflow run ID, attempt и graph digest. Missing, malformed,
replayed, duplicate или incomplete results дают terminal failure. Superseded run или
изменившийся PR head не публикуют stale state.

## Требуемый amendment контракта

Текущий spec требует для publisher exact action pin `caf5366...`. Решение владельца
должно разрешить amendment:

- action pin остаётся обязательным для graph collection jobs;
- trusted publisher становится repository-owned inline кодом без `uses:`;
- publisher write allowlist — exact `POST /check-runs` и
  `PATCH /check-runs/{owned-id}`;
- comments, labels, approvals, reactions, checkout и PR execution запрещены.

Нельзя выдавать эту реализацию за pinned 0.1.7, молча патчить package или менять pin.

## Acceptance evidence до установки на default branch

- Spec amendment фиксирует hosting, runner label, event parser, endpoint allowlist,
  concurrency group, external ID, ownership lookup, graph-constant binding, state
  machine и fail-closed outcomes.
- RED transport tests запрещают любой write кроме двух Check Run endpoints и любой
  доступ к comments/labels/approvals/reactions; доказывают отсутствие checkout и PR
  execution.
- Tests покрывают POST-create, PATCH-refresh, повтор events, terminal replay,
  duplicate owned checks, foreign creator/check, changed head, superseded run,
  PASS/FAIL, missing/extra/duplicate/malformed artifact и provenance replay.
- Mutation tests отвергают добавленные triggers, permissions, steps, `uses:`, endpoint,
  изменение `ubuntu-24.04`, graph constants и произвольный drift двух deployable
  файлов.
- Независимый Gate 3 утверждает tests; focused GREEN проходит; независимый Gate 5
  утверждает exact implementation commit; новый immutable receipt supersedes старый.
- После Linux CI repair exact-SHA `make verify` выдаёт literal `VERIFY_OK`.

Затем отдельно reviewed два файла устанавливаются на default branch. На disposable PR
сохраняются actual run URLs/IDs, permissions, Result artifacts и Check Run evidence:

| Случай | Обязательный trusted check result |
|---|---|
| Текущий run, полный набор валидных artifacts | completed success |
| Repository/node failure | completed failure |
| Missing, malformed или duplicate artifact | completed failure |
| Replayed head/run/attempt/digest | completed failure |
| Повтор in-progress/completed event | тот же check ID |
| Superseded run или изменившийся PR head | нет stale write |

Для всех случаев evidence подтверждает отсутствие comment/label/reaction writes,
checkout и PR execution. Phase B завершается только после реального base-topology run.

## Точное решение владельца

Разрешить repository-owned inline check-only publisher и amendment
QUALITY-GRAPH-GOVERNANCE-001 с границами:

1. Topology — только `quality-graph.yml` и inline workflow без `uses:` и checkout.
2. Permissions — только `actions: read`, `contents: read`, `checks: write`.
3. Writes — только owned/idempotent POST/PATCH Check Runs; comments, labels, commands
   и approvals отсутствуют.
4. Установка на default branch — только после перечисленных gates и review exact
   bytes. Это не разрешение merge PR37, изменения branch protection или публикации
   нынешнего несовместимого runtime.

До решения Linux repair/phase A могут продолжаться, publisher phase B остаётся
заблокированной несовместимостью runtime и отсутствием default-branch topology.
