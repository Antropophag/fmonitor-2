# Design

Очередь читает `first_shipment_date` и `full_shipment_date` из существующей current projection по object id. View выбирает ровно одно состояние: full, иначе first, иначе none. Нативный `<details>` даёт keyboard/touch disclosure без нового JavaScript и не превращает строку в mutation owner. Публичные exports `shlz-ui` `delivery-box.svg` (SHA-256 `b4517454d78cb79f5063022a65c7d685de5035b1baa204bc442fc181eb5afa04`) и `delivery-4.svg` (SHA-256 `e79f74ceb6b1b7c596a22c1be3d9072005203f3681181fa193d2f56a8ab81583`) визуально различают промежуточную коробку и полную доставку. На мобильном индикатор остаётся рядом с идентичностью объекта.

Schema, persistence, auth, concurrency, backup/restore и deployment не меняются. Ошибочное/устаревшее значение исправляется только владельцем ERP facts; очередь отражает current projection после refresh. Недоступность таблицы остаётся общей runtime failure очереди и не превращается в положительный статус.

По отдельному указанию владельца browser focused profile материализует публичный `shlz-ui` behaviors bundle, устанавливает системные Chromium dependencies, публикует `FMONITOR_TEST_PLAYWRIGHT_MODULE` и предоставляет test-owned writable `.test-artifacts`. Это восстанавливает существующие browser/schema obligations; product runtime и production image не меняются.
