# Единая инструкция и первый администратор — #27

[PR63](https://github.com/Antropophag/fmonitor-2/pull/63) слит штатно
2026-09-09T02:05:38Z, merge `839001b427ccff851dc0332cfe2d731e207f2bee`.
[Полный CI34300992370](https://github.com/Antropophag/fmonitor-2/actions/runs/34300992370)
для head `f89e0a2ccc03d27c0083c311c26b5f79e9772c9c`:8/8 SUCCESS и VERIFY_OK.

README ведёт к единому production runbook. Clean-only native provisioning явно
создаёт одного владельца с existing ролями и Argon2id credential; exact replay
ничего не меняет, partial/existing identity отвергается. Schema отдельно, runtime
DML-only, startup не создаёт администратора. Gate3/Gate5 APPROVED, runbook пройден
в отдельном чистом контуре: login303, health/restart PASS. Контур проверки удалён.

Первый CI34299969447 упал только в missing-config сценарии нового теста: parent
CI env возвращал удалённый fixture password. До fix сохранён полный inventory:
`/tmp/pr63-53ef6828-failure-inventory.log` и `/tmp/pr63-53ef6828-full.log`.
Poison reproduction и независимый review подтвердили изоляцию child config;
production code не менялся. Финальный лог: `/tmp/pr63-f89e0a2c-green.log`.
Private clean-install evidence: `/tmp/fmonitor2-ops27-evidence-dcca600ad7b3`,
0700/0600, без включения secrets/primary evidence в git.

Это интеграция инструкции/provisioning, не переключение основного стенда8092 и
не завершение restore/jobs. Отдельный #33 contour8093 сохраняется; #34 и #36
продолжаются. Нормативы SLA/RPO/RTO/retention остаются решениями владельца.
