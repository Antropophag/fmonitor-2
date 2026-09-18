## 1. Rebind slice

- [x] 1.1 Обновить stable spec, verification input и delivery goal до object-card scope; planner MUST выдать complete obligations без duplicate mappings.
- [x] 1.2 Пересобрать root-authored RED tests для object-card normal/edge/responsive/JS-off/domain invariants; Gate 3 отложен явным owner exception 2026-09-18 и MUST NOT отмечаться APPROVED.

## 2. Implement

- [x] 2.1 Executor реализует shared composition/motion tokens и проверяет architecture boundary.
- [x] 2.2 Executor пересобирает object-card hierarchy/actions/regions без изменения routes, payloads и facts; object-card browser/HTTP tests MUST стать GREEN.

## 3. Verify and review

- [x] 3.1 Выполнить один desktop/mobile screenshot pass, одну correction batch, один confirmation pass и Impeccable detector.
- [x] 3.2 Выполнить planner-selected focused checks и architecture-check, не запуская local full suite.
- [ ] 3.3 Получить independent Gate 5 `APPROVED` и one exact-source CI GREEN; UNKNOWN не считается approval.

## 4. Done

- [ ] 4.1 Object card соответствует contract; follow-up changes `refresh-otiz-shlz-ui` и `refresh-active-yii2-shlz-ui` созданы перед архивацией этого slice.
