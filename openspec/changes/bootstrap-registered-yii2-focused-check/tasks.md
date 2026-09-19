## 1. Gate 1–3

- [x] 1.1 Зафиксировать `REGISTERED-FOCUSED-BOOTSTRAP-001`, OpenSpec delta и verification input; проверить `openspec validate --strict` и planner obligations.
- [x] 1.2 Root написать executable tests для exact planner route, owned DB lifecycle, setup-failure stages, source isolation и exact selected argv; получить intended RED через prepared harness command.
- [ ] 1.3 Получить обязательный независимый Gate 3 review полного spec/test/RED candidate и устранить все findings до APPROVED.

## 2. Gate 4

- [ ] 2.1 Отдельному executor углубить existing `run-in-profile` для unique Compose lifecycle, bounded readiness и stage-specific SETUP_FAILURE; focused tests должны пройти.
- [ ] 2.2 Адресно подключить pinned `shlz-ui` assets и подходящий existing profile только для navigation test; planner/package test подтверждает route и сохранённые identities.
- [ ] 2.3 В disposable worktree без host dependencies и sibling `shlz-ui` выполнить первую и повторную prepared command, controlled uncommitted mutation RED и restored GREEN, а также bounded negative setup cases; сохранить external evidence и отдельно measured setup/test durations, если доступны.

## 3. Gate 5 и publication

- [ ] 3.1 Выполнить planner-selected focused checks без local full suite и получить независимый final review exact source с APPROVED.
- [ ] 3.2 Зафиксировать candidate, выполнить один exact-source CI через selected existing consumer, собрать полный failure inventory при сбое и не дублировать full run локально.
- [ ] 3.3 Push отдельной ветки и открыть один PR от актуального `origin/main`; delivery record содержит PR/head, одну clean-worktree command, first/repeat results, isolation proof и remaining limitations.
