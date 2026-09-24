# OTIZ-SETTLEMENT-FORM-RECOVERY-001 — ввод суммы и восстановление формы ОТиЗ

## Простыми словами

Сотрудник ОТиЗ вводит сумму удержания привычным способом, а при понятной ошибке не набирает форму заново и не теряет объект. Устаревший расчёт по-прежнему запрещён, но объясняет дальнейший путь. Финансовые правила, запись ledger и история #248 не меняются.

## 1. Public seam и границы

Актор — authenticated active user с current `otiz.manage`. Публичные seams:

- `POST /pilot/otiz/snapshots/{snapshotId}/closures` → существующий `OtizSettlement::recordDiscipline`;
- `POST /pilot/otiz/snapshots/{snapshotId}/payments/complete` → существующий `OtizSettlement::completeSnapshotPayments`;
- последующий Yii2 GET/render исходного snapshot/error state.

Yii2 adapter SHALL выполнять только строгую нормализацию presentation input и отображение outcomes. `OtizSettlement` остаётся единственным financial writer. Не меняются формулы, limits owner `1..1000000000000` cents, settlement fingerprints/outcomes, signed ledger, snapshots, object-wide history #248, XLSX, schema, routes, роли, CSRF и права.

## 2. A01 — strict rubles-to-cents

Допустимы только положительные строки:

- без группировки: `DIGITS`, опционально `.` или `,` и одна/две fractional digits;
- с группировкой ASCII space: первая группа 1–3 digits без leading zero, далее одна или больше групп ровно по три digits, опционально `.` или `,` и одна/две fractional digits.

Leading zero допустим только для целой части `0`; итог после дополнения одной fractional digit справа SHALL быть `1..1000000000000` cents. Parser MUST использовать decimal strings/checked integer arithmetic, никогда float или округление. Он может удалить только spaces после успешной проверки grouping grammar.

Worked values: `1000`→`100000`; `1000,5`/`1000.5`→`100050`; `1000,50`/`1000.50`/`1 000,50`→`100050`; `10000000000`→`1000000000000`. Canonical `1000.00` сохраняется.

Отклоняются до owner: empty, zero (`0`, `0.00`), negative/plus, exponent, tabs/NBSP/leading/trailing spaces, letters/currency, `01`, `1 00`, `10 00,50`, `1,000.50`, `1.000,50`, repeated/mixed separators, missing/three+ fractional digits и результат `>1000000000000`. Rejection создаёт zero closures/events/operation receipts/jobs/outbox и не меняет snapshots.

Поле SHALL видимо объяснять поддерживаемые примеры до submit; server validation идентична при disabled JavaScript.

## 3. A02 — known validation refusal сохраняет форму

При invalid amount, empty/over-500 basis или over-300 artifact controller SHALL применить POST/redirect/GET и one-time server-side state. Allowlist состояния: exact `snapshotId`, valid member `objectId`, submitted `operationId`, `discipline`, `basis`, `artifact`, field errors. Unknown POST fields, CSRF, cookies, credentials и другие secrets не сохраняются.

Redirect Location содержит только stable snapshot/object/error markers, но не values/basis/artifact/operationId. Приложение не пишет значения в logs. GET повторно проверяет, что object принадлежит snapshot; mismatch не раскрывает state.

View SHALL HTML-escape values и errors, открыть exact object drawer, дать error summary `role=alert`, связать field error через accessible description и при JavaScript перевести focus на summary/первое invalid field. Без JavaScript форма и ошибка видимы на той же snapshot page. Flash потребляется один раз.

Fixture: object B, amount `10 00,50`, basis длиной 500 с `<script>`, artifact длиной 300 и extra secret field. Ответ сохраняет только три permitted strings и исходный operationId для B, DOM не создаёт script, URL/Location/log не содержит strings/secret, facts byte-equivalent.

Исправленная явная отправка использует тот же operationId. Existing `recorded`, exact replay и `OPERATION_CONFLICT` semantics дают не более одной append-only closure; object-wide history #248 продолжает показывать её среди всех расчётов.

## 4. A03 — known domain refusal

Если owner вернул подтверждённый domain refusal до success, UI SHALL показать безопасную причину в исходном snapshot/object context и MAY сохранить те же allowlisted fields/operationId. Он MUST NOT утверждать больше, чем известный owner outcome. Denied/CSRF/unknown snapshot/object выполняются до раскрытия form state и сохраняют действующие login/403/400/404 outcomes.

## 5. A04 — stale calculation

`STALE_CALCULATION` complete-payment SHALL вернуть status 409, `text/html; charset=UTF-8`, `Cache-Control: no-store` и полноценную страницу. Она идентифицирует snapshot, объясняет «этот расчёт устарел; подготовьте новый», содержит GET links к `/pilot/otiz/snapshots/{id}` и существующему `/pilot/otiz/payments`.

409 response MUST NOT calculate, accept, complete, retry или POST что-либо. Before/after snapshots, closures, events, operation receipts, jobs и outbox идентичны. Guest, denied actor и invalid CSRF не получают snapshot amounts/object/form data.

## 6. A05 — in-flight и unknown outcome

Каждая financial form имеет один rendered operationId. Browser enhancement SHALL при первом accepted submit синхронно disable все submit triggers этой формы и не допустить второй POST до navigation/confirmed response. Enhancement MUST NOT заменять submit на `fetch`, автоматически повторять POST или создавать новый UUID.

Если browser достоверно не получил server outcome, UI SHALL считать результат UNKNOWN: не писать «ничего не сохранено», сохранить исходный operationId и allowlisted values для явного решения пользователя, не выполнять retry/polling/новую financial command. Явный повтор с тем же payload/id использует existing exact replay; исправленный payload с тем же id получает existing conflict. Native no-JS submit полагается на тот же server idempotency contract.

Back/forward restoration MAY re-enable controls только после завершённой navigation; operationId не меняется. Double click/in-flight Enter SHALL наблюдаться как один request.

## 7. A06 — retained adjacent behavior

На одних fixtures до/после совпадают owner cents inputs после declared normalization, ordinary canonical discipline, payout complete, replay/conflict, reversal, signed totals, snapshot values, object-wide history rows/links, authorization, CSRF и no-write GET inventory. Никакой HTTP/JS code не рассчитывает available amount и не пишет facts. #258/#250/#260 paths не изменяются.

## 8. Verification и Done

Root-authored focused HTTP/parser/browser tests используют disposable isolated DB/resources, проверяют desktop и narrow, no-JS, long/XSS strings, URL/log absence, object isolation, double submit и simulated unknown outcome без реальных финансовых операций. Planner выбирает lane/reviews. Done требует требуемых independent reviews, focused GREEN, architecture/auth qualification, один exact-source GitHub CI run на PR head и пользовательский before/after. Полный локальный `make test`/`make verify`, рабочий стенд, merge и deploy запрещены.
