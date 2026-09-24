## Context

Карточка уже владеет командами и полной историей completion. Реестру нужна bounded SQL projection без N+1 полного reader.

## Decisions

- Новый `MariaDbYiiCompletionRegister` выполняет server-side query/count/pagination и валидирует цепочки версий в SQL/result assembly.
- Effective details выбираются как последнее ненулевое correction value, effective date — из текущего leaf.
- Controller владеет только GET/HEAD, канонизирует query string и проверяет `objects.read` через существующую identity seam.
- View использует существующий shell/shlz classes; локальный общий asset не требуется.
- Никакая ошибка adapter не преобразуется в пустой dataset; controller возвращает штатный 503.

## Risks / Trade-offs

- Без новой схемы сложнее индексировать большие выборки; первый срез сохраняет bounded pagination и не материализует историю в PHP. Индексы — только отдельный measured follow-up.
- SQL обязан проверять последовательность correction version/linkage; простой MAX(id) запрещён.
