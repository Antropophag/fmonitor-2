# BACKLOG-ISSUE-LABELS-001 — классификация открытого GitHub backlog

Версия: 0.1
Источник решения: issue #256 и OpenSpec change `classify-backlog-issue-labels`.

## 1. Публичный seam и полномочия

Единственный state-changing seam этой поставки — GitHub Issues/Labels API
репозитория `Antropophag/fmonitor-2`. Записи разрешены явным поручением владельца
на issue #256 и ограничены десятью labels ниже и краткими недублирующими
пояснениями для `prep:needs-work`/`status:blocked`.

`prep:ready` не является `agent-ready`, не запускает агента и не разрешает
merge, deployment или пропуск Gates. Закрытые issues, pull requests, priorities,
Quality Graph, workflows, CI/admission и остальные labels не изменяются.

## 2. Exact label schema

| Label | Color | Русское описание |
|---|---|---|
| `type:product` | `1d76db` | Пользовательские функции, исправления пользовательских ошибок, UX и бизнес-правила. |
| `type:tech-debt` | `1d76db` | Технический долг приложения без самостоятельного нового пользовательского результата. |
| `type:harness` | `1d76db` | Инструменты агентской разработки и поставки: harness, CI, Quality Graph, gates и reviews/evidence. |
| `type:tracking` | `1d76db` | Сводные аудиты, roadmap, эпики-реестры и учёт нескольких самостоятельных остатков. |
| `prep:triage` | `5319e7` | Готовность ещё не оценена либо достоверных данных для оценки недостаточно. |
| `prep:needs-work` | `5319e7` | Есть конкретные пробелы в решениях, границах, приёмке или декомпозиции. |
| `prep:ready` | `5319e7` | Постановка достаточно определена для начала реализации по действующему процессу. |
| `status:blocked` | `d93f0b` | Есть подтверждённое препятствие или неудовлетворённая зависимость. |
| `status:deferred` | `d93f0b` | Есть актуальное решение владельца отложить задачу. |
| `status:in-progress` | `d93f0b` | Есть актуальное подтверждение, что задача уже выполняется. |

## 3. Инварианты классификации

Для каждого issue в полном paginated открытом наборе, исключая pull requests:

- ровно один `type:*`;
- для `type:tracking` — ни одного `prep:*`;
- для остальных типов — ровно один `prep:*`;
- неизвестная готовность — `prep:triage`, а не выдуманный пробел;
- `prep:needs-work` и `status:blocked` имеют конкретное актуальное пояснение;
- каждый `status:*` имеет положительное актуальное основание; отсутствие status
  ничего не доказывает;
- сторонние и `quality-graph:*` labels сохраняются.

Перед каждой записью issue перечитывается. Изменившаяся issue переоценивается,
закрытая пропускается, а mutation добавляет/снимает только labels схемы. Повтор
при неизменных данных не создаёт mutations или дублирующих comments.

## 4. `prep:ready`

`prep:ready` требует понятных проблемы/сценария, результата и проверяемой
приёмки; bounded scope и существенных non-goals; принятых продуктовых решений;
названных зависимостей; сверки с актуальными source/PR и ясного непоставленного
остатка. RED, готовый код и пройденные Gates заранее не требуются.

## 5. Конкурентная безопасность и итог

Before snapshot содержит время, полный label catalogue и все открытые issues.
План фиксирует номер, `updatedAt`, type, prep, statuses и основания. После
точечных записей полный набор читается снова: новые issues классифицируются либо
явно задают границу отчёта. Итоговый отчёт фиксирует охват, распределение,
пробелы/блокеры, фактические и неприменённые mutations. GitHub mutations уже
действуют и не откатываются автоматически исходом repository PR.

## 6. Deterministic evidence envelope

Focused delivery evidence — один JSON object `schemaVersion: 1` со следующими
обязательными полями:

- `repository`: exact `Antropophag/fmonitor-2`;
- `before` и `after`: `capturedAt` в UTC, полный label catalogue как
  `{name,color,description}` и полный открытый issue set без PR как
  `{number,state,isPullRequest,updatedAt,labels,comments}`, где comment witness —
  `{id,createdAt,bodySha256}`;
- `plan.snapshotCapturedAt`: exact `before.capturedAt`; `plan.issues` содержит
  ровно каждый before issue как `{number,observedUpdatedAt,type,prep,statuses,reasons,
  explanationBodySha256,existingExplanation}`, а
  `existingExplanation.needsWork/blocked` содержит `null` либо exact
  `{commentId,bodySha256}` witness из before snapshot; witness hash обязан
  совпадать с `explanationBodySha256` соответствующей причины;
- `operations`: append-order records `{kind,issue,label,rereadUpdatedAt,
  reclassified,outcome,reason}`, где kind ограничен `label.upsert`,
  `issue.label.add`, `issue.label.remove`, `issue.comment`; issue operations
  могут касаться только before/after open issues, а label operations — только
  десяти labels схемы;
- `skippedClosedIssueNumbers`, `excludedPullRequestNumbers`, `newIssueNumbers`
  и `classifiedNewIssueNumbers` задают границы полного набора;
- `failures` перечисляет каждую неприменённую запись; успешный итог имеет пустой
  массив, а любой `FAILED` operation обязан иметь коррелированную failure;
- `rerunOperations` — операции dry rerun после итогового чтения; успешная
  идемпотентная миграция имеет пустой массив.

Для каждой issue `observedUpdatedAt` совпадает с before snapshot. Перед любой
issue mutation обязателен `rereadUpdatedAt`; если он отличается, ставится
`reclassified: true`. `prep:needs-work` и `status:blocked` требуют непустых
`reasons.needsWork`/`reasons.blocked` и ровно одного нового comment только когда
эквивалентного актуального пояснения не было. Новый comment operation содержит
`resultCommentId` и `bodySha256`, совпадающие с after witness; все before comment
witnesses сохраняются неизменными. `status:deferred` и `status:in-progress`
требуют непустых `reasons.deferred` и `reasons.inProgress`. Для номера из
`skippedClosedIssueNumbers` допустимы только `SKIPPED_CLOSED` issue operations.
Сторонние labels каждой surviving
issue и весь сторонний label catalogue до/после совпадают. After issue set равен
before set минус `skippedClosedIssueNumbers` плюс `newIssueNumbers`; все новые
issues либо классифицированы, либо итог считается неполным. Ни один номер PR не
может присутствовать в plan или issue operations.

Для каждой surviving before issue exact множество labels схемы в after равно
`{type} + {prep, если не null} + statuses` из её итоговой plan-записи. Каждый
APPLIED comment связывается своим hash с ожидаемым `explanationBodySha256`.
Кроме того, append-order replay issue label journal начинается с before labels
схемы (для новой issue — с пустого множества), применяет только `APPLIED`
`issue.label.add/remove` и обязан получить exact after labels схемы. `NOOP`,
`FAILED`, `SKIPPED_CLOSED` и comment operations label state не меняют.
