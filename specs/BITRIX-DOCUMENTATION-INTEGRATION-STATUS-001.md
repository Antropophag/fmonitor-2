# BITRIX-DOCUMENTATION-INTEGRATION-STATUS-001 — состояние синхронизации технической документации Битрикс

## Простыми словами

Администратор видит, ждёт ли документная синхронизация запуска, действительно ли началась последняя попытка, чем она известным образом завершилась и когда был последний отдельно подтверждённый успех. Экран читает только сохранённые факты очереди, ничего не запускает и не выдаёт старый успех или отсутствие данных за текущее благополучие.

## Actor и public seam

Actor: активный локальный пользователь с `access.administer`.

Public seam: `GET|HEAD /pilot/admin/integrations` и server-rendered HTML. Все требования `openspec/changes/show-bitrix-documentation-status/specs/admin/bitrix-documentation-status/spec.md` нормативны и включены сюда ссылкой; при расхождении этот стабильный контракт и issue #267 имеют приоритет.

## Acceptance matrix

- A1: source строго `bitrix.order-document-links.sync`; enqueue time не становится start time, отсутствие runs не становится disabled.
- A2: success A + later failed/retry B показывает B как latest attempt и A как separate last success; новый success объединяет обе роли без подмены timestamps.
- A3: queue различает queued-before-claim, running only with credible lease, retry wait, terminal dead, completed и unknown contradiction.
- A4: каждый claimed event — отдельная attempt row; event outcomes переживают очистку lease и failure fields; fixed server pagination сохраняет независимые parameters.
- A5: last success требует completed event и valid non-negative integer `published`; подпись — опубликованные связи «заказ — папка».
- A6: missing/corrupt/unknown facts не становятся success/zero/exact cause; raw payload/result/details, tokens, URLs, credentials и exceptions не публикуются.
- A7: canonical admin access, read-only GET/HEAD, no writer locks/network/enqueue/retry и неизменность durable facts.
- A8: реальный экран имеет ясную hierarchy на desktop/narrow, contained tables и keyboard-operable working pagination без общих CSS/JS changes.

## Persistence, replay и adjacent flows

Slice не владеет фактами очереди и не меняет их. Повторный или параллельный GET может видеть только committed состояния и не создаёт audit/history. Jobs handlers/registry/scheduler, document retrieval/publication, credentials, schema, router, общие assets, deployment, #251 целиком и соседние flows не меняются.
