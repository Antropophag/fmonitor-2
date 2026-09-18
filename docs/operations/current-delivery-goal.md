# Текущая цель — импорт инженеров строительного контроля

Поручение владельца 2026-09-18: расширить штатный Yii2 legacy import от актуального `main`, чтобы referenced инженеры из `fm_maintable.responsstroicontrol` создавались в локальном справочнике как ожидающие явного приглашения, а импортированные объекты имели каноническую связь с этими пользователями. Приглашения, credentials и активация импортом не создаются; владелец позднее запускает существующее административное действие.

Контракт: [LEGACY-CONTROL-ENGINEER-IMPORT-001](../../specs/LEGACY-CONTROL-ENGINEER-IMPORT-001.md). Lifecycle: [import-legacy-control-engineers](../../openspec/changes/import-legacy-control-engineers/). Root пишет scope/spec/tests; отдельный `gpt-5.6-sol/low` executor реализует; независимые reviewers решают planner-required Gates 3/5. Локально только bounded focused checks; full `make test`/`make verify` запрещён.

Source identity — exact positive legacy `users.id`; ФИО/email не используются для auto-merge. Read-only inventory 2026-09-18: 2 782 objects, 2 без инженера, 7 referenced identities, 0 unresolved; роли 16 «Строительный контроль» (6 identities/2 680 refs) и 18 «Руководитель отдела» (1 identity/100 refs), все active. Наблюдение не заменяет runtime fail-safe validation.

Не входят автоматическая отправка приглашений, перенос паролей/сессий, импорт unreferenced users, UI редактирования назначения, создание распоряжений, изменение workforce sync или новая логика в `rapid-pilot`. Действующий стенд `8093` и WIP других worktrees сохраняются.
