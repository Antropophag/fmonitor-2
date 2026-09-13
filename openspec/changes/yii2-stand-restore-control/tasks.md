## 1. Root contract и intended RED

- [x] 1.1 Root создать `YII2-STAND-RESTORE-CONTROL-001` normative executable spec и exact `verification-input.json`, привязанные к issue #76, PR #119 и текущему main; verify через `openspec validate --strict` и harness root package.
- [x] 1.2 Root написать public-seam RED для Yii2 argv/output, shared bundle validation, unsafe/non-empty target и zero-effects rejection; выполнить bounded commands и сохранить intended failure evidence вне checkout.
- [x] 1.3 Root добавить production-shaped backup → mutate/destroy disposable target → restore roundtrip RED для DB/history/AUTO_INCREMENT, artifacts, sessions и readiness/inventory; fixture MUST не подменять restore result.
- [x] 1.4 Root добавить RED для replay/conflict, restore failure/interruption и post-restore readiness failure; UNKNOWN MUST не публиковать confirmed state.
- [x] 1.5 Независимый gpt-5.6-sol/low reviewer проверить exact spec, tests и RED evidence; Gate 3 проходит только с записанным `APPROVED`.

## 2. Minimal Yii2/application implementation

- [x] 2.1 Отдельный executor минимально выделить общий PR #119 bundle/manifest validator без изменения backup outcomes; существующий stand-backup focused suite остаётся GREEN.
- [x] 2.2 Executor реализовать `StandRestoreApplication` и test-only recording restore driver с preflight-before-effects, append-only operation/lease protocol и no-success-on-ambiguity; approved focused tests GREEN.
- [x] 2.3 Executor подключить restore controller через существующую Yii2 console composition и safe JSON/exit mapping; real `php bin/yii` tests GREEN без нового bootstrap.
- [x] 2.4 Executor зарегистрировать новые tests в Quality Graph и выполнить только generated bounded focused/fast plan; результаты должны быть GREEN на exact candidate source.

## 3. Legacy inventory, review и delivery

- [x] 3.1 После GREEN выполнить executable inventory production consumers `RuntimeRecovery`/legacy CLI и сравнить forward-update, jobs recovery, schema compatibility и runbook contracts; записать каждую оставшуюся responsibility.
- [x] 3.2 Удалить legacy path только если 3.1 доказывает полное замещение; иначе сохранить его и добавить architecture assertion, предотвращающий новую production dependency.
- [x] 3.3 Root подготовить exact-source Gate 5 package; независимый gpt-5.6-sol/low reviewer вынести `APPROVED` либо вернуть findings в Gate 2/4.
- [ ] 3.4 После Gate 5 получить один exact-source Quality Graph CI `VERIFY_OK` перед PR-ready; live deployment, operational drill, cutover и checklist #76 остаются неизменёнными до отдельного executable evidence.
