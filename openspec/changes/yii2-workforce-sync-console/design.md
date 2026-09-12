## Context

См. `proposal.md`. Scheduled `workforce.sync` уже выполняется через поставленный Yii2 jobs console, но ручной production entrypoint самостоятельно читает environment, создаёт Bitrix client и mysqli, затем вызывает каноническую синхронизацию. Это оставляет две composition boundaries для одной операции и мешает финальному runtime cutover №76.

## Goals / Non-Goals

**Goals:**

- Общая composition для ручного Yii2 command, scheduled job handler и совместимого alias.
- Один mysqli lifecycle и существующий persistence/application owner для каждой invocation.
- Полная повторная проверка delivery/history/canonical-runner oracle через новый transport.
- Закрытый package/load contract без OTIZ и `rapid-pilot`.

**Non-Goals:**

- Не менять Bitrix protocol, нормализацию, кадровые правила, schema, schedule или retry policy jobs.
- Не менять web routes, stand, runtime recovery и OTIZ.
- Не удалять alias до доказательства отсутствия внешних callers.

## Decisions

1. Общая adapter/composition размещается в Yii runtime boundary и принимает только проверенную environment configuration. Она создаёт существующий Bitrix delivery client, владеет mysqli resource, вызывает `MariaDbWorkforceSynchronization`, затем гарантированно закрывает ресурс. Единственность composition проверяется по достижимому source/load set, а release — обязательным `finally`; точное число внутренних driver handles не является новым product behavior. Альтернатива — использовать Yii DB — отклонена: она смешала бы DB APIs внутри уже атомарного mysqli owner без продуктовой ценности.
2. Yii controller владеет только строгим transport grammar и mapping закрытого результата. SQL и workforce decisions в controller запрещены. Альтернатива — перенести owner в controller — нарушает один публичный application seam.
3. Scheduled jobs composition переиспользует тот же environment/client/connection factory, но сохраняет свой job outcome mapping, lease/retry и durable queue behavior. Общая часть заканчивается до transport-specific outcome. Это предотвращает обратную зависимость manual command от jobs.
4. `bin/fmonitor2-sync-workforce.php` становится launcher к точному Yii route. Behavioral tests сравнивают direct/alias success и failures; lexical/package witness запрещает вторую environment/client/connection composition.
5. Gate 2 запускает существующие независимые delivery/history/synchronization oracle неизменёнными и добавляет новый subprocess transport для success/repeat/parity. Ожидаемые counts и durable facts не выводятся из новой реализации. Architecture inventory регистрирует все новые tests; local verification остаётся bounded согласно owner decision 2026-09-11.

## Risks / Trade-offs

- [Risk] Общая composition случайно изменит job retry classification → Mitigation: сохранить job-specific mapping и прогнать jobs workforce/retry oracle.
- [Risk] Yii bootstrap раскроет exception или environment values → Mitigation: закрытая canary matrix stdout/stderr/log и неизвестный Throwable.
- [Risk] Alias сохранит самостоятельную composition → Mitigation: source ownership witness плюс direct/alias DB parity.
- [Risk] Пересечение с параллельной OTIZ-веткой → Mitigation: planned paths ограничены console/workforce/tests/verification/docs; `app/Otiz`, OTIZ controllers/specs/tests не изменяются.

## Migration Plan

1. Root фиксирует нормативный spec, verification input и intended RED, затем готовит harness package от актуального main.
2. Независимый reviewer решает Gate 3; executor реализует только утверждённый candidate.
3. Выполняются bounded focused suites, независимый Gate 5 и exact-source PR/один GitHub full CI.
4. Stand/deployment не переключается. Rollback — возврат repository-owned callers к сохранённому alias; workforce facts не откатываются и не переписываются.
