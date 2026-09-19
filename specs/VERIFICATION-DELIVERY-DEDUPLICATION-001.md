# VERIFICATION-DELIVERY-DEDUPLICATION-001 — однократный CI/E2E и короткое повторное review

## Простыми словами

Один и тот же браузерный сценарий не должен исполняться через inventory и ещё раз через обёртку. Перед ручным Quality Graph dispatch агент сначала недолго ищет уже созданный штатный PR-run и безопасно переиспользует только точно применимый run. Повторное review получает дельту и состояния прежних замечаний; чисто косметическая галочка или опечатка PR после одобрения не запускает новый code-review цикл.

Этот срез не отключает проверки, не объединяет результаты разных runs, не вводит глобальную exactly-once гарантию и не меняет workflow/branch settings, FAST classifier, merge/deploy или production application.

## 1. Идентификатор, актор и публичные швы

- Идентификатор: `VERIFICATION-DELIVERY-DEDUPLICATION-001`.
- Актор: delivery agent, действующий только в уже выданных полномочиях владельца.
- Oracle browser selection: repository-owned `tools/verification/suites.tsv` и acceptance mappings.
- Browser seam: существующий verification runner, запускающий `tests/Yii2/yii2_preopening_browser_001_test.php`.
- CI seam: команда `tools/delivery/ci-launch.py`, принимающая repository/workflow/head/base/mode и использующая инъецируемый GitHub transport.
- Review seam: prepared role/review package и `tools/delivery/handoff-template.md`.
- Persistence: новой БД или repository ledger нет; выбранный run identity живёт в результате одного launcher invocation, а итоговые ссылки хранятся в PR/существующем внешнем delivery record.

## 2. Общие инварианты

1. `UNKNOWN` не является GREEN, approval или разрешением dispatch/retry.
2. Один GitHub run является неделимой единицей CI evidence; jobs разных runs не объединяются.
3. Совпадения HEAD недостаточно: применимость включает repository, workflow, exact head, exact base, trigger/mode и обязательные результаты существующего observer/admission пути.
4. Исторические review records append-only и не переписываются.
5. Изменение не расширяет GitHub, merge, deploy, settings или retry полномочия.
6. Локальный focused run и exact-source GitHub CI — разные обязательные уровни; канонический browser-сценарий исполняется один раз на каждом.

## 3. Acceptance A1 — единственный browser witness

`yii2_preopening_browser_001_test.php` является каноническим executable witness для соответствующих SHLZ operational UI acceptance mappings. Inventory и mappings MUST ссылаться на него напрямую и MUST NOT выбирать `yii2_shlz_operational_ui_001_test.php` либо иную обёртку, которая повторно запускает тот же тест.

После изменения файл прежней исполняющей обёртки отсутствует; `tools/verification/suites.tsv` содержит канонический browser test ровно один раз в `e2e`; каждый перенесённый acceptance mapping указывает канонический test; browser assertions, fixtures, isolation и исходная классификация intended RED/regression/environment failure не меняются.

## 4. Acceptance A2 — decision table CI launcher

Launcher выполняет bounded discovery штатного `pull_request` run до manual dispatch без участия модели в poll loop.

| Наблюдение после bounded discovery | Результат | Dispatch |
|---|---|---:|
| применимый `queued`/`in_progress` | `REUSE`, identity/link переданы observer | 0 |
| применимый `completed` | `REUSE`, тот же run передан проверке результата | 0 |
| достоверно нет применимого run | `DISPATCHED`, создан один manual run и захвачен его identity | 1 |
| stale либо mismatch repository/workflow/head/base/trigger/mode | `BLOCKED_MISMATCH`; не GREEN | 0 |
| применимый `failure`/`cancelled` | `OBSERVE_FAILURE`; полный triage, без automatic retry | 0 |
| API error, incomplete response либо UNKNOWN applicability | `UNKNOWN`; явный blocker | 0 |

После единственного fallback dispatch последующие polls внутри invocation отслеживают захваченный run и MUST NOT повторять dispatch. Прямые Actions API/UI действия владельца вне этого маршрута не покрываются глобальной exactly-once гарантией.

## 5. Acceptance A3 — применимость и обязательные результаты

Launcher только выбирает run; итог CI продолжает определять существующий observer/admission механизм. Completed run без подтверждённых обязательных jobs/results не становится GREEN. Изменённая base при том же HEAD является mismatch. Failure/cancelled сохраняют исходный результат и требуют действующего triage; автоматический retry допустим только по отдельному явному основанию существующего процесса.

## 6. Acceptance A4 — correction package

При повторной передаче reviewer package MUST содержать ссылку/identity полного кандидата и последнего проверенного source; delta между ними; полный список открытых findings предыдущего review; disposition каждого finding (`fixed`, `open`, `not-applicable` с основанием либо blocker); новые риски delta, если они расширяют проверку.

Открытый finding не может быть представлен как исправленный. После двух возвратов по одной неустранённой причине root пересматривает подход текущего среза либо сообщает blocker. Reviewer suggestion вне согласованного контракта не становится обязательным молча и отделяется от дефекта текущей acceptance.

## 7. Acceptance A5 — косметика после approval

После одобрения новый code review не требуется только когда diff состоит исключительно из checkbox завершения и/или исправления опечатки номера PR и не меняет нормативные или исполняемые байты, source/evidence binding, полномочия либо утверждение GREEN/approval.

Классификация и diff фиксируются в PR/существующем внешнем delivery record. Любое материальное изменение сохраняет применимые проверки и независимый review нового кандидата. Проверяемый commit не обязан содержать будущий результат собственного CI; для одного сообщения «CI GREEN» отдельный commit не создаётся.

## 8. Rejections и отсутствие побочных эффектов

- `CI_RUN_MISMATCH`: имеется только неприменимый run; dispatch=0, GREEN не заявляется.
- `CI_DISCOVERY_UNKNOWN`: API/ответ не позволяет доказать отсутствие или применимость; dispatch=0.
- `CI_RUN_FAILED` / `CI_RUN_CANCELLED`: исходный run передаётся triage; dispatch=0 и automatic retry=0.
- `REVIEW_FINDING_OPEN`: package сохраняет finding как blocker; approval не заявляется.
- `REVIEW_DELTA_MATERIAL`: косметическое исключение неприменимо; новый candidate проходит применимые проверки/review.

Ни один rejection не меняет workflow, branch settings, merge/deploy state или исторические review records.

## 9. Независимые примеры

- №194: два runs, созданные автоматически и вручную с разницей в секунды для одного кандидата, дают `REUSE` штатного PR-run и ноль manual dispatch; поздняя правка только checkbox/PR typo документируется без нового code review.
- №195: реальный незакрытый finding остаётся `open` и блокирует approval; предлагаемые улучшения вне контракта перечисляются отдельно. Browser inventory выбирает канонический journey один раз вместо прямого и обёрнутого запуска.

## 10. Done

Gate 3 одобряет полную mapping/RED матрицу. Отдельный executor реализует A1–A5. Bounded local checks и один фактический focused browser run GREEN; независимый Gate 5 `APPROVED`; собственный PR использует launcher без параллельного ручного дубля и один exact-source CI GREEN. PR, CI или enforcement, которые нельзя подтвердить, остаются `UNKNOWN` и блокируют PR-ready утверждение.
