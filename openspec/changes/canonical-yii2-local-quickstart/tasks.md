## 1. Gate 1–3: контракт и RED

- [x] 1.1 Зафиксировать `YII2-LOCAL-QUICKSTART-001`, owner issue #128 и полный verification input; проверить `openspec validate --strict` и сгенерировать Quality Graph plan без незакрытых obligations
- [x] 1.2 Написать минимальные public-seam tests для routing Make-команд, config rejection, clean/repeated `make up`, сохранения volumes при `down` и ограничения `reset`; сохранить intended RED, вызванный legacy routing, а не setup failure
- [x] 1.3 Получить независимое Gate 3 `APPROVED` на spec, tests, mapping и RED evidence до production edits

## 2. Gate 4: минимальная реализация

- [ ] 2.1 Реализовать идемпотентный create-or-verify runtime DB provisioning seam и доказать focused tests для clean, exact replay и incompatible grants
- [ ] 2.2 Перевести `make up/down/logs/ps/reset` на один canonical `deploy/runtime/compose.yaml` project и доказать focused lifecycle tests без ссылок на rapid-pilot
- [ ] 2.3 Обновить `.env.example` для минимального Yii2 local contract с fail-closed placeholder validation и доказать config tests без утечки secrets
- [ ] 2.4 Обновить README/development setup, сохранив production runbook и обозначив legacy contour как неканонический; проверить documentation/architecture assertions
- [ ] 2.5 Выполнить bounded disposable clean и repeated startup на уникальном project/port, проверить live/ready и сохранение identity/data, затем безопасно удалить только disposable project

## 3. Gate 5 и Done

- [ ] 3.1 Пересчитать exact-source verification plan и выполнить focused/adjacent commands; сохранить компактное evidence вне checkout и не объявлять UNKNOWN как GREEN
- [ ] 3.2 Получить независимое Gate 5 `APPROVED` на полный candidate diff, Gate 3 tests и verification evidence; corrections вернуть в соответствующий gate
- [ ] 3.3 Подготовить PR-ready exact source и один authoritative full Quality Graph CI run; Done означает APPROVED Gates 3/5, GREEN mapped checks/CI и отсутствие deployment/reset существующих стендов
