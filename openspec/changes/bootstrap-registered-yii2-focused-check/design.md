## Context

См. `proposal.md`. Planner уже типизирует зарегистрированный тест как `integration` с MariaDB service, harness уже сохраняет identity/evidence, `run-in-profile` уже materialize-ит uncommitted snapshot и строит pinned image. Но launcher лишь подключается к ранее существующей Compose network, integration image не содержит `shlz-ui`, а fixture ожидает CSS через runtime environment.

## Goals / Non-Goals

**Goals:**

- Углубить existing `run-in-profile`, чтобы service lifecycle принадлежал конкретному запуску и setup завершался до child assertions.
- Адресно зарегистрировать Yii navigation test в подходящем existing profile без глобального повышения integration-категории.
- Сохранить exact argv/command identity и harness classification/evidence.

**Non-Goals:**

- Новый runner, dependency registry, supervisor или cache subsystem.
- Изменения product code, schema, error handler, FAST/review/admission policy.
- Host fallbacks или использование текущего рабочего стенда.

## Decisions

### 1. Existing runner remains the owner

`run-in-profile` остаётся единственным execution seam. Planner формирует wrapper argv с малым `browser --with-services` alias только для зарегистрированной addressable command; harness сам не заворачивает generic diagnostics. Без alias существующие `integration`/`browser` profiles сохраняют non-owning service contract. Альтернатива — новый Yii launcher — отклонена как второй runner.

### 2. Per-run Compose lifecycle

Для profile с MariaDB launcher генерирует unique `COMPOSE_PROJECT_NAME`, вызывает existing `compose.test.yaml up --detach --wait test-db`, проверяет service readiness, подключает child container к project network и в trap вызывает `down --volumes` только для этого project. Host port publication не требуется container-to-container path; существующий compose lifecycle переиспользуется, шаблон/пины сохраняются.

### 3. Addressable profile registration

Category остаётся `integration`. Existing policy получает narrow command override/registration для exact test, направляющую его в existing browser image target, потому что этот target уже владеет pinned `shlz-ui` build/assets. MariaDB lifecycle остаётся service obligation integration. Все прочие integration commands продолжают использовать integration target.

### 4. Container-only assets and dependencies

Focused image строит Composer dependencies из `composer.lock`; browser stage строит `shlz-ui` по `SHLZ_UI_REVISION` и его lockfile. Fixture получает `FMONITOR_SHLZ_CSS_PATH`/root из container environment. Snapshot source копируется в `/workspace`; host dependencies не bind-mountятся. Existing image identity labels продолжают связывать source digest и Composer lock.

### 5. Explicit setup stages

Launcher пишет компактный `SETUP_FAILURE stage=<docker|dependencies|db_start|db_readiness>` и завершает nonzero до `docker run` child. Harness уже сохраняет stdout/stderr paths и классифицирует pre-behavior wrapper failure как setup failure. HTTP status сам по себе не является setup signal.

### 6. Verification and architecture impact

Root добавляет executable tests для planner argv, first/repeat disposable-worktree execution, controlled uncommitted mutation и bounded fake-Docker/DB failure semantics. Docker-heavy evidence запускается адресно; полный local suite запрещён. Persistence/domain owners, rapid-pilot adapter и architecture rules не меняются.

## Risks / Trade-offs

- [Browser image тяжелее integration image для одного теста] → narrow exact-test registration; shared cache amortizes unchanged dependency layers.
- [Compose collision или destructive cleanup] → cryptographically unique per-run project and no `--remove-orphans` against shared/default project.
- [Trap hides primary failure] → cleanup is best-effort and retains original child/setup exit.
- [Docker unavailable prevents real acceptance] → explicit SETUP_FAILURE evidence, never fabricated GREEN/RED.

## Migration Plan

Добавить contract/tests и prepared registration, затем executor минимально изменяет launcher/image/fixture. Проверить в отдельном disposable worktree, провести required independent reviews, один exact-source CI, push и открыть отдельный PR. Rollback — revert PR; external persistent migration отсутствует.
