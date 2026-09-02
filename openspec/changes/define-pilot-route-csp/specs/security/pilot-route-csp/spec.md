## Purpose

Ограничивает выполнение JavaScript точными успешными pilot HTML routes, которым нужны внешние same-origin scripts, сохраняя строгую fail-closed CSP для остальных ответов.

## ADDED Requirements

### Requirement: Базовая строгая CSP
Каждый pilot HTTP response, кроме отдельной политики Service Worker script asset, SHALL содержать базовую политику `default-src 'none'; style-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'`. Политика SHALL NOT содержать `unsafe-inline`, `unsafe-eval`, nonce, script hash, third-party origin или wildcard source.

#### Scenario: Ошибка не получает право выполнять script
- **WHEN** любой pilot route возвращает `4xx` или `5xx`, включая ошибку script-enabled route
- **THEN** response содержит точную базовую строгую CSP без `script-src`, body не определяет исключение из неё, а существующие status, redaction, cache и security headers не изменяются

#### Scenario: Redirect не получает право выполнять script
- **WHEN** pilot route возвращает `3xx`
- **THEN** response содержит базовую строгую CSP без `script-src`, сохраняет целевой `Location`, пустое/существующее тело и `Cache-Control: no-store`

#### Scenario: Asset не получает право выполнять вложенные script
- **WHEN** успешный или ошибочный response обслуживает CSS, JavaScript, SVG, font, PDF или другой non-HTML asset
- **THEN** его CSP не содержит `script-src`, а Content-Type, bytes, Content-Length, caching и asset-specific security headers сохраняются

### Requirement: Script разрешён только allowlisted успешному HTML
`script-src 'self'` SHALL добавляться только к успешному `2xx` HTML response, если его method/path входят в allowlist и итоговый body содержит один или несколько внешних same-origin `<script src="/pilot/...">`. Решение SHALL зависеть от нормализованного route pattern, method, итогового status и итогового media type, а не от пользовательских данных или произвольного поиска текста в body.

Allowlist для pilot release SHALL охватывать следующие `GET`/`HEAD` route
families при successful HTML result и единственный successful scripted POST
result `POST /pilot/login`:

- `/pilot/login`;
- `/pilot/`;
- `/pilot/objects`;
- `/pilot/objects/{positive-id}`;
- `/pilot/objects/{positive-id}/assignment-order/prepare`;
- `/pilot/objects/{positive-id}/checklist`;
- `/pilot/construction-control`;
- `/pilot/construction-control/objects/{positive-id}/checklist`;
- `/pilot/installers`;
- `/pilot/admin/users` и `/pilot/admin/roles`;
- `/pilot/calendar` и `/pilot/calendar/`;
- HTML read routes `/pilot/otiz`, `/pilot/otiz/`, `/pilot/otiz/objects`, `/pilot/otiz/payments`, `/pilot/otiz/history`, `/pilot/otiz/reconciliation`, `/pilot/otiz/reconciliation/quarantine`, `/pilot/otiz/active-baselines`, `/pilot/otiz/historical-replay`, `/pilot/otiz/snapshots/{positive-id}`.

Любой новый route SHALL оставаться без `script-src`, пока отдельное reviewed изменение одновременно не добавит его в allowlist и не докажет наличие необходимого external same-origin script.

`POST /pilot/login` SHALL получать scripted policy только когда final result —
existing `200 text/html` representation с `/pilot/assets/preloader.js` (invalid
credentials или промежуточный login step). Redirect/error/non-HTML outcomes
MUST оставаться на base policy. Slice SHALL NOT менять login/session semantics
или вводить POST/Redirect/GET.

#### Scenario: Scripted login POST сохраняет существующее поведение
- **WHEN** `POST /pilot/login` возвращает existing `200 text/html` с external
  `/pilot/assets/preloader.js`
- **THEN** response получает `SCRIPT_HTML_CSP`, а status/body/login/session
  behavior не меняются
- **AND** redirect/error login result получает base policy

