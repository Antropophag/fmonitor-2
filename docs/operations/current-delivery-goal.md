# Текущая цель — №31, обратная связь тестового стенда

Поручение владельца 2026-09-14: реализовать [№31](https://github.com/Antropophag/fmonitor-2/issues/31) от актуального main до PR-ready, строго минимальный механизм обратной связи через существующие Yii2/application/persistence и shlz-ui seams. Без notification/telemetry framework. Checklist/process owners №40, OTIZ №66 и общие verification/CI files не менять без доказанной необходимости.

Контракт: [FEEDBACK-001](../../specs/FEEDBACK-001.md). Lifecycle: [test-stand-feedback](../../openspec/changes/test-stand-feedback/). Root владеет scope/spec/tests; отдельный sol/low executor реализует; независимые sol/low reviewers решают Gates 3/5. Автономное делегирование spec/tests не разрешено. Merge и deployment не входят в поручение.

Предыдущий указатель №129 сохранён [без изменений](current-delivery-goal-history-issue-129-2026-09-14.md). Фактические source/PR/CI/lane получать через harness state и активный package. Full make test/verify локально запрещён; bounded checks и existing exact-source CI. Отсутствующие live adapters/enforcement/deployment остаются UNKNOWN.

Delivery record: [issue-31-feedback-delivery.md](issue-31-feedback-delivery.md).
