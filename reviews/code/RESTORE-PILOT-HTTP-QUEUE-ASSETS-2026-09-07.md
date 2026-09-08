# RESTORE-PILOT-HTTP-QUEUE-ASSETS — независимое ревью

Дата: 7 сентября 2026. База сравнения: `2539559`. Автор extraction — поток
`architecture_diagnosis`; автор этого ревью не изменял проверяемые исходники или
тесты.

## Code verdict

**APPROVED. Блокирующих замечаний нет.**

`MariaDbObjectQueue` переносит прежние SQL, source predicate, status predicates,
escaping поиска, сортировку, fallback плановых дат и pagination metadata без
изменения результата HTTP-клиента. Rapid adapter по-прежнему до чтения очереди
проверяет активного локального пользователя и точный `objects.read`; decoration,
shared status canonicalization и rendering остаются на прежних местах.

Параметры таблиц проверяются до интерполяции. Строка поиска экранирует `\`, `%`
и `_`; page/size проверяются как положительные числа. Обычный HTTP caller
использует прежний размер страницы 50. Фильтр сохраняет шесть канонических
значений, query string пагинации и точный читаемый SHLZ Select markup. Renderer
сохраняет прежние исходные литералы и итоговый HTML без обфускации.

`RapidPilotFileTypeAsset` сохраняет прежнюю ограниченную грамматику имени,
публичный `shlz-ui/packages/icons/dist/file-types`, generic fallback, статус 200,
`image/svg+xml`, точный Content-Length, часовой public cache и `nosniff`.
Router только делегирует совпавший публичный asset route; порядок относительно
общего asset-404 и auth-маршрутов сохранён. GET/HEAD используют те же bytes;
посторонний путь не принимается `matches()`.

## Test verdict

**APPROVED для сфокусированного extraction evidence с указанным ограничением
текущего shared worktree.**

Независимо прошли:

- `rapid-pilot/verify-auth-hot-path.php`;
- `rapid-pilot/verify-object-queue-filters.php`;
- `pilot_route_csp_inventory_001_test.php`;
- `pilot_route_csp_001_test.php`;
- PHP lint всех шести source/verifier файлов;
- `git diff --check`.

Source/render verifier проверяет четыре поля поиска, все шесть status predicates,
pagination metadata, shared label source и точный отрендеренный SHLZ Select.
Auth verifier требует новый public read seam и запрещает process/checklist/
completion SQL в rapid adapter.

В текущем параллельно изменяемом worktree повтор
`local_rbac_objects_route_admission_001_test.php` и
`pilot_shlz_assets_001_test.php` получил общий 503 уже на независимом
`/pilot/assets/shlz.css`. Прямой local-server probe подтвердил, что ответ создаёт
`public/router.php`/production entrypoint до вызова проверяемых rapid-pilot seams;
`public/router.php` не входит в этот diff. Поэтому эти два результата не
приписываются extraction, но и не заявляются здесь как независимо повторённый
GREEN. Авторское evidence фиксирует их предыдущий PASS. Отдельно остаётся
известное старое ожидание `Показано` в `pilot_object_list_001_test.php`; оно не
изменялось этим потоком.

Architecture baseline не изменён. Нового runtime/schema/data поведения,
deployment или внешних действий в проверяемом diff нет.

## Точные SHA-256

```text
869af25af09f3c4fd46a4b8220182c4ab31b17f285ea63283007c5abadca83d8  app/PilotHttp/MariaDbObjectQueue.php
5b6a7f4e8679bb0dfe1a8d837da21d0393c3cedc4da659710f835eb361326a7b  rapid-pilot/ObjectQueue.php
eb6ae0de1f0a24a5a749629ba81cc3ae0eea542b9fc0b80fa9411e6ce562bdc8  rapid-pilot/FileTypeAsset.php
fe8ec6008c6e2cb4a8fe4f480469adf276a66f206af73f1c9d134412a44d5389  rapid-pilot/router.php
2d0e532c3e2119e2d1a61e04453e29ceaa30d1aa1e57383c87475ebbbc17ed9d  rapid-pilot/verify-auth-hot-path.php
0bd84d1ddf3b77882736876eb0de3a6f9d677f3ffcd5ee7019c1585092a8826d  rapid-pilot/verify-object-queue-filters.php
c0ba9a0dcab51b132727858015aeda28fbb89fd82903974c9bf257ddf29addbc  docs/operations/http-queue-assets-ownership-2026-09-07.md
```

Все проверенные файлы имеют режим 0644.