#### Scenario: Script-enabled GET получает ограниченное разрешение
- **WHEN** `GET /pilot/objects` успешно возвращает `200 text/html` с `/pilot/assets/navigation.js` и route-specific external scripts
- **THEN** CSP равна базовой политике с единственным дополнительным directive `script-src 'self'`, body не содержит inline script/event handler, third-party script URL или executable string-evaluation contract

#### Scenario: HEAD классифицируется как соответствующий GET
- **WHEN** `HEAD` к allowlisted HTML route имел бы успешный `2xx text/html` GET representation
- **THEN** response получает ту же CSP и Content-Length, что GET representation, но возвращает пустой body

#### Scenario: Script-free успешный HTML остаётся строгим
- **WHEN** `GET /pilot/activate` успешно возвращает activation HTML без внешнего script
- **THEN** response содержит базовую CSP без `script-src` и body не содержит script element или inline event handler

#### Scenario: Неизвестный или похожий path не расширяет allowlist
- **WHEN** запрошен `/pilot/objects/0`, `/pilot/objects/4512/unknown`, unknown OTIZ path или иной path, не совпадающий с route pattern целиком
- **THEN** он получает свой нормальный rejected result и базовую CSP без `script-src`

### Requirement: Checklist сохраняет только необходимые дополнительные источники
Успешные allowlisted checklist HTML responses SHALL использовать базовую CSP плюс `script-src 'self'; worker-src 'self'; connect-src 'self'` и `img-src 'self' blob:` вместо базового `img-src`. Другие HTML routes SHALL NOT наследовать `worker-src`, `connect-src` или `blob:` только потому, что используют JavaScript.

#### Scenario: Checklist online/offline resources разрешены узко
- **WHEN** `GET /pilot/objects/4512/checklist` либо `GET /pilot/construction-control/objects/4512/checklist` возвращает `200 text/html`
- **THEN** CSP разрешает same-origin checklist script, worker и connect, а также same-origin/blob images, не разрешая inline/eval/third-party sources

#### Scenario: Checklist error остаётся базовым
- **WHEN** тот же checklist route возвращает `401`, `403`, `404`, `409` или `503`
- **THEN** CSP равна базовой строгой политике без `script-src`, `worker-src`, `connect-src` и `blob:`

### Requirement: Inline CompletionFlow поведение устранено без семантического изменения
Итоговый checklist HTML SHALL не содержать inline `<script>`, inline event attributes или иной executable inline fragment. Ограничение отображаемого completion progress до `85` до документарного закрытия и до `100` после него SHALL выполняться через уже разрешённый внешний same-origin checklist asset либо серверный rendering; state-changing completion behavior, факты и проценты SHALL не изменяться этим slice.

#### Scenario: Незавершённый checklist не содержит inline script
- **WHEN** открытый объект с `data-progress-cap="85"` возвращает успешный checklist HTML
- **THEN** body содержит внешний checklist script, не содержит inline script, а наблюдаемое значение progress не может отобразиться выше `85`

#### Scenario: Завершённый checklist сохраняет 100 процентов
- **WHEN** объект имеет принятые ПТО и декларацию и checklist содержит `data-progress-cap="100"`
- **THEN** body остаётся без inline script и внешний behavior сохраняет отображаемый итог `100`

### Requirement: CSP не меняет authorization, аудит и состояние
Определение CSP SHALL происходить после обычной маршрутизации и SHALL не предоставлять доступ, не менять body/status бизнес-ответа, не записывать domain/audit facts и не зависеть от конкурентного состояния. Повторные `GET`/`HEAD` при одинаковом representation SHALL возвращать одну и ту же CSP.

#### Scenario: Запрещённый actor не получает расширенную политику
- **WHEN** actor без требуемого permission запрашивает allowlisted route
- **THEN** обычный authorization contract возвращает `401` или `403`, CSP остаётся базовой без `script-src`, а domain и audit history не меняются

#### Scenario: Повтор безопасного чтения детерминирован
- **WHEN** один и тот же успешный representation запрошен повторно без изменения route/configuration
- **THEN** CSP byte-identical, а запросы не создают persistence facts и не требуют concurrency coordination
