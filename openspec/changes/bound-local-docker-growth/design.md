## Context

См. `proposal.md`. Диагностика 2026-09-26 показала: checkout 324 МБ, Docker Desktop data 264 ГиБ, 161 image / 176.9 ГБ, BuildKit cache 128.6 ГБ и 355 volumes / 54.06 ГБ. `tools/delivery/run-in-profile` безусловно вызывает `docker build`; исторические worktrees/версии накопили source-specific focused images. Текущий `compose.test.yaml` уже использует `tmpfs`, поэтому происхождение каждого старого anonymous volume неизвестно и blanket volume cleanup недопустим.

## Goals / Non-Goals

**Goals:**

- ограничить новые project-owned image/cache артефакты до предсказуемого бюджета;
- не начинать тяжёлую сборку при опасно малом свободном месте;
- сделать cleanup точным, наблюдаемым, сериализованным и тестируемым без реального удаления;
- закрыть repository-owned disposable lifecycle там, где он создаёт ephemeral resources.

**Non-Goals:**

- автоматически менять Docker Desktop settings или запускать host scheduler;
- удалять существующие local-stand volumes, чужие Docker resources или выполнять одноразовую миграционную зачистку;
- менять CI admission, product behavior либо запускать локальный full suite.

## Decisions

### 1. Один owning seam для storage guard

Новый delivery helper владеет измерением, lock, bounded maintenance и решением «build/reject». `run-in-profile` вызывает только этот seam, а не собирает собственную cleanup policy. Это сохраняет одну точку destructive policy и позволяет fake-Docker контракту наблюдать полную последовательность.

Альтернатива — добавить несколько prune-команд в Makefile — отклонена: границы удаления и обработка ошибок разойдутся между callers.

### 2. Выделенный project builder с собственным GC budget

Focused builds используют deterministic project builder/config с ограничениями cache. Builder создаётся/проверяется идемпотентно; cache cleanup направлен на его identity. Это отделяет FMonitor cache от других проектов лучше, чем prune текущего глобального builder.

Builder и loaded runtime images — разные области. Образы маркируются `org.fmonitor.owner=focused-checks`, профилем и dependency digest. Source SHA остаётся label/result provenance, но не входит в dependency identity.

Альтернатива — глобальный `docker buildx prune` — отклонена как вмешательство в cache других проектов.

### 3. Двухуровневый порог

Repository defaults: hard build floor 50 ГиБ свободного места, cleanup target 80 ГиБ, project builder maximum 30 ГБ, минимальный retention 48 часов. Значения имеют проверяемые environment overrides в разумных границах для CI/fixtures. При достижении hard floor seam выполняет project-only cleanup, повторяет измерение и fail closed, если floor не восстановлен.

Cleanup изображений требует положительного owner label и unused status; отсутствие измерения, lock или поддерживаемой Docker capability блокирует новую тяжёлую сборку, но не инициирует более широкий prune.

### 4. Volumes очищает только их lifecycle owner

Глобальный volume prune не входит в автоматизацию. Отдельный reusable disposable lifecycle seam принимает только validated `fm2-disposable-*` project identity и exact Compose file, связывает `down --volumes --remove-orphans` через trap и сохраняет primary status. Текущая инвентаризация не нашла volume-producing caller, который безопасно мигрировать в этом slice, поэтому seam поставляется и тестируется как обязательная граница для будущих disposable callers, а persistent stand callers остаются неизменными.

### 5. Tests не расходуют Docker storage

Root-authored executable contract использует fake `docker`, `df`, lock и signal harness. Он проверяет полный argv/order, повторное измерение, exit propagation, redaction, concurrency и отсутствие запрещённых команд. Один bounded real smoke допускается только для config/label/identity без создания повторных browser builds; full local suite запрещён текущей owner policy.

### 6. Owning module и зависимости

Owner — `tools/delivery/`; allowed dependencies — POSIX shell/Python standard library и Docker/Buildx CLI. Persistence ограничена Docker metadata/cache и вне-репозиторным lock/state; domain persistence отсутствует. `rapid-pilot/` не меняется. Architecture check должен предотвращать прямой destructive prune вне owning seam и регистрировать новые тесты в verification inventory.

## Risks / Trade-offs

- [Первый build на выделенном builder будет медленнее] → сохранить dependency-addressed cache и не удалять записи моложе retention.
- [Docker/Buildx версии различаются] → capability probe до effects; unsupported environment получает ясный fail-closed outcome и documented host action.
- [Image labels не позволяют выбрать только unused до команды] → использовать штатную семантику image prune, fake/real smoke и положительный owner label; никогда не применять отрицательный/global selector.
- [Trap способен скрыть исходную ошибку] → сохранять primary exit status, cleanup status выводить отдельно.
- [Порог 50/80 ГиБ может быть неудобен на малом диске] → bounded validated overrides без возможности опустить абсолютный safety minimum незаметно.
- [Старые anonymous volumes останутся] → отдельная owner-authorized inventory cleanup; эта change предотвращает новые утечки, но не угадывает владельца исторических данных.

## Migration Plan

1. Зафиксировать normative tooling contract и полный fake-Docker RED matrix.
2. Ввести owning guard/builder seam и подключить один focused runner.
3. Инвентаризировать disposable callers и добавить teardown только доказанным ephemeral lifecycle.
4. Выполнить focused tests, architecture check и независимый final review на exact source.
5. Провести один exact-source CI run выбранного planner lane.

Rollback удаляет подключение guard и dedicated builder, не трогая уже созданные Docker resources. Existing builder/cache остаются пригодны для последующей явной owner cleanup.
