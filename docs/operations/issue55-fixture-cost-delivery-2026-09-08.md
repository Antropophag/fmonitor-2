# Issue55 — измерение и ускорение снимков схемы

## Объём и основание

Владелец разрешил после завершения CI/merge PR10 автономно продолжить issue55.
PR10 имеет полный SUCCESS в [run34255531489](https://github.com/Antropophag/fmonitor-2/actions/runs/34255531489)
на35c137ad и вмерджен как102e6056. PR37 закрыт, ветка сохранена; открытых PR перед началом55 нет.
База новой работы —102e6056, отдельный worktree/branch, test Compose project
`fmonitor2-issue55`, loopback DB порт23365. Стенд8092 и его volumes не затрагиваются.

## Проверенная первая гипотеза

На одной машине PHP8.5.10/MariaDB11.4.7 последовательно выполнены три прогона:

| Файл | Before1 | Before2 | Before3 |
|---|---:|---:|---:|
| template_generation | 2.535с | 2.442с | 2.742с |
| template_generation_boundaries | 23.594с | 23.528с | 23.442с |
| original_history_download | 26.539с | 27.148с | 27.360с |

Все PASS. Отдельный временный фазовый профиль21 template-сценария:
selection5.477с, native migrations4.700с, original migration/seed3.749с,
сценарии2.826с, native seed/readiness1.944с, registry migration/seed1.519с,
PDF renderer probe1.104с, parser probe0.017с. Остальные фазы меньше0.2с каждая.
Профиль21.779с — отдельный instrumented прогон, не один из baseline.
Поэтому кеширование PDF prerequisites отложено: потенциальный выигрыш около1с
на этом файле не решает длительность CI. Никакой кеш или shared DB не внедрён.

## Выбранный срез

Read-only анализ полного database-setup теста выявил484 state snapshot и23таблицы
в его fixture DB. Каждый state snapshot структурно выполняет140 запросов:
два перечня таблиц, пять семейств metadata на каждую таблицу и23свежих row reads.
Это статический расчёт, не SQL trace. Предлагается пять database-wide metadata
SELECT вместо115 per-table; каждый snapshot остаётся свежим, row reads сохраняются.

Сохраняются все поля/нормализация/порядок columns/keys/FK/checks, вся матрица
мутаций и zero-DML/DDL/foreign-data assertions. Временное сравнение прежнего и
пакетного наблюдателя на полном тесте дополнит независимый literal-schema regression.

## Evidence

Первичные redacted логи, benchmark script и временная инструментализация:
`~/.local/state/fmonitor2/issue55-20260908/` (вне репозитория).
Изменения production source отсутствуют. Runtime checks и редкие сценарии не
исключаются из CI; матрица/permissions/publisher не меняются.

Фактические after-замеры, независимые review и CI дописываются по получении;
эта запись сама по себе не заявляет завершения55 или ускорения полного CI.

## Baseline тяжёлого теста

Три последовательных неизменённых прогона `assignment_order_original_database_setup_001_test.php`
на102e6056:85.392с,82.736с,81.874с, все exit0/`ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_001_OK`.
Медиана82.736с. Между прогонами не выполнялись другие DB тесты.
Предметные файлы после profiling template восстановлены побайтно.

Для сравнения CI: последний зелёный run34255531489 имеет integration13м53с,
E2E3м46с и wall-clock всего workflow14м13с (17:10:54–17:25:07UTC).
Эти числа относятся к baseline PR10, не к ещё не опубликованной оптимизации.

## RED и независимая проверка наблюдателя

Новый `tests/Verification/batched_schema_snapshot_001_test.php` использует
буквальную схему двух таблиц, полные ожидаемые metadata и повторные чтения после
DDL. `php .../batched_schema_snapshot_001_test.php` даёт intended RED255:
новый test-support helper отсутствует. Production код не менялся.
Первое независимое Gate3 CHANGES_REQUESTED указало на отсутствие freshness-проверки
collation существующей таблицы; добавлены ALTER CONVERT и точные table/column
expectations, а также finally закрытие соединения. История review сохраняется.
