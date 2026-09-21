# Текущая цель — стабильный сайдбар и аудит иконок shlz-ui

Поручение владельца 2026-09-21: автономно одним быстрым срезом устранить раскрытие-сворачивание сайдбара при переходах, провести аудит рабочих иконок, максимально заменить точные соответствия публичными exports `shlz-ui` и довести отдельный PR до merge-ready. Владелец заранее подтвердил спецификацию и автономную делегацию spec/tests/implementation/review в рамках этого задания.

Контракт: [YII2-SIDEBAR-STATE-ICONS-001](../../specs/YII2-SIDEBAR-STATE-ICONS-001.md). Lifecycle: [fix-sidebar-state-and-adopt-shlz-icons](../../openspec/changes/fix-sidebar-state-and-adopt-shlz-icons/). Baseline — чистый `origin/main` `e67b566d8958faa0df8f8ebb8c09db3f1e0983ce`; исходный dirty checkout №157 сохранён без изменений.

Root — фактический автор scope/spec/tests и delivery orchestration. Отдельный `gpt-5.6-sol / low` executor реализует production code; независимый `gpt-5.6-sol / low` reviewer решает planner-required review. Локально разрешены только bounded focused checks; полный `make test`/`make verify` запрещён. После review выполняется один exact-source GitHub CI run.

Не входят SPA-router, серверный профиль настройки, изменения доменных фактов/прав/маршрутов, редизайн бренда, создание новых компонентов или иконок в `shlz-ui`, использование грязного sibling checkout и приблизительные замены без публичного semantic match. Merge не выполняется: результат — merge-ready PR.
