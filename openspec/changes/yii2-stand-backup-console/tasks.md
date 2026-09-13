## 1. PHP contract и Gates 1–3

- [ ] 1.1 Root создать normative PHP/Yii2 spec и verification input, привязанные к #76/#115 и base PR #113; strict validation и harness plan должны пройти.
- [ ] 1.2 Root заменить Python-seam tests на black-box вызовы `php bin/yii stand-backup/*`, сохранив exact bundle/failure/replay matrix; получить intended RED.
- [ ] 1.3 Независимый sol/low reviewer вынести Gate 3 APPROVED по PHP public seam.

## 2. Yii2/PHP implementation

- [ ] 2.1 Executor реализовать PHP application owner и filesystem/backup ports в `app/RuntimeRestore`; focused unit tests GREEN.
- [ ] 2.2 Executor добавить Yii2 console controller/config и safe JSON/exit mapping; real `php bin/yii` tests GREEN.
- [ ] 2.3 Executor удалить Python production artifact и добавить architecture ratchet: production seam/runtime не загружает Python/rapid-pilot.
- [ ] 2.4 Executor зарегистрировать PHP tests в Quality Graph; bounded focused plan GREEN.

## 3. Gate 5 и поставка

- [ ] 3.1 Root подготовить exact-source package; независимый reviewer вынести Gate 5 APPROVED.
- [ ] 3.2 После APPROVED создать stacked PR относительно #113 и получить один exact-source Quality Graph VERIFY_OK; deployment остаётся UNKNOWN.
- [ ] 3.3 Append-only записать PHP delivery result; старый Python WIP сохранить только как superseded history, не включать в candidate.
