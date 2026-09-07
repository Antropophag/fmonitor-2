# Original upload HTTP — scoped GREEN

Gate1 v0.3 APPROVED; Gate3 v2 APPROVED, затем два узких test-input исправления
с самостоятельными approvals v3/v4. PDF fixture теперь literal из normative
original spec (327 bytes, SHA4028af…), stale correction изменяет documentDate,
поскольку один новый requestId не отменяет semantic replay. Expectations сохранены.
Повтор RED v3/v4 выполнен в отдельном checkout051eaf0 без production original HTTP.

## Implementation

Native `AssignmentOrderOriginalSubmissionQuery` владеет read-only operator context;
registered immutable selection и original leaf/backing сверяются в snapshot.
HTTP только проверяет transport/CSRF, вызывает read context и approved
`createForSelections` с настоящим fresh terminal reader. Raw PDF ограничен20MiB,
метаданные canonical UTF8 JSON/base64, native result сохраняет exact11 fields.
Форма initial/correction использует shlz и прежнюю оболочку. Router представляет
HEAD формы как GET только для вычисления decorated representation/Content-Length,
не выводит response body; canonical entrypoint отдельно сохраняет native HEAD.

Same-origin JS передаёт File, сохраняет intent при retry, меняет его при изменении
ввода/4xx. Подготовка состава, загрузка и opening остаются отдельными действиями.
Нет новых domain writers, DDL, runtime grants, template storage или remote mutations.

## Verification evidence

External root:
`/Users/antropophag/.local/state/fmonitor2-verification/original-http-20260907`.

`final-green-manifest.json` фиксирует6 focused suites, их SHA/commands/logs,
все exit0: admission, bounds/exact20MiB, flow/replay/correction/historical order,
permissions, prefill/manager, Node client. `selection_http_*-regression-green.log`:
три существующих HTTP regression suites PASS. PHP lint, visual/focus и Impeccable
выполнены; финальные logs `visual-final.log`, `focus-final.log`, `impeccable-final.log`.
Architecture evidence `architecture-final.log` необходимо проверить до интеграции.

Chrome CUA actual native login в isolated fictional fixture64247 → selection link
→ direct-upload form, today2026-09-07 → File picker → date2026-09-01 → confirmation
→ keyboard Tab/Return → revision1 current original date2026-09-01. Нейтральный
keyboard focus inspected. Коррекция через HTTP/client GREEN, но browser correction
и final layout ещё НЕ завершены: native file picker CUA остался в GoToWindow,
Return/Escape не возвращают основное окно; сохранение user credentials отклонено.
Browser fixture process пока активен, final snapshot/cleanup pending.

Это scoped GREEN до независимого Gate5. Change/goal не complete, не archived.
General original history/download, application/opening, users503, real Bitrix
и full VERIFY остаются на launch critical path. Preview8092 не переключался.
