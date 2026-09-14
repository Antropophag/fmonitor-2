## Context

См. `proposal.md`. Текущий `MariaDbYiiChecklist::queue()` выбирает только `working`, поэтому неоткрытая readiness не доходит до стройконтроля. Существующий preopening journey уже владеет вычислением применимого оригинала, формой и атомарной командой `open_confirmed`; дублировать эти правила в checklist controller нельзя.

## Goals / Non-Goals

**Goals:** единая read-модель очереди для готовых и открытых дел; server-side назначение actor; переиспользование существующего owner открытия; read-only GET/HEAD; переход после POST обратно к checklist URL стройконтроля.

**Non-Goals:** новая команда открытия, schema/DDL, новые capabilities, изменение кадровых или документальных gates, открытие при одном только выбранном составе, изменение post-opening checklist semantics, `rapid-pilot` или deployment.

## Decisions

1. Owning module для состава очереди и checklist projection остаётся `InspectionEvidence`; он читает readiness через существующие persisted original/selection/application facts, но не пишет их. Альтернатива — собирать строки в controller — нарушила бы явный application seam.
2. Готовая строка допускается только при актуальном применимом original lineage и exact назначении текущего engineer. Используется тот же смысл readiness, что в `InstallationProcess` card/queue, без упрощённого `process_state != working`.
3. Checklist view получает явную preopening model: status, opening fields/CSRF и возможность действия. POST делегируется существующему Yii execution route/confirmed-original owner; success return URL ведёт к construction-control checklist. Альтернатива — второй writer в ChecklistController — отвергнута.
   Existing confirmed-original owner сохраняется единственным writer, но его role admission расширяется с FKR/manager на назначенного `construction_control_engineer` при exact `installation.open`; owner всё равно повторно проверяет actor, назначение, original и остальные gates внутри транзакции. Это необходимое изменение #40, а не новый seam.
4. Server-side queue отдаёт только строки, относящиеся к actor, либо сохраняет отдельно обозначенный manager-wide режим по действующему контракту. Клиентский переключатель «Мои/Все» не считается авторизацией.
5. Предоткрывающий checklist является read-only shell: пункты и offline sync не активируются до `working`. Это сохраняет инвариант «чек-лист нельзя начать до открытия».
6. Root-authored executable tests покрывают queue, checklist form, authorization/no-facts, full transition, replay и adjacent #39 exclusion. Изменения Yii HTTP paths проходят `make architecture-check`, включая обязательный PilotHttp qualification при фактическом затрагивании `app/PilotHttp`.

## Risks / Trade-offs

- [Расхождение readiness между очередью и карточкой] → общий read owner/value или чувствительный parity test на одинаковых facts.
- [Утечка чужих готовых объектов через HTML] → фильтрация actor на сервере и отдельный отрицательный HTTP test; JS не является security boundary.
- [Дублирование формы и неверный return path] → один partial/model contract и тот же execution owner, с browser/HTTP проверкой фактической навигации.
- [Регрессия #39] → в focused matrix одновременно присутствуют ready, working, PTO и completed cases.

## Migration Plan

Schema migration не нужна. Поставка — обычный code/test deploy; rollback возвращает прежнюю read projection и UI, не изменяя сохранённые domain facts. Stand/deployment остаются вне этого change.
