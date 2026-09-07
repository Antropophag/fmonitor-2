# Открытие сформированного шаблона в новой вкладке — 2026-09-07

## Решение владельца и scope

Владелец сообщил, что при действии «Сформировать шаблон» открывается пустая
вкладка и начинается скачивание, и потребовал вместо этого открыть сформированный
документ. Для manual pilot это прямое решение заменяет прежний HTTP contract
`Content-Disposition: attachment` только для успешного ответа optional template
generation.

Новый observable contract: существующая форма с `target="_blank"` открывает
новую вкладку, а успешный POST template route отвечает реальным PDF с
`Content-Disposition: inline`, чтобы браузер показал документ своим PDF viewer.
RFC5987 filename, ASCII fallback, media type, exact Content-Length, CSP/security
headers и `Cache-Control: no-store` сохраняются.

Не менялись selection, template renderer, generation audit/date, stateless
отсутствие сохранённого template file/version, original upload/history/download,
document facts или другие artifact endpoints. Реальные объекты и объект №966 не
использовались; stand/runtime/data не изменялись.

## RED и минимальная реализация

В `selection_http_flow_001_test.php` добавлены два public assertions: template
form сохраняет exact action и `target="_blank"`; успешный template response
начинается с `inline;` и сохраняет RFC5987 filename. До production change focused
test достиг нового assertion и завершился ожидаемым RED:

```text
template opens inline instead of triggering a download
Expected: true
Actual: false
```

В `FreshOrderHttpHandler.php` изменено одно значение только в success-ветви
template response: `attachment` → `inline`. View менять не потребовалось.

## Focused GREEN

```text
selection_http_flow_001_test.php
PASS selection/replay/replace_pending/PDF/accepted-root new_order native HTTP

selection_http_admission_001_test.php
PASS: все admission/CSRF/media/method/fresh-route cases

selection_http_failures_001_test.php
PASS domain mapping/retry identity/read failure/template failure

template_generation_001_test.php
PASS today repeat failure and direct original
PASS production constructor uses native renderer and current date

PHP lint обоих изменённых файлов: PASS
git diff --check: PASS
Impeccable detector: []
tools/architecture/check: ARCHITECTURE CHECK PASSED (7 rules)
```

`OriginalHistoryHttpHandler.php` по-прежнему возвращает
`Content-Disposition: attachment; filename="assignment-order-original.pdf"`;
этот файл и original endpoint не изменялись.

## Headless browser evidence

Private synthetic fixture находится в
`~/.local/state/fmonitor2/manual-pilot-20260907/runtime/`:
`template-inline-browser-fixture.php`, `template-inline-browser.cjs` и
`template-inline-browser-result.json`.

Полный Google Chrome запущен через Playwright в headless mode. Browser выполнил
обычный login, создал composition только в уникальной synthetic DB и нажал
«Сформировать шаблон». Результат:

```text
popupUrl=/pilot/objects/4512/assignment-orders/81/template
status=200
contentType=application/pdf
contentDisposition=inline; filename="assignment-order.pdf"; filename*=UTF-8''...
downloads=0
errors=[]
result=PASS
```

Bundled Playwright Chromium headless shell не имеет PDF viewer и даже при
`inline` создаёт download event; поэтому он не является корректным oracle для
отображения PDF. Повтор с установленным full Google Chrome headless подтвердил
viewer-навигацию без download event. HTTP test независимо подтвердил magic
`%PDF-`, exact length, содержимое документа, audit и отсутствие сохранённого PDF.
Оба synthetic fixture run очистили свои базы через штатный `close()`.

Exact SHA-256 перед независимым review:

```text
04579710161990afdc5626847d71c92defbbbfa5ea623680e0bf0892d4ec7ba0  app/PilotHttp/FreshOrderHttpHandler.php
70c90aeb2b79a9dda02d310ae09f3b1bccf3bfd7e8343a1e73ac6ea76bee8eed  tests/AssignmentOrderComposition/selection_http_flow_001_test.php
dc21cc213355a857a6431fa963972006faff347d704e83826e6c59cd1fb0b5ba  template-inline-browser-fixture.php
35b376debfab016028ad2f7ab1feaad28818165f08d27dd853abed4e62013e98  template-inline-browser.cjs
119ed6fa8829ec6bbc985d0185f9a691e6426dca464f69ad9629b4c6cb281f0f  template-inline-browser-result.json
```

Это focused manual-pilot evidence, а не deployment, full `make verify`,
production readiness или независимое code-review approval.
