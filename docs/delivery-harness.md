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
