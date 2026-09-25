# Construction-control mobile list — delivery record

Owner authorization 2026-09-25: реализовать согласованный mobile-first список стройконтроля, создать PR и merge в `main`. Это поручение supersedes предыдущую queue pause для данного bounded presentation slice. Root authors scope/spec/tests; отдельный `gpt-5.6-sol / low` executor implements; independent planner-required reviewers decide Gates 3/5.

- Base: `origin/main@403a57bded679ec79819a2833f6ed50832f01d86`.
- OpenSpec: `redesign-construction-control-mobile-list`.
- Contract: `YII2-CONSTRUCTION-CONTROL-MOBILE-LIST-001` plus superseding queue clause in `BITRIX-ORDER-DOCUMENT-LINKS-001`.
- Persistence/schema/deploy: inapplicable; read projection and Yii presentation only.
- Full local `make test` / `make verify`: prohibited by owner decision; bounded focused checks plus one exact-source CI.
- Gate 3, Gate 5, PR, CI and merge identities: PENDING.

Owner steering 2026-09-25: до PR собрать candidate в существующий local Yii2 stand `fm2-local-timofey`, сохранить volumes/data и предоставить URL для ручной валидации. PR/CI/merge блокированы до явного owner validation. Production deployment не разрешён.
