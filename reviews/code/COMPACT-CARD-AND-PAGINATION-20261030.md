# Компактная карточка и навигация — независимое code review

Reviewer: `/root/otiz_ui_review`, не автор кода или тестов.
Verdict: **APPROVED**, 2026-10-30. Base: `dbc5de11`.

## Проверенный результат

- Комментарий находится под паспортом объекта, свёрнут по умолчанию, раскрывается
  по `#object-comment` и при ошибке сохраняет введённое значение и открытое состояние.
- Справка о сроке ОТИЗ показана внутри планового завершения, использует прежние
  permission/evidence reads и не подменяет плановую дату.
- Навигация страниц ОТИЗ находится слева, выбор размера страницы справа; query и
  фильтры сохраняются.
- Число монтажников удалено из общего списка объектов и добавлено в экономику ОТИЗ
  со ссылкой на вкладку команды.
- Счётчик использует один канонический batched read: последняя применённая native
  композиция приоритетна; registered fallback берёт последний приказ и только
  действующие `assign`/`retain`. Повреждённая native композиция fail-closed.
- Финансовые формулы, факты, схема и write seams не изменены.

Focused evidence: object-comment desktop/narrow browser PASS; certificate HTTP PASS;
OTIZ browser/navigation PASS; installer-count HTTP/read PASS; `git diff --check` and
PHP syntax PASS. Full local suite/CI не запускались по срочному owner-authorized циклу.

## Exact runtime binding

- `05008e630b3f3c5b9f51be528949459942d2a84550833ea77382c07cbcb9b717` `app/InstallationProcess/MariaDbYiiObjectQueueProjection.php`
- `344f8327b4bd123991c061a19b55667b19ede2ee4ea0362461b9b5553d1762ed` `app/Otiz/MariaDbOtizPortfolioEconomy.php`
- `7efcf4e100b55e269840ab57b0db0226ccc1e0b9959d98a8834636e1bc9fd0e1` `app/YiiRuntime/Assets/pilot.css`
- `5d915b7651dea76dd5f1516c10e3cad2b3fa068d0a6761f15f8356dde67ecbb4` `app/YiiRuntime/Assets/preopening.css`
- `9d2cdd1c3d825f7082627a9396f59076a86c2ef1b6d842398a271d54bea15548` `app/YiiRuntime/Assets/preopening.js`
- `f58e4abf18e8d20c1229f2d961943369ec1eb2f01cdd894e9cbfd375b69aa3e2` `app/YiiRuntime/Views/_otiz-v2-group-table.php`
- `14ce75bf04233ff480a704b2279f5d6915ef5af2f45f61169bb7658416035b85` `app/YiiRuntime/Views/object-card.php`
- `732eef575655b7d83d5493f47230d22e2ce876ed5ed36448a8bd9021bbab6653` `app/YiiRuntime/Views/objects.php`
- `51f8f15140c5fb45c1ed44ad6dfe2957d2a0616b344d8fb97ec10a94b76cd4eb` `app/YiiRuntime/Views/otiz-economy-v2.php`
