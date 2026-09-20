## Context

Yii2 reader фильтровал любой case с `pto_act`, хотя production JS уже умеет фильтровать `data-completed`. Runtime использует server-side pagination и exact original-lineage predicate.

## Goals / Non-Goals

**Goals:** сохранить completed cases в read projection, PTO-only closure исключить, lineage/pagination не ослаблять.

**Non-Goals:** не менять writers, schema, process state или production JS.

## Decisions

- Completion вычисляется только как native `pto_act AND declaration`.
- Candidate predicate включает active working без PTO, completed working с declaration и existing preopening branch с exact original lineage.
- Total и LIMIT используют тот же SQL predicate; completed rows сортируются после активных.
- При отсутствии current assignment completed-only row использует существующую historical engineer projection для «Мои».

## Risks / Trade-offs

- [Pagination drift] → exact two-page integration assertions.
- [Lineage regression] → исходный selection/original identity predicate сохранён целиком.
- [False-positive UI test] → active и completed rows проверяются до, после и после обратного переключения.

## Migration Plan

Schema/data migration отсутствует; deploy заменяет application image, rollback возвращает прежний image без изменения facts.
