## Context

Native selection, original binding и PDF approved, canonical frontier15. Portal
пока использует prepare/registration predecessor; нельзя производить новую identity
через старый renderer/writer. Original HTTP planning reconciled separately.

## Goals / Non-Goals

Рабочая server-rendered shlz форма выбора и on-demand PDF. No domain reimplementation,
original storage/read, composition application, opening, historical writer migration.

## Decisions

HTTP coordinator wrapper перехватывает только fresh routes и legacy fresh denials,
делегируя остальные requests прежнему coordinator. Explicit FMONITOR_FRESH_ORDER_FLOW=1
не получает grants и не deploy-ит schema. GET старой prepare ссылки redirect-ит в
новый wizard; old POST не могут mutate в fresh mode. Native session actor/CSRF.

Public readSelectionPortal принадлежит AssignmentOrderComposition: authorized
snapshot object/latest selection/candidates/template date. SQL только там. HTTP
создаёт DTO и вызывает existing production selection/template factories. Fresh DB
prefix един для native process/object source; generation identity mismatch fail closed.

Форма без JS для выбора: public checkbox/radio/button/field, existing PilotView shell.
No custom base shlz overrides. Failed retry сохраняет intent/key, 4xx reload=new key.
Новый query не объявляет selected состав effective. Parent application integration
позднее свяжет actual effective owner, не standalone null projection.

## Risks / Trade-offs

Read query должен быть coherent и fail closed при partial schema. Public HTTP
fixture использует настоящую session и native DB; old protected E2E не меняется.
PDF audit retry наследует approved owner и не является idempotent selection replay.

## Migration Plan

Gate1→RED→Gate3→minimal GREEN→regression/architecture/visual/focus→Gate5/commit.
Separate owned native deployment and browser QA; старый preview image сохраняется.
До full make verify/VERIFY_OK нет CI/publication/launch claim.
