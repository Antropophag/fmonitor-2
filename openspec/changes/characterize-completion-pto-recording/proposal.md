## Why

Текущий rapid-pilot изменяет состояние при фиксации даты Акта ПТО, но существующий verifier проверяет только проекции с заранее вставленными фактами и не защищает реальный HTTP mutation path. Перед переносом ownership нужно воспроизводимо зафиксировать этот oracle отдельно от неутверждённых правил `85/15`, авторизации и документарного закрытия.

## What Changes

- Добавляется `PILOT_ONLY` characterization-срез `CHARACTERIZE-COMPLETION-PTO-RECORDING-001` для пользователя rapid-pilot, фиксирующего дату Акта ПТО через существующий HTTP route.
- Characterization охватывает наблюдаемые HTTP-ответы, единственный сохранённый `pto_act`, порядок отказов, replay, конкурентные запросы, слишком широкую текущую авторизацию, deactivated-session redirect и request-triggered DDL.
- Время `recorded_at` проверяется относительно живых границ часов `Europe/Moscow`, без добавления clock seam в production pilot.
- Результат становится source oracle для будущего целевого seam-кандидата `InstallationCompletion::recordPtoAct`; сам seam в этом change не утверждается и не реализуется.
- `NEEDS_GRILL`: target-срезы `COMPLETION-PTO-001`, `COMPLETION-DECLARATION-001` и `canonicalize-installation-completion-schema` остаются заблокированы GRILL-001 вопросами 2–3.

Явные non-goals: фиксация декларации; утверждение `85% + документы = 100%`; целевая authorization policy; требования к файлу/основанию Акта ПТО; correction/supersession; reconciliation с assignment-order/OTIZ; перенос DDL в canonical migration; production implementation.

## Capabilities

### New Capabilities

- `verification/completion-pto-recording-characterization`: воспроизводимая проверка фактического поведения pilot при первой записи `pto_act` через публичный HTTP seam.

### Modified Capabilities

Нет.

## Impact

- Oracle: `rapid-pilot/router.php`, `rapid-pilot/LocalAuth.php`, `rapid-pilot/CompletionFlow.php`.
- Existing verifier gap: `rapid-pilot/verify-completion-flow.php`.
- Новая тестовая поверхность должна использовать изолированный DB prefix и реальный pilot HTTP route, не production/legacy данные.
- Planning evidence: `docs/operations/completion-pto-recording-behavior-evidence.md`.
- Runtime DDL и broad authorization намеренно характеризуются как риски, а не принимаются как target architecture.
