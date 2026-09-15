# Текущая цель — №15, ссылки на техническую документацию Битрикс

Поручение владельца 2026-09-14: реализовать [№15](https://github.com/Antropophag/fmonitor-2/issues/15) от актуального `main` `b2907355` до PR-ready. Scope: read-only исследование legacy-контракта, native application/integration owner, exact-сопоставление только по номеру заказа `fm_maintable.zavnumber`, безопасная актуализация и отображение ссылок в карточке объекта.

Не входят №12/№30, общий redesign интеграций, object identity refactor, rapid-pilot cleanup, construction-control/checklist owner №40 и OTIZ/calculation №66. Общие verification/harness files не менять без доказанной необходимости. Копирование файлов и merge/deploy не входят.

Контракт: [BITRIX-ORDER-DOCUMENT-LINKS-001](../../specs/BITRIX-ORDER-DOCUMENT-LINKS-001.md). Lifecycle: [bitrix-order-document-links](../../openspec/changes/bitrix-order-document-links/). Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые gpt-5.6-sol/low reviewers решают требуемые planner-ом gates. Full `make test`/`make verify` локально запрещён; bounded checks и один exact-source CI run.

Предыдущий указатель №31 сохранён в [истории](current-delivery-goal-history-issue-31-2026-09-14.md). Фактические source/PR/CI/lane получать через harness state и активный package. Live Bitrix access/auth остаётся `UNKNOWN`, пока не проверен отдельно.

Delivery record: [issue-15-bitrix-order-document-links-delivery.md](issue-15-bitrix-order-document-links-delivery.md).
