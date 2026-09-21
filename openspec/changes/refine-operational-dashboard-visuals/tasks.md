## 1. Gate 1 и planner

- [x] 1.1 Root создаёт нормативный `specs/YII2-OPERATIONAL-DASHBOARD-REFINEMENT-001.md` с полным контрактом refinement и вручную сверяет каждое OpenSpec requirement/scenario с ним
- [x] 1.2 Root обновляет current-delivery goal и delivery record, фиксируя owner-approved prototype как primary visual source, baseline от актуального `origin/main`, авторизацию bounded fix и отсутствие разрешения на merge/deploy
- [x] 1.3 Root создаёт `verification-input.json` со всеми планируемыми production/test/spec/icon/asset boundaries, запускает `python3 tools/delivery/harness.py prepare`, читает каждую obligation и подтверждает `missing_tests=[]` до Gate 2; lane и required reviews берутся только из planner

## 2. Gate 2 — полный RED candidate

- [x] 2.1 Добавить data-seam RED fixture для пяти risk buckets, границ cutoff/-1/0/6/7/13/14, неизвестной даты, взаимного исключения этапов, bounded query/DTO и read-only fingerprint; проверить intended RED на merged PR #217
- [x] 2.2 Добавить HTTP RED для `GET|HEAD /pilot/dashboard` и точного `chart=start-risk` drill-down, включая RBAC, search/page composition, invalid/conflicting/client-range `400`, filtered total и atomic `503`; проверить intended RED без setup failure
- [x] 2.3 Усилить browser RED: CSP console, proportional nonzero/zero heights, semantic colors, exact 23→23 replacement composition, KPI/header/baseline geometry, equal weekly pair widths/no overlap, two-line week ranges, no repeated row labels, breakpoint `1201/1200`, viewports `1440/1201/1200/1051/900/681/680/390` и отсутствие overflow
- [x] 2.4 Добавить navigation RED для exact public icon identities/provenance/hashes, порядка «… Монтажники → Дашборд», Interface Calendar и stable fill/stroke на Dashboard/Calendar/Objects/Construction Control/Users/Roles при разных AssetBundle orders
- [x] 2.5 Planner потребовал Gate 3; после двух corrections полный root-authored RED candidate получил независимый APPROVED review без открытых findings

## 3. Реализация bounded fix

- [x] 3.1 Реализовать общий start-risk predicate vocabulary в dashboard read model и object queue, server-derived ranges и fixed DTO shape; проверить data/HTTP RED становится GREEN и 30k envelope остаётся bounded
- [x] 3.2 Реализовать CSP-safe SVG mark/frame и согласованную цветовую семантику без inline style, JS/canvas или новой зависимости; проверить console/height/color assertions
- [x] 3.3 Реализовать компактные KPI tracks, week range labels, chart header/plot alignment, fixed bar tracks и responsive stack; проверить все граничные viewport и одну batched desktop/mobile visual inspection
- [x] 3.4 Подключить exact public icon exports `shlz-ui`, порядок навигации и единый content-hash cache-busting helper для menu-bearing AssetBundle; проверить route matrix и отсутствие stale fill/stroke
- [x] 3.5 Удалить из production candidate prototype-only постоянные query versions/временные решения, запустить `impeccable detect --json` один раз по итоговым UI targets и подтвердить отсутствие debug/prototype hooks

## 4. Gate 4–5 и публикация

- [x] 4.1 Root сверяет candidate со всеми planner obligations и запускает только selected bounded focused checks плюс `openspec validate refine-operational-dashboard-visuals --strict`; полный локальный `make test`/`make verify` не запускать
- [x] 4.2 Получить обязательный независимый final review exact reconstructible source; при test/spec изменениях пересчитать plan и выполнить требуемый review restart
- [ ] 4.3 После APPROVED review создать PR-ready commit/PR и выполнить один selected exact-source GitHub CI consumer; при сбое сначала собрать полный failed-job и `REGRESSION_FAILURE` inventory
- [ ] 4.4 Обновить tasks/delivery record точными source, авторами, review/CI evidence и UNKNOWN; Done означает GREEN selected checks, required reviews и exact-source CI, но не merge/deploy
