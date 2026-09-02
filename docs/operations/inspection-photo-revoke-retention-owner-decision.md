# Owner decision — inspection photo revoke, correction and retention

Date: 2026-09-02  
Decision: **APPROVED_PERMANENT_RETENTION**  
GRILL: `GRILL-007`

## Authorization

Обычный отзыв требует exact capability `inspection.photo.revoke` и current
engineer assignment. Supervisor override является отдельной elevated audited
correction command и не наследуется из broad checklist edit.

## Confirmation and reason

Отзыв требует явного подтверждения и обязательной bounded reason. Причина
сохраняется в append-only revoke fact; silent или безосновательный revoke
запрещён.

## Completed section

Обычный отзыв последнего active photo завершённого section отклоняется до
добавления replacement. Elevated correction отдельно append-ит readiness
correction; она не стирает исходный completion fact.

## Identical-content re-upload

После revoke identical bytes разрешено загрузить как новый evidence fact с
новой operation/fact identity и историей. Content-addressed blob storage может
переиспользовать тот же physical object, не смешивая факты.

## Permanent retention

Все inspection photos — active и revoked — хранятся бессрочно. Runtime,
operators, reset/cleanup и background jobs MUST NOT физически удалять их как
часть product lifecycle. Изменить это правило можно только новым explicit owner
retention decision с legal/security review и отдельной миграцией; отсутствие
такого решения не является разрешением на cleanup.

Это решение сознательно принимает рост storage и долгосрочное хранение
потенциально чувствительного evidence. TEST-USER capacity/backup monitoring
должен учитывать permanent retention.

## Unblocked slices

Target `INSPECTION-PHOTO-REVOKE-001`, elevated readiness-correction command и
offline revoke/re-upload UX разблокированы для Gate 1. Все SSD/TDD gates
сохраняются.
