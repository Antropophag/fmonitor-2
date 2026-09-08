## Purpose

Локальный Docker pilot получает явную доверенную схему HTTP и сохраняет
fail-closed поведение owner-backed пользовательских страниц при неверной настройке.

## ADDED Requirements

### Requirement: Explicit local transport scheme
Local profile SHALL соблюдать PILOT-LOCAL-TRUSTED-SCHEME-001 с native session
owner, exact authorization и без записи domain facts при GET.

#### Scenario: Local administrator opens users
- **WHEN** активный authorized администратор открывает пользователей в local profile
- **THEN** явная trusted http configuration позволяет200 и owner-committed action tokens

#### Scenario: Unconfigured transport
- **WHEN** trusted scheme отсутствует или пустая, даже с forwarded header
- **THEN** страница возвращает503 без tokens или user data
