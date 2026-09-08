# Две integration jobs — решение владельца и контракт

Владелец2026-09-08 явно одобрил две параллельные jobs («ну давай сделаем»)
после обсуждения стандартного GitHub Actions matrix/sharding. Это разрешает
изменение ранее согласованной CI-матрицы только в данном объёме.

## Контракт INTEGRATION-SHARDING-001

- Сохраняются категории unit/integration/e2e/governance и общий обязательный verify.
- Integration запускается matrix из двух jobs `shard: [1, 2]`, `fail-fast: false`.
- Каждая VM использует собственную testDB и always teardown. Общих локальных DB,
  данных, файлов результатов или процессов между jobs нет.
- CLI `list/run integration --shard 1/2|2/2` выбирает чередующиеся файлы из полного
  validated integration inventory, отсортированного по пути. Первая часть берёт
  индексы0,2,4; вторая1,3,5. Суммарно каждый тест исполняется ровно один раз.
- Без shard selector прежний полный category запуск и порядок сохраняются.
  Shard допустим только для integration; ошибочный selector отклоняется до runtime.
- Каждый shard продолжает остальные назначенные файлы после отказа, затем
  возвращает nonzero. Ошибка/отмена/пропуск необходимой matrix job не дают VERIFY_OK.
- Все файлы inventory валидируются до деления. Новые файлы попадают в одну часть
  автоматически; timing manifest и отдельный балансировщик не вводятся.
- `make test CATEGORY=integration SHARD=1/2` поддерживается через тот же CLI.
  SHARD, CATEGORY и MAKE override vars не передаются вложенным тестовым Make-командам.
- Docs-only по-прежнему пропускает integration целиком с DOCS_VERIFY_OK;
  code/tests/CI/unknown paths, schedule/manual/release требуют обе части.

## Scope и стоимость

Меняются только test runner, workflow, его focused verification и инструкции.
Branch protection, permissions, pins, продукт, стенд и данные не меняются.
Кеш fixtures и дальнейшая микрооптимизация остановлены.

Модель по run34263647931:177 файлов/546.099с; подготовка и прочее130.901с.
Чередование даёт255.681с и290.418с. При той же скорости модель integration
11:17→около7:01; дополнительные runner seconds около131. Это расчёт, не benchmark.
Полный actual CI на exact head, проверка union двух логов и independent reviews
обязательны перед merge; реальные durations добавляются в PR после завершения.
