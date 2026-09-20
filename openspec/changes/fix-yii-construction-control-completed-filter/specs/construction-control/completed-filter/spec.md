## Purpose

Определяет наблюдаемое поведение Yii2-очереди стройконтроля при отображении завершённых монтажных дел существующим клиентским переключателем.

## ADDED Requirements

### Requirement: Завершённые дела доступны клиентскому фильтру

Для actor с exact `construction_control.read` система SHALL включать `working` case с обоими append-only фактами `pto_act` и `declaration` в `GET /pilot/construction-control` с `completed=true`. Клиент SHALL скрывать строку по умолчанию, показывать после включения фильтра и снова скрывать после выключения, не скрывая активные строки.

#### Scenario: Полный цикл переключателя

- **WHEN** инженер включает и выключает «Показывать завершённые»
- **THEN** завершённая строка проходит состояния hidden/visible/hidden, а активная остаётся visible

### Requirement: Промежуточное документальное закрытие исключено

Система MUST исключать `working` case с `pto_act`, но без `declaration`. Неоткрытый case SHALL по-прежнему требовать accepted current original и authoritative preopening projection.

#### Scenario: Есть только акт ПТО

- **WHEN** сохранён `pto_act`, но отсутствует `declaration`
- **THEN** case отсутствует в server response очереди

### Requirement: Чтение сохраняет доступ и историю

GET, HEAD и клиентская фильтрация MUST не изменять case, completion, checklist, assignment или audit facts. Guest redirect, exact permission, HEAD без body, deterministic pagination и safe HTML SHALL сохраняться.

#### Scenario: Повторное чтение

- **WHEN** actor повторяет GET/HEAD и переключает фильтр
- **THEN** строки детерминированы, а факты не изменяются
