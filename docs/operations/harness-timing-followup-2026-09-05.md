# Harness timing — fresh exact-SHA followup

Дата: 2026-09-05. Автор `/root`.
Дополняет `harness-acceleration-audit-2026-09-05.md`, не переписывает его гипотезы.
Full `make verify` на clean SHA `060e880cdff41b8564a005fba95d6ed796c7772f`
занял 369.515 seconds; exit2, DB/E2E failures сохранены.
Archive/hashes: `protected-e2e-admission-integration-verification-2026-09-05.md`.

Monotonic интервалы между terminal stage markers (включают orchestration):

| Stage | Seconds |
| --- | ---: |
| reset | 1.004 |
| migrate | 0.172 |
| architecture | 19.409 |
| lint | 31.269 |
| unit | 14.268 |
| DB | 269.646 |
| characterization | 30.032 |
| E2E | 3.701 |
| diff | 0.017 |

Самые длинные интервалы от top-level `VERIFY <file>` до следующего marker:
original database setup 100.010s; workforce canonical runner 21.579s;
demo bootstrap 20.420s; object-detail characterization 13.557s;
prepare form 11.927s; UI shell 10.660s. Это inclusive intervals, не отдельные
timings скрытых child processes.

Следовательно, предложенное в первом audit устранение nested bootstrap repeats
не доказано как крупнейший выигрыш. Даже весь bootstrap interval составляет
около 20s текущего failing run. Причина 100s schema setup пока не исследована;
нельзя объявлять timeout/lock/DDL причиной без отдельного наблюдения.
Производительность GREEN E2E также неизвестна, поскольку текущий run падает
раньше полного journey. Скорость запуска не является доказательством готовности.

С deadline 9 сентября 09:00 МСК harness код не меняется без доказанного выигрыша
на критическом пути и Gates 1–5. Full verify, isolation, cleanup, attempt-all и
exact-SHA evidence сохраняются. Измерение выполнено внешним private recorder,
без ослабления runner или tests.
