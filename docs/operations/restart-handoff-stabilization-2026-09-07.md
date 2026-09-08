# Restart handoff — стабилизация до VERIFY_OK

Владелец вернулся и запросил перезапуск сессии. Работа приостановлена в безопасной
точке; глобальная цель ACTIVE, VERIFY_OK **не достигнут**. Все три агента остановлены,
их DB/browser/worker процессы завершены, DB slot свободен. Новый полный verify не
запущен. Продолжить существующую работу, не начинать заново и не откатывать WIP.

## Источник и рабочий стенд

- Ветка `codex/remove-pilot-work-navigation-v2`.
- Последний HEAD до этого документа: `67667cf9540bd6665042e065253db2bd2c794d52`.
- `77701c5`: checkpoint и address/remote research.
- `b662e1e3a08c206c06f4df40a992fbd083856c1b`: полный registration number и изоляция/
  cleanup bootstrap verifier, оба с независимым APPROVED review и focused GREEN.
- `67667cf`: сохранение результата полного verify и промежуточного состояния.
- **Worktree содержит существенный незакоммиченный WIP**, перечисленный ниже.
  Он не считается approved или готовым к установке только потому, что сохранён.
- Установлен source `6aa39aa79d0c2e7cbd1a140f2110fcf2199fb9d4`, image
  `sha256:a624164ee5cf7aafd7c077f3c843377bd3e8b8a73bf989a8e8071f8aacd9674a`.
- Container `fmonitor2-manual-pilot-1`: последний read-only check `running healthy`.
- URL `http://127.0.0.1:8092/pilot/objects`; прежняя owner-admin учётная запись.
- Данные владельца, object 966, originals/history и volumes сохранены.

## Резерв WIP

Private directory:
`~/.local/state/fmonitor2/manual-pilot-20260907/runtime/restart-stabilization-checkpoint/`.
Содержит `tracked-wip.patch` (binary diff), `untracked-wip.tar.gz`, `sha256.txt`
(52 файла на момент снимка), `head.txt`. Резерв сделан после остановки агентов;
directory0700/files0600. Основной источник продолжения — существующий worktree.
**Не применять patch поверх уже существующих изменений.** Резерв нужен при потере
worktree. `.DS_Store` не включены и остаются пользовательскими untracked файлами.

## Что уже установлено

Все присланные manual fixes до стабилизации доставлены на source6aa39aa: layout
декларации, стабильный bulk checklist overlay, настоящие date defaults ПТО/
декларации, имена авторов, фильтр завершённых в стройконтроле, текущий ОТиЗ progress,
inline PDF и уточнённый original flow.

Последний controlling flow: original upload + confirmation на той же странице
→ карточка → отдельное явное «Открыть работы» с фактической датой. Отдельного ручного
«Применить состав» нет; compound `open_confirmed` атомарно создаёт внутренние
application/opening facts. У загрузчика и открывающего разные полномочия/учётные
записи. GET ничего не применяет. Синтетический headless golden до100% уже PASS
на source6aa39aa, включая different actors, pending reload и нетронутые даты.

Подробности исходного checkpoint: `stabilization-checkpoint-2026-09-07.md` и
`original-confirmation-direct-opening-owner-decision-2026-09-07.md`.

## Полный verify: терминальный результат

Exact source `6aa39aa`, detached worktree `/Users/antropophag/code/fmonitor-2-verify-6aa39aa`.
Log `~/.local/state/fmonitor2/manual-pilot-20260907/runtime/verify-6aa39aa.log`.
`FULL_VERIFICATION_FAILURE count=4 stages=unit-test,db-test,characterization-test,e2e-test`.
Setup/migrate/architecture/lint/diff-check PASS. Старые session30319/PID52546
**завершены**, не ждать и не возобновлять их.

Причина десяти PDF-related failures — отсутствующий gitignored vendor в detached
checkout. После окончания прогона скопирован проверенный main vendor; все десять
affected focused tests PASS, log `runtime/pdf-dependency-focused.log` заканчивается
`FOCUSED_FAILURES []`. Clean checkout перед следующей проверкой обязательно
подготовить: TCPDF6.11.4 pinned commit `fbbaf14cfae8fe646f154f7c530d15ec25764040`,
autoload из `rapid-pilot/tcpdf-autoload.php`, как в Dockerfile. Наличие vendor не
меняет source SHA. PATH должен включать `/opt/homebrew/bin`.

## Незавершённые пакеты

### 1. Native UI verifier reconciliation — architecture_diagnosis

Change `openspec/changes/reconcile-pilot-queue-shell-verifiers/`.
- `pilot_object_list_001_test.php` focused **GREEN**.
- `pilot_ui_shell_001_test.php` focused **GREEN**.
- `pilot_object_card_001_test.php` **WIP, последняя правка не проверена**.
  Последний RED: `definition-list section Распоряжение Expected1 Actual0`.
  Helper уже разрешает empty-document panel без dl; populated panels по-прежнему
  требуют paired dt/dd. Следующий шаг — focused card, затем остальные stale
  orderedVisible assertions заменить exact DOM field/panel/date expectations,
  сохранив RBAC, escaping, историю и read-only гарантии.
