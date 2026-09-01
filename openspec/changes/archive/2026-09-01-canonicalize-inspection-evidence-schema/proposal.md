## Why

HTTP-путь checklist сейчас создаёт и изменяет четыре production-таблицы при запросе. Это мешает воспроизводимому fresh deployment и блокирует calibration slice: inspection evidence должен появляться только после canonical migration, а runtime обязан fail closed при несовместимой схеме.

## What Changes

- Добавить строгую canonical migration v8 для `fm2_checklist_revisions`, `fm2_checklist_operations`, `fm2_checklist_operation_installers` и `fm2_checklist_photos` непосредственно после landed catalogue v1–v7.
- В fresh schema сразу включить три template-identity columns operations и `assignment_source` installers, которые runtime сейчас добавляет через `ALTER`.
- Поддержать без потери данных обе уже наблюдаемые совместимые upgrade-формы; несовместимую family отклонять до первой мутации.
- Удалить production `CREATE`/`ALTER` из `ChecklistSync`; HTTP и characterization становятся только consumers canonical schema.
- Добавить clean/repeat/compatible-upgrade/conflict/prefix-isolation/runner-order/runtime-no-DDL verification.
- Source oracle: текущий `ChecklistSync::ensureSchema` и checklist characterization. Actor: deployment operator для migration; checklist user остаётся consumer через существующий HTTP seam.
- Release value: fresh test environment получает inspection evidence schema до первого пользователя; calibration item completion больше не зависит от request-time DDL.
- Non-goals: менять completion/retraction semantics, photo revoke reason/history, dimensions/caption, authorization policy, checklist commands или persistence redesign.
- NEEDS_GRILL: перечисленные behavior gaps остаются отдельными slices и не блокируют ownership-only migration.

## Capabilities

### New Capabilities

- `deployment/canonical-inspection-evidence-schema`: canonical runner единолично владеет точной четырёхтабличной inspection-evidence family и её совместимыми upgrade paths.

### Modified Capabilities

Нет.

## Impact

- Canonical migration runner и новый strict schema migration в `app/InstallationProcess`.
- `app/PilotHttp/ChecklistSync.php`: удаление runtime DDL без изменения публичного checklist behavior.
- MariaDB schema/runner tests, checklist current-crew/template/offline characterization, architecture baseline debt reduction и clean-deployment verification.
