## ADDED Requirements

### Requirement: Yii user access lifecycle

Система SHALL выполнять полный контракт
[YII2-USER-ACCESS-001](../../../../../specs/YII2-USER-ACCESS-001.md).

#### Scenario: Administrator invites and manages an activated account

- **WHEN** авторизованный администратор выдаёт ссылку, получатель активируется,
  администратор назначает роль и блокирует/восстанавливает доступ
- **THEN** Yii формы вызывают одного application owner, роли и аудит сохраняются,
  старые sessions и использованные ссылки не обходят проверки

#### Scenario: Rejection or concurrent operation

- **WHEN** операция запрещена, повторена или конкурирует за последнее полномочие
- **THEN** отказ не создаёт частичных facts, история сохранена, последний active
  superadministrator остаётся доступен
