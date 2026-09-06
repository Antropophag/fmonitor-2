# Повторное формирование шаблона — owner approval

Дата2026-09-06. Владелец продукта: пользователь текущей autonomous session.
На вопрос о повторном «Сформировать шаблон» (вернуть прежний PDF/дату либо
создать новую версию с сегодняшней датой при прежних составе и распоряжении)
owner ответил: **«новый с сегодняшней датой»**.

## Approved behavior

- Новое пользовательское формирование создаёт новую immutable версию PDF
  с сегодняшней датой по принятому календарю приложения Europe/Moscow.
- Прежние PDF, даты и metadata сохраняются append-only и доступны в истории.
- Повторное формирование не создаёт новый выбор состава, order identity,
  order version или selection revision и не меняет состав.
- Технический replay того же request не является новым пользовательским
  формированием: exact idempotency/response-loss contract закрепляется Gate1.

Это новое явно принятое product decision для optional-render package, без
повторного запроса approval. Оно не отменяет delivery gates, не включает renderer
в production, не меняет original document date и не открывает работы.
Existing ARTIFACT-STORE-001 исключал rerender из своего scope; новая возможность
доставляется отдельным slice, сохраняя его immutable content-store contract.
