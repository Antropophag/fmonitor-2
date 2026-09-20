# Текущая цель — быстрый срез Yii operational UI consistency

Поручение владельца 2026-09-20: одним bounded срезом исправить деградации основного Yii-интерфейса: перенести трёхрядный календарь из rapid-pilot oracle без runtime-зависимости, исправить представление объекта, использовать полноценные публичные компоненты `shlz-ui`, устранить множественное формирование навигации и закрепить порядок «Объекты → Стройконтроль → Календарь → ОТиЗ → Монтажники → Пользователи → Роли» с RBAC-скрытием без перестановки.

Иконка календаря, новые domain facts/DDL/RBAC permissions и формулы ОТиЗ не входят. Lifecycle: [harden-yii-operational-ui](../../openspec/changes/harden-yii-operational-ui/). Contract: [YII-OPERATIONAL-UI-CONSISTENCY-001](../../specs/YII-OPERATIONAL-UI-CONSISTENCY-001.md).

Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые gpt-5.6-sol/low reviewers выполняют planner-required reviews. Локально только bounded focused checks; полный `make test`/`make verify` запрещён. После approval обновить локальный стенд `8093` без удаления named volumes; один exact-source CI остаётся обязательным.
