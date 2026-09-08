# Выравнивание template и original fixtures — 7 сентября 2026

Изменения ограничены проверочными fixtures и их тестами. Runtime pilot, стенд и
реальные данные не изменялись.

## PDF-шаблон и плановые даты

RED `template_generation_boundaries_001_test.php` завершался с `exit 1`: случаи
`missing_object_date` и `malformed_adjusted` ожидали отказ без PDF, хотя актуальный
контракт допускает отсутствующую или плохо заполненную плановую дату. Оба случая
теперь проверяют успешную генерацию настоящего PDF, точную дату шаблона, один
metadata-only audit и отсутствие сохранённого файла шаблона. Для отсутствующей
даты ожидается `plannedFinishDate=null`; некорректная adjusted date использует
действующий fallback `2026-12-20`. Все прежние security/domain refusals сохранены.

GREEN завершился с `exit 0`; оба случая выдали `PASS`.

## Идемпотентность original verification fixture

RED `assignment_order_original_database_setup_001_test.php` завершался с
`exit 255`: обязательный повтор `seedExampleA` повторно вставлял уже существующий
primary key роли 5301 и permission `assignment_order.original.upload`.

Две локальные role permissions перенесены внутрь той же транзакционной seed/clean
последовательности, которой уже принадлежат остальные строки Example A. Внешние
post-seed и pre-clean запросы удалены. Тест дополнен точным oracle двух permission
rows; существующая полная проверка состояния подтверждает, что повторный seed —
no-op, cleanup удаляет весь fixture, а drift не переписывается.

GREEN завершился с `exit 0` и
`ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_001_OK`.

Сфокусированные команды использовали уникальные одноразовые базы на тестовом
MariaDB-контуре порта 23306. Полные выводы с PDF перенаправлялись в приватные
логи вне репозитория. Все три изменённых исходника имеют режим 0644;
`git diff --check` и PHP lint прошли.
