## Context

См. `proposal.md` — Why. Защищённый тест остаётся ценным из-за реального HTTP, MariaDB, filesystem artifact и повторного чтения, но его центральный happy path зафиксирован до последних owner decisions. Новое runtime behavior уже принадлежит существующим public application seam; эта change меняет только oracle/fixture/bootstrap.

## Goals / Non-Goals

**Goals:**

- Сохранить защищённый файл как один сквозной release-critical verifier актуального маршрута.
- Явно отделить retained security/data checks от superseded representation checks.
- Получить воспроизводимый clean-checkout E2E с точным failure classification и review provenance.

**Non-Goals:**

- Не менять product/runtime behavior ради старых assertions.
- Не возвращать manual registration, отдельный apply UI, упрощённый UI или legacy artifact split.
- Не использовать object 966, owner accounts, stand volumes, Bitrix или production import.
- Не публиковать CI и не объявлять `VERIFY_OK` из результата одного protected verifier.

## Decisions

### 1. Oracle строится по операциям, а не по старой HTML-композиции

Основные checkpoints: очередь/карточка, selection, template, original initial/correction, card return, `open_confirmed`, checklist, completion. DOM assertions проверяют доступное действие, точные поля и важную структуру, но не требуют старую universal semantic list. Альтернатива — минимально заменить строки старого теста — отвергнута: она оставляет несовместимую модель маршрута.

### 2. State-changing owners вызываются только через public HTTP seams

Selection/original/opening/checklist/completion остаются владельцами своих фактов. Compound opening принадлежит application owner, который атомарно создаёт application/opening/template facts. Protected fixture не пишет эти таблицы вместо команд, кроме начальных синтетических prerequisites. Rapid-pilot остаётся adapter/oracle, а не владельцем домена.

### 3. Сохраняется отрицательная матрица, связанная с реальным риском

Retained: identity/role, CSRF/Origin, method/route/body/media, concurrency/replay, exact PDF GET/HEAD, redaction, no partial write, foreign/legacy preservation, fresh connection. Superseded: ручной registration number, registration endpoint, separate apply, старые copy/status/navigation и appendix split. Это исключает механическую замену failure на success.

### 4. Fixtures отражают разные роли

Uploader подтверждает original, opener имеет `installation.open`, viewer получает read-only representation, инженер выполняет checklist. Opener не получает standalone composition apply только ради compound command. Actor IDs и dates проверяются в созданных native events.

### 5. Clean-checkout prerequisites фиксируются до verifier

Bootstrap обязан поднять disposable MariaDB, мигрировать canonical frontier и обеспечить checkout-local PDF dependency TCPDF 6.11.4, как в production Dockerfile. Browser checks используют headless Playwright, а не окна владельца. Protected test не должен сам скачивать dependency или обращаться к production.

### 6. Protected patch требует независимого процесса

До изменения сохранить исходный SHA и mapping old→new assertions. Автор patch не выполняет test/code review. Review подтверждает неизменность retained checks, затем focused test, bootstrap caller, полный E2E stage и exact-SHA `make verify` дают последовательное evidence.

## Risks / Trade-offs

- [Большой monolithic verifier затрудняет review] → группировать assertions по пользовательским операциям и приложить таблицу retained/superseded.
- [Runtime regression может быть скрыта как fixture alignment] → запретить runtime edits в change и требовать независимый diff review protected test.
- [Compound opening partial failure недостаточно виден через happy path] → сохранить отдельный post-application rollback scenario в focused contract и повторить его из bootstrap при необходимости.
- [Внешняя dependency отсутствует в clean worktree] → явный preflight checkout-local TCPDF до запуска, без skip.

## Migration Plan

1. Зафиксировать protected file SHA и assertion inventory.
2. Изменить только test fixture/expectations и bootstrap wiring.
3. Выполнить независимый review exact diff.
4. Запустить protected test и его bootstrap caller на disposable DB.
5. Запустить полный exact-SHA `make verify`; при failure не объявлять readiness.
6. Rollback — вернуть только protected patch к зафиксированному SHA; runtime/data rollback не требуется.

### 7. Demo CLI bootstrap использует текущую canonical frontier

Private post-contract diagnosis после E2E reconciliation дал повторяемый normal-smoke
vector: queue `401`, card `503`, selection form `401`, foreign card `503`, при этом
оба CSS assets `200`, CSS HEAD `200`, unknown asset `404` и graph exact. Причина —
demo provision оставался на v4/восьми process tables и передавал только
`REMOTE_USER`, тогда как текущий adapter требует local actor и current card schemas.

Bootstrap SHALL применять public canonical migration catalogue до v19, создавать
только synthetic local identity/roles/grants и выводить trusted actor из единственной
active local identity с exact configured email. Нельзя подставлять actor 18 для
произвольного `REMOTE_USER`. Marker ownership, failure atomicity, foreign data,
permissions и запреты reset/cleanup во время работы сохраняются.

Current composition требует один generation namespace для native и imported
source tables. Новые поколения используют общий process/legacy prefix; два
разных nonce anchor table comments сохраняются. Исторические dual-prefix demo
generations не переписываются и не мигрируют автоматически.

Bootstrap verifier независимо перечисляет полный literal canonical19 catalogue,
проверяет active local users, role assignments и exact permissions. `status`
перепроверяет фактический catalogue: одного `ready.json` version19 недостаточно.
Для launcher persistence bootstrap выполняет current composition selection;
полный current flow остаётся обязательным protected child.

Старые v4/exact-eight-table и manual-registration representation assertions
superseded текущим pilot flow. Их security/history смысл не удаляется: protected
current E2E и retained child contracts проверяют original/opening/checklist flow;
demo CLI продолжает проверять lifecycle startup/status/restart/cleanup и сохранность
данных на current-compatible synthetic generation.
