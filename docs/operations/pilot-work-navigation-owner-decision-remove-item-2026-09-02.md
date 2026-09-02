# Owner decision — remove «Моя работа» navigation item, 2026-09-02

Владелец продукта решил:

> Да вообще выкинуть пункт моя работа

## Interpretation

- Navigation item «Моя работа» удаляется из shared pilot navigation на всех
  успешных pilot HTML screens, включая exact `/pilot/`.
- Рабочая очередь и route `/pilot/` не удаляются и не перенаправляются: решение
  относится только к navigation presentation.
- Authorization, route admission, business state, queue filtering, redirects и
  error responses не меняются.
- Это решение supersedes прежнее направление
  `restore-pilot-work-navigation` и запрос на одобрение восстановления ссылки.

## Gate boundary

Решение разрешает coherent planning amendment и fresh independent Gate 1
review. Оно не одобряет будущие exact hashes, tests, production code, GREEN,
code review или Done.
