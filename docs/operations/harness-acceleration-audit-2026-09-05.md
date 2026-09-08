# Аудит ускорения verification harness — 2026-09-05

Дата: 2026-09-05. Исполнитель: `/root/harness_cost_audit`.
Это read-only исследование сохранённых свидетельств; harness, tests и production
не изменялись, тяжёлые проверки не запускались. Рабочий HEAD на момент аудита:
`88a6715f0046b0c4b99d6206ffa3de59fe6c5728`. В checkout уже находились чужие
untracked-файлы; аудит их не менял.

Проверенные identities текущего harness:

```text
df42826cf5fdd1af6a711f268a2dc79cc0b5b14bd6b0808665839b779ef6ac15  Makefile
ee0fb1d2f1ba0fa5586edc78a1c5a6165b81a4a2fe43f9dc36e6ca0cbbed2904  tools/verification/run.sh
a4016301bc2416970a1a28791f31e5071bb9b51803feeb42d85d31a96a0c84fc  tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

## Что измерено

Последний полный первичный log относится к implementation SHA
`c658ac8a02c2a3de5baac8f7db4c281f47da87fe`; его SHA-256
`74024006639beb32619f7912c83ba18229288b756080d2269f1cba202584adf0`.
Файл private archive создан в `20:43:40+0300` и закончен в `20:49:43+0300`:
наблюдаемое wall-clock окно — 363 секунды. Это длительность всего log-файла,
а не инструментированные per-stage timings, поэтому распределить эти 363 секунд
по стадиям по имеющимся данным нельзя.

В этом log трижды выполнен одинаковый
`docker compose -f compose.test.yaml up --detach --wait test-db`: перед reset,
DB-suite и E2E-suite. Reset и migration выполнены только один раз, как требует
`HARNESS-FULL-AGGREGATION-001`; повторяются только readiness-вызовы Compose.

`pilot_demo_bootstrap_001_test.php` через `pdbContract()` запускает пять уже
канонически классифицируемых InstallationProcess contracts. Поэтому четыре
контракта (`production_migration_runner`, `pilot_case_import`, `artifact_store`,
`pilot_shlz_assets`) исполняются один раз как top-level DB files и ещё раз как
скрытые children bootstrap. `pilot_e2e_flow_001_test.php` исполняется трижды:
внутри bootstrap, отдельно в DB-suite и отдельно в E2E-stage. Последний full run
фиксирует один и тот же actor18 failure во всех трёх путях; terminal protocol при
этом правильно сообщает обе failed stages `db-test,e2e-test` и не печатает
`VERIFY_OK`.

Канонический runner классифицирует 58 из 135 InstallationProcess files как DB по
текстовому наличию `FMONITOR_TEST_DB|new mysqli`. Это измеренный размер выборки,
но не свидетельство ошибки и не основание менять классификацию.

## Что пока является гипотезой

- Удаление двух повторных Compose readiness-вызовов должно сэкономить время,
  однако per-call timings не записаны. Нельзя утверждать размер выигрыша.
- Исключение вложенных повторов пяти contracts должно дать больший выигрыш,
  особенно для real HTTP E2E, но текущий log не содержит child timings.
- Параллельный запуск DB-tests может ускорить suite, но сейчас это небезопасная
  гипотеза: контракты проверяют реальные locks, prefixes, cleanup, ambient decoys
  и последовательную изоляцию. Без отдельной executable spec и RED это делать
  нельзя.
- SHA-based reuse успешных стадий полезен для локальной итерации, но не может
  заменить обязательный полный `make verify` на точном integration SHA. Cache
  также обязан учитывать runner, environment/dependency identities и итоговую
  cleanup-проверку; сейчас такого доказанного контракта нет.

## Самый малый безопасный следующий срез

Предлагается отдельный harness slice: канонический DB-runner передаёт bootstrap
явный suite-owned context, в котором bootstrap не запускает пять inherited
contracts повторно; standalone-вызов bootstrap продолжает запускать их все.
Каждый из пяти файлов остаётся top-level элементом канонической DB-suite, поэтому
`run_files()` по-прежнему пытается выполнить следующий файл после любого отказа.
E2E остаётся отдельной обязательной стадией и сохраняет независимый stage verdict.

До реализации нужны все Gates 1–5. Executable spec должна закрепить:

1. каждый top-level DB file исполняется ровно один раз и в стабильном порядке;
2. standalone bootstrap сохраняет все inherited checks;
3. canonical suite не доверяет cached PASS и не превращает failure в skip;
4. setup/reset/migrate выполняются один раз, все независимые stages делаются
   attempt-all, оба stdout/stderr сохраняются;
5. cleanup, foreign-decoy preservation и exact terminal summaries остаются
   неизменными; `VERIFY_OK` допустим только после всех PASS;
6. evidence фиксирует exact commit SHA и hashes runner/Makefile/test inputs.

RED должен быть дешёвым fixture/overlay tracer без Docker: он считает реальные
child/top-level invocations при injected middle failure и доказывает продолжение
последующих top-level файлов. После независимого Gate 3 минимальный GREEN меняет
только test orchestration seam; затем нужны focused harness regressions,
independent Gate 5 и один полный exact-SHA `make fresh-test-verify` с teardown.

Отдельным последующим срезом можно убрать два лишних Compose `up --wait`, заменив
их дешёвой fail-closed DB readiness-проверкой только внутри уже успешно созданного
full-run lifecycle. Не следует объединять это с первым срезом: иначе RED не
локализует выигрыш и regressions в lifecycle отдельно от de-duplication.

Текущие обязательные контракты не разрешают ради скорости менять девять стадий,
их порядок, initial clean reset/canonical migration, attempt-all независимых
стадий, failure aggregation, isolation/owned cleanup или exact-SHA финальную
проверку. Safe-log blocked mechanisms в этом аудите не исследовались и не
запускались.
