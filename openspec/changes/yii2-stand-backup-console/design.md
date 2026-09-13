## Context

См. `proposal.md`. Репозиторий уже имеет Yii2 console composition и модуль `app/RuntimeRestore`; Python используется delivery harness, но не является application runtime №76.

## Goals / Non-Goals

**Goals:** Yii2 command owner; PHP application protocol; injectable PHP filesystem/backup ports; сохранение проверенной exact-target/bundle/replay матрицы; удаление Python production seam.

**Non-Goals:** live stand driver, deployment, restore/reset, изменение domain facts, новый framework или Python runtime dependency.

## Decisions

1. `app/YiiRuntime/Commands/StandBackupController.php` владеет только CLI adaptation; `app/RuntimeRestore/StandBackupApplication.php` владеет protocol/state machine.
2. PHP ports описывают target inventory, backup payload writer и filesystem durability. Recording implementation доступна только test contour; production external-effect port остаётся fail closed до отдельного live change.
3. JSON contracts и content-addressed layout сохраняются, но реализуются PHP. Tests вызывают реальный `php bin/yii`; Python test code является внешним наблюдателем.
4. `config/yii/console.php` регистрирует command через существующую DI/config seam. Никаких require/import из `rapid-pilot`.
5. `tools/delivery/stand-backup.py` исключается из candidate; его прежние packages/reviews остаются историей, а не production dependency.

## Risks / Trade-offs

- [Перенос логики меняет implementation при стабильном контракте] → новый RED/Gate 3 проверяет именно PHP public seam.
- [PHP filesystem fault injection может стать test-only framework] → порты остаются узкими и живут в RuntimeRestore; production wiring fail closed.
- [Live backup пока отсутствует] → отдельный authorized change после exact-source CI.

## Migration Plan

1. Создать PHP normative spec/tests и получить Gate 3.
2. Реализовать RuntimeRestore owner и Yii2 command, удалить Python production artifact.
3. Получить focused GREEN, Gate 5 и stacked PR; stand остаётся неизменным.
