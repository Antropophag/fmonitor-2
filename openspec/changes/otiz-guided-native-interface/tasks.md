## 1. Scope, baseline и Gates 1–3

- [x] 1.1 Обновить `docs/operations/current-delivery-goal.md` новым owner assignment, base `c8bfc42d`, отдельным worktree/branch, запретом merge/deploy/real finance и обязательным показом на stand до PR; проверить, что предыдущий #157 сохранён в Git history, а не переписан как выполненный этим change.
- [x] 1.2 Снять полный route/state/data/command inventory текущего ОТиЗ и зафиксировать mapping: calculate→draft, snapshot review, accept→accepted, accepted-only XLSX, complete/discipline/reverse→append-only ledger; проверить inventory против controllers, projections и focused tests.
- [x] 1.3 Сверить доступный branch/PR #222 и записать точные общие файлы/public interfaces либо отсутствие результата; не менять object card, write model, `MariaDbNativePremiumInputs*` и effective-values mechanism.
- [x] 1.4 Создать `verification-input.json` для `OTIZ-GUIDED-WORKFLOW-001`, выполнить `python3 tools/delivery/harness.py prepare`, прочитать все planner obligations/lane/required_reviews и закрыть либо обосновать каждую категорию до Gate 2.
- [x] 1.5 Root написать complete intended RED на public Yii/read/browser seam: false payment, blocker/warning, read-only GET, persisted reload, historical immutability, row/drawer parity, archive metadata и isolated full journey; выполнить только bounded RED commands и записать evidence.
- [x] 1.6 Если plan требует Gate 3, передать exact prepared source независимому `gpt-5.6-sol / low` reviewer, устранить полный findings list и получить явный `APPROVED` до implementation.

## 2. Read models и правдивые состояния

- [x] 2.1 Добавить единый read-only presentation model для snapshot step/status/saved totals/current availability/blocker-warning counts/ledger meanings; проверить unit/table tests на draft, accepted, zero/no_change, payment, discipline и reversal без новых writes.
- [x] 2.2 Минимально расширить archive/snapshot projections сохранёнными author/time/object-count/document metadata и object+snapshot detail; проверить несколько snapshots одной даты, stable ids и отсутствие подмены исторического snapshot текущими данными.
- [x] 2.3 Устранить fallback «Выплата по объекту выполнена» без payment fact и проверить regression на незаблокированный draft и accepted snapshot с нулевой доступной суммой.

## 3. Native UI варианта B

- [x] 3.1 Привести `_otiz-nav.php` и три режима к единому native visual language и названию «Выполнение расчёта»; проверить канонические URL, active state, back/reload и отсутствие mutations от GET.
- [x] 3.2 Реализовать step presentation дата/подготовка → проверка → подтверждение/XLSX → учёт выплаты поверх существующих commands; проверить main next action, blocker-disabled accept, warning-enabled accept, exact previews и cancel/no-submit.
- [x] 3.3 Перестроить экономику в полноширинную таблицу с девятью колонками, вторичными данными и сохранёнными server search/filter/sort/page; проверить queries вне текущей страницы и отличия unknown/zero/not-calculated.
- [x] 3.4 Реализовать одну shared object+snapshot drawer/detail композицию для экономики/questions/archive с saved trace, coefficients, allocations, issues, sources и existing actions; проверить no client formula, link behavior, focus entry/return, Escape/backdrop/button и narrow readability.
- [x] 3.5 Реализовать archive table с real snapshot metadata, distinct same-date ids, accepted-only real XLSX и настоящей ledger history; проверить, что нет demo feedback и current balances подписаны отдельно.
- [x] 3.6 Добавить только scoped ОТиЗ CSS/behavior через публичные `shlz-ui` exports и Golos Text; проверить first-render geometry, 14/20 body, 12/18 uppercase headers, widths/alignment/scroll, focus states и JS-failure fallback.

## 4. Focused verification и локальный stand

- [x] 4.1 Выполнить planner-selected bounded PHP/HTTP/read-model checks, `git diff --check`, architecture/dependency checks и при затрагивании `app/PilotHttp/*.php` обязательный `pilot_http_auth_001_global_calls_test.php`; полный локальный `make test`/`make verify` не запускать.
- [x] 4.2 Playwright на изолированной fixture проверить полный journey prepare → persisted draft → object/workers → accept → real XLSX download → conditional payment → ledger history, а также blocker/warning, no-change, discipline/reversal, reload/back/query context; сохранить результаты и screenshots вне checkout.
- [x] 4.3 Выполнить один bounded visual pass Playwright desktop+narrow+keyboard по трём tabs и drawer, исправить найденное одним batch и сделать не более одного confirm pass; после finish один раз запустить `impeccable detect --json` по changed UI targets.
- [x] 4.4 Интегрировать candidate в пользовательский локальный stand `http://127.0.0.1:8093`, показать владельцу объект `/pilot/objects/1427` и связанные ОТиЗ screens с реальными native transitions; до явного owner feedback не создавать и не публиковать PR, не выполнять реальные финансовые команды на пользовательских данных.
- [x] 4.5 После результата #222 проверить установленный public effective-values interface на том же object context; записать точные совместимые/пересекающиеся файлы и оставить совместимость `UNKNOWN`, если результата ещё нет.

## 5. Независимое завершение и PR-ready

- [x] 5.1 После owner stand review захватить reconstructible exact candidate, передать независимому `gpt-5.6-sol / low` Gate 5 reviewer, исправить полный findings list и получить явный `APPROVED` для matching source.
- [ ] 5.2 Создать PR только после owner checkpoint и Gate 5, запустить один planner-selected exact-source GitHub CI, собрать полный failed-job/`REGRESSION_FAILURE` inventory при сбое и получить GREEN без локального full-suite повтора.
- [ ] 5.3 Передать PR/HEAD, screenshots, focused checks, review/CI evidence, пошаговый пользовательский результат, неперенесённые prototype actions и точный статус #222; merge/deploy и реальные финансовые действия оставить невыполненными.

## 6. Done definition

- [ ] 6.1 Подтвердить matching PR HEAD: три native режима и общая drawer работают, persisted states правдивы, false-payment defect закрыт, formulas/schema/financial owners не изменены, owner увидел локальный stand до PR, требуемые Gates/CI GREEN, а merge/deploy отсутствуют.