- Review draft `RECONCILE-PILOT-QUEUE-SHELL-VERIFIERS-2026-09-07.md` устарел
  относительно последних hashes/spec; обновить после card GREEN и независимо
  рецензировать весь пакет. Module-manifest review был approved отдельно, не whole card.

Важная граница: `public/router.php` — native adapter seam с unfiltered collection,
ignored query и прежним cap500/501. Он уже рисует новую table, но q/status/page
добавляются `rapid-pilot/router.php`. Нельзя требовать rapid фильтры от native seam
или начинать архитектурную миграцию ради ошибочного теста. Native configured
`/pilot/` сохраняет compatibility body «Моя работа», но navigation такого пункта
не содержит. Actual rapid **`/`** делает302→objects; **`/pilot/`** остаётся200.
Предыдущее ожидание redirect `/pilot/` было ошибкой тестового планирования.

### 2. Protected current E2E — verify_triage

Change `openspec/changes/reconcile-protected-pilot-e2e-current-flow/`.
Modified `tests/InstallationProcess/pilot_e2e_flow_001_test.php`; new
`tests/Support/pilot_current_flow_browser.cjs`. Исторический protected SHA
`8f0d3626401b4a638bb56be40e17129626f1fc320ceeda6be798e3079294909b` и mapping
retained/superseded assertions сохранены в `assertion-map.md`. Это **draft, не APPROVED**.

Retained child contracts уже проходили. Browser journey ещё не GREEN. Исправлены
review findings: отсутствующие requires/env aliases, tautological PDF assertion,
общие cookies, role collision, child process-group cleanup и потеря failure evidence.
Теперь три контекста (uploader18, opener19, engineer73), Google Chrome headless,
PDF200/application/pdf/inline/bytes/no download, correct root semantics.

Последний log `runtime/protected-current-e2e-development.log` содержит уже
устаревший RED о `/pilot/` redirect. После него добавлены queue prerequisites:
InspectionPlanning/ClassificationProvenance migrations и valid hashed object_details
eligibility row. **Эти последние изменения не запускались.** Следующий шаг —
browser/fixture diagnostic; не гонять восемь уже GREEN child contracts при каждой
правке selector. Использовать отдельный private diagnostic, не добавлять skip flag
к защищённому тесту. Финальный полный protected run обязан выполнить всё.
Успешный прежний selector oracle:
`runtime/direct-opening-browser-20260907/golden-browser.cjs` (private state).

### 3. Photo re-upload/schema19 — auth_review, root

Change `openspec/changes/allow-identical-photo-reupload-after-revoke/`.
GRILL-007 разрешает повторные bytes после revoke как новую evidence identity при
вечном сохранении revoked rows/blobs. Новая migration19 меняет только unique
content index на non-unique ordered lookup. Runtime принимает exact v8 или exact19;
literal v8 остаётся исторически точным, catalogue явно распознаёт successor no-op.

Author reports **focused GREEN**: populated migration/allocator/history/prefix,
full catalogue first+repeat, re-upload characterization, existing upload,
photo-limit concurrency, manual checklist HTTP, global calls, architecture7.
См. `inspection-photo-content-index-v19-green-2026-09-07.md`.
Есть supplemental fixture fixes (mysqli integer metadata и standalone autoload).
**Нужны повторное вычисление hashes и независимый code/test supplemental review.**
Никакая migration19 на пользовательский стенд не устанавливалась.

Root подготовил **16 current-frontier consumer updates18→19**, только tests и
calendar verifier: `reviews/tests/CANONICAL-FRONTIER-019-CONSUMERS-2026-09-07.md`.
Lint16 PASS; независимый review/исполнение pending. Shared historical catalogue
helpers не изменены; mixed inspection test явно различает v8 и current19.
Известный TODO: в `assignment_order_original_attempt_audit_schema_001_test.php`
остался `assertSameValue([true,18], …schemaVersion)` и copy final version18 —
проверить и исправить только current-runner ожидание, не actor18.

## Следующие шаги и ограничения

1. Прочитать current delivery goal и этот handoff, проверить actual git status/HEAD,
   persistent goal и hashes; существующий WIP не стирать.
2. Закончить card/E2E diagnostics и review; закончить photo supplemental/code review
   и 16 frontier focused tests. Авторы не рецензируют собственные изменения.
3. Зафиксировать reviewed candidate. Только после focused GREEN запускать следующий
   полный make verify в подготовленном чистом exact-SHA checkout.
4. После literal VERIFY_OK — source/image, backup/populated deployment/restart/golden
   evidence; далее ранее подготовленная GitHub CI/Quality Graph интеграция по правилам.

Все агенты `gpt-5.6-sol`, low, fork none; координировать DB slot, не сбрасывать БД
под чужим прогоном и не удерживать slot во время редактирования. Browser только
headless, реальные окна владельца не трогать. Объект966 нельзя мутировать тестами.
Секреты/primary evidence вне repo; volumes/history/originals/queues сохранять.
Для образов брать git archive exact commit, не весь параллельный worktree.
Source files0644, private dirs0700/files0600; launcher scripts executable.

Новые features/address enrichment — backlog; research уже сохранён. CI publication
до первого literal VERIFY_OK, PR10 merge, реальные Bitrix calls/imports остаются
ограничены current goal. Не объявлять readiness по отдельным GREEN или checkpoint.
