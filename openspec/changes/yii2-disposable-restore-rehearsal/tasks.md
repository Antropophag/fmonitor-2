> **Owner decision / supersession — 2026-09-14.** Remaining real restore/rollback/reconciliation tasks не выполнять и не считать gates closure issue #76. Completed implementation/evidence, UNKNOWN record и retained lease остаются историческими; offline backup/restore capability сохраняется. Replacement: `yii2-clean-stand-cutover`.

## 1. Root scope, executable specification и Gate 2

- [x] 1.1 Root создать `YII2-DISPOSABLE-RESTORE-REHEARSAL-001` с plain-language summary и нормативными authorization/attestation, driver, roundtrip, rollback и evidence acceptance; проверить traceability к issue #76, PR #124 и `verification-input.json`.
- [x] 1.2 Root инвентаризировать executable test-only seams и реальные DB/artifact/session/credential/identity/restart boundaries; проверить, что inventory называет owner/consumer и не предлагает cleanup `StandRestoreApplication`/`RuntimeRecovery`.
- [x] 1.3 Root добавить public-seam intended RED для production-driver admission: explicit authorization, disposable marker, immutable identity match, credential references, name-only rejection и pre/post-attestation drift; сохранить exact command/failure evidence вне checkout.
- [x] 1.4 Root добавить real-boundary RED для disposable known-state backup → verify → destroy → restore → restart/readiness → DB/history/schema/AUTO_INCREMENT/artifacts/modes/sessions/jobs/golden assertions и отдельного rollback operation; fixture output не может удовлетворить assertions.
- [x] 1.5 Root вычислить mandatory Quality Graph plan по `tools/delivery/change-verification.md`, записать exact source/package через harness и убедиться, что unresolved obligations блокируют Gate 2.

## 2. Independent Gate 3 и minimal implementation

- [x] 2.1 Независимый gpt-5.6-sol/low reviewer проверить exact spec, tests, intended RED и verification mapping; записать `APPROVED` или `CHANGES_REQUESTED` в `reviews/tests/YII2-DISPOSABLE-RESTORE-REHEARSAL-001.md`.
- [x] 2.2 Отдельный gpt-5.6-sol/low executor ввести узкий restore-effect driver port/config value под существующим `StandRestoreApplication`, сохранив fixture adapter и все PR #124 bundle/ledger/lease/replay/outcome contracts; focused restore suites GREEN.
- [x] 2.3 Executor реализовать production adapter для exact MariaDB, artifact/session roots и phase evidence с secret redaction, staging/fsync и repeated target attestation; admission/failure/drift tests GREEN.
- [x] 2.4 Executor подключить explicit stop/quiesce, restart и fresh liveness/readiness к существующему pinned compose topology без второй state machine и без implicit project discovery; lifecycle tests GREEN.
- [x] 2.5 Executor добавить reproducible disposable rehearsal/rollback runner и safe evidence summary, не меняя harness policy и не включая production credentials; architecture/focused plan GREEN.

## 3. Independent Gate 5 и disposable operational evidence

- [x] 3.1 Root подготовить complete-candidate exact-source Gate 5 package; независимый gpt-5.6-sol/low reviewer проверить spec conformance, destructive safety, authorization, history, ambiguity, secret handling и regression sensitivity и записать verdict.
- [ ] 3.2 Только после Gate 5 и explicit disposable action package создать изолированный stand с новыми credentials/observed identities, загрузить known state и создать/independently verify known-good bundle; evidence содержит exact digests/ids без secrets.
- [ ] 3.3 Выполнить authorized destructive roundtrip и подтвердить после restart DB facts/history/schema/next real AUTO_INCREMENT insert, artifact bytes/modes, expected sessions, applicable jobs/outbox/recovery и golden smoke; записать exact external evidence и redacted summary.
- [ ] 3.4 Инъецировать заранее определённый failure predicate, отдельной authorized operation восстановить known-good backup и повторно подтвердить restart/readiness и обязательный integrity subset; failure и rollback outcomes записаны раздельно.
- [x] 3.5 Выполнить executable `RuntimeRecovery` responsibility inventory и точно перечислить remaining production-cutover prerequisites; legacy retirement объявить возможным только если все legacy responsibilities реально замещены.
- [ ] 3.6 Получить один full exact-source Quality Graph CI `VERIFY_OK` до PR-ready; PR/CI/deployment `UNKNOWN` не считать GREEN, production deployment/cutover не выполнять.

## 4. Done definition

- [ ] 4.1 Done только когда Gates 3/5 `APPROVED`, focused checks и exact-source CI GREEN, disposable roundtrip и rollback имеют exact evidence, inventory полон, production untouched и OpenSpec честно отражает все незавершённые cutover/retirement gates.
