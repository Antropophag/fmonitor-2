# Design

Очередь читает `first_shipment_date` и `full_shipment_date` из существующей current projection по object id. View выбирает ровно одно состояние в узкой icon-only колонке: full, иначе partial, иначе unknown. Public `shlz-ui` `delivery-box.svg` обозначает любое подтверждённое событие отгрузки без дополнительных маркеров; unknown остаётся визуально пустым, а `delivery-4.svg`, `info-circle.svg` и `checkmark.svg` не используются. Статус и дата доступны через `aria-label`/`title`. На мобильном иконка позиционируется рядом со стрелкой, не создавая отдельной строки и не увеличивая карточку.

Schema, persistence, auth, concurrency, backup/restore и deployment не меняются. Ошибочное/устаревшее значение исправляется только владельцем ERP facts; очередь отражает current projection после refresh. Недоступность таблицы остаётся общей runtime failure очереди и не превращается в положительный статус.

По отдельному указанию владельца browser focused profile материализует публичный `shlz-ui` behaviors bundle, устанавливает системные Chromium dependencies, публикует `FMONITOR_TEST_PLAYWRIGHT_MODULE` и предоставляет test-owned writable `.test-artifacts`. Это восстанавливает существующие browser/schema obligations; product runtime и production image не меняются.
