## 1. Контракт и RED

- [x] 1.1 Зафиксировать нормативный YII2-CHECKLIST-PHOTO-VIEWING-001 и строгие OpenSpec artifacts; проверить `openspec validate --strict`
- [x] 1.2 Подготовить verification-input/plan и закрыть все обязательства либо явно остановить Gate 2
- [x] 1.3 Добавить focused HTTP и browser RED для clean-browser/cross-user/full-size/error-retry/revoked/local-preview сценариев и сохранить intended failure evidence
- [x] 1.4 Получить независимый Gate 3 APPROVED для полного контракта и RED candidate

## 2. Реализация

- [x] 2.1 Отдельному executor добавить минимальный авторизованный GET/HEAD read seam и durable projection URL; проверить focused PHP test
- [x] 2.2 Отдельному executor добавить адресный rendering/full-size/error retry при сохранении local preview/queue; проверить focused browser test
- [x] 2.3 Выполнить planner-selected bounded local checks и подтвердить отсутствие изменений rows/revision/history/private bytes

## 3. Delivery

- [x] 3.1 Получить независимый Gate 5 APPROVED на exact source и исправить все findings с повторным review изменённого delta
- [ ] 3.2 Создать PR без merge/deploy и запустить один selected exact-source GitHub CI consumer; собрать полный failure inventory при неуспехе
- [ ] 3.3 Зафиксировать PR, HEAD, сценарии и пересекающиеся общие файлы в delivery record
