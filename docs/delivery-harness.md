# Delivery harness

`python3 tools/delivery/harness.py` — публичный CLI механики delivery. Он не
принимает решений Gates 3/5 и не запускает проверки из read-only prompts.

## Проверки и отчёт

```sh
python3 tools/delivery/harness.py run --reason correction -- php tests/example.php
python3 tools/delivery/harness.py report
```

Runner сохраняет argv, точный source, identity окружения и fixture, исходный
exit code и полные потоки вне checkout. В stdout возвращается компактный JSON с
фрагментом ошибки и путями к полным логам. `FMONITOR_HARNESS_HOME` может задать
локальный каталог evidence, но он обязан находиться вне любого Git checkout.
GREEN не кэшируется. В GitHub Actions существующий verification runner выводит
полные потоки в job log; локально он показывает компактный фрагмент.

`report` дедуплицирует append-only records и наблюдённые hook events. Token
telemetry остаётся `UNKNOWN`: установленные Codex hooks v1 не предоставляют
поддерживаемые token fields. Размер сохранённого или показанного вывода не
выдаётся за расход токенов.

Planner — единственный источник `verification_lane` и `required_reviews`, но
ширина CI не определяет ceremony. Обычный bounded fix и поддержанный `FAST`
требуют один независимый final review; чувствительные изменения требуют Gate 3
и final review. Агент не выбирает FAST по размеру
diff: v1 ограничен поддержанным bounded UI scope, а tests/spec могут повысить
lane. CI восстанавливает выбранные FAST-команды из exact-source plan; text-only
docs allowlist остаётся отдельным CI mode, а не решением о delivery lane.

## План и диагностика

```sh
python3 tools/delivery/change-verification.py refresh --plan plan.json
python3 tools/delivery/change-verification.py run --plan plan.json --phase focused --diagnostic
```

`refresh` пересчитывает тот же verification plan и сообщает изменение
обязательств. Diagnostic выполняет выбранную фазу последовательно и возвращает
структурированный результат каждой команды, даже если несколько проверок
упали. Обычный run сохраняет fail-fast. Подтверждённые consumer obligations
InspectionEvidence/ChecklistSync живут в существующей verification policy.

Команды `state`, `prepare`, `doctor` и `hook` описывают актуальный source,
готовят role-specific review package и обслуживают repository Codex hooks.
Локальная настройка ограничена доверием к repository hook definitions через
нативный `/hooks`; глобальные настройки harness не перезаписывает.

Во время delivery локально выполняются только bounded focused/fast checks;
существующий exact-source CI consumer выполняет проверки, выбранные
planner/policy. Полный `make test`/`make verify` выполняется только когда
consumer выбирает полный matrix; его локальный запуск требует отдельного owner
override. Отсутствующие live preflight/review adapters и server-side enforcement
из #107 остаются `UNKNOWN`, а не approval или GREEN. Они не требуют повторять
уже GREEN same-source CI и не превращают ручной owner merge в autonomous
admission.
