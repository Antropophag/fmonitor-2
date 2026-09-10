## Why
Владелец поручил автоматизировать подготовку и измерение delivery без изменения ролей. Текущий main содержит устаревший статус PR88, runner передаёт полные логи и не сохраняет provenance.
## What Changes
Ограниченный CLI harness поверх change-verification, review-source и verification runner: локальные измерения, компактное исполнение, фактическое состояние, роль/контекст/review delta, поддерживаемые Codex hooks, узкие consumer obligations.
## Capabilities
### New Capabilities
- `delivery-harness`: автоматическое подключение существующего процесса.
### Modified Capabilities
- `change-verification`: consumer obligations, явное обновление устаревшего плана.
## Impact
Только tools/delivery, verification, policy и указатели. Нормативный контракт: specs/DELIVERY-HARNESS-001.md. #76, продукт, стенд, merge/deployment вне scope. Root пишет spec/tests; отдельные sol/low исполнители и независимые reviewers.
