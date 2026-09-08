# Issue55 — убрать повтор общего architecture-check

## Основание и граница

Владелец поручил продолжить55 после поставленного PR57. Работа от main
`6cc90b81532cad888510f146f18bc8aa27a02d7f`, в отдельной ветке/worktree.
Предыдущий профиль: nested architecture-check36.375с, семь migration CLI2.730с.
Свежие baseline/after прогоны выполняются на отдельной MariaDB11.4.7,
Compose project `fmonitor2-issue55-workforce`, порт23366. Стенд8092 не используется.

Узкий технический контракт WORKFORCE-ARCHITECTURE-DEDUP-001:
- Workforce-тест проверяет прежнюю public migration CLI/DB matrix и запрещённые workforce DDL/direct apply без запуска общего make architecture-check.
- Полный `make test` сохраняет обязательный отдельный architecture-check; CI сохраняет его в fast job. Ошибка обязательного этапа запрещает VERIFY_OK.
- Не меняются ни workforce-specific source checks, ни schema/row/prefix/recovery/failure assertions, ни состав CI категорий, ни runtime/permissions.
- Удаляются только два вызова/проверки общего дочернего процесса и ставшая неиспользуемой wcrRunCommand.

Это реорганизация test composition по явно поставленному пункту55, не новая
предметная или schema migration. Существующий WORKFORCE-CANONICAL-RUNNER-001
§§5–7 и принятая verification CI matrix остаются источниками требований.

## Проверка

Новые дублирующие source-string tests не добавляются. Используются неизменённая
реальная workforce matrix, существующие full-harness aggregation/CI tests, а
также динамический make-spy: до исправления вызов дочернего make даёт RED;
после — вся matrix PASS и trace отсутствует. Это отдельный диагностический
прогон, не benchmark. Три обычных before/after измеряются без spy и профайлера.

Primary logs/scripts вне репозитория: `~/.local/state/fmonitor2/issue55-workforce-20260908/`.
Независимый test review оценивает эквивалентность покрытия до изменения,
code review — точный итоговый diff. Full Actions на итоговом head обязателен
перед merge; local-full→CI-full дубль не выполняется по принятой матрице.

## Текущий статус

Исходная запись: baseline/review идут. Последующие факты дописываются отдельно;
эта запись не объявляет GREEN, завершения55 или поставки нового PR.

## Фактические before/after

Before: 41.640с, 39.903с, 39.929с, все PASS.
After: 3.403с, 3.366с, 3.399с, все PASS.
Медианы 39.929→3.399с: экономия 36.531с (91.5%).
Обычные прогоны последовательны на одной машине/PHP8.5.10/одном DB контейнере,
без make-spy и без фоновых локальных тестов. Это не CI benchmark.

Динамический spy-before: после выполнения всей DB/CLI matrix nested make вызван
один раз, возвращён97; workforce verifier падает255 на прежнем assertion общего
architecture gate. Spy-after: полный verifier PASS, trace файла нет — дочерний
make не запускался. Реальные migration CLI не подменялись: используются их
прежние абсолютные PHP argv и отдельная owned DB. Вся matrix/ownership scan
осталась побайтно прежней; diff удаляет ровно17строк.

Independent Gate3 APPROVED до изменения:
`reviews/tests/WORKFORCE-ARCHITECTURE-DEDUP-001.md`; итоговый SHA256 теста
`1d864afb89d9e06762528182073462e15562e247372f104f5ba711c35a1c1be7`
совпадает с заранее reviewed deletion hash.

## Сохранённый обязательный gate

На8599929d: standalone `make architecture-check` — PASS7/7;
`harness_full_aggregation_001_test.php` — PASS, включая nonzero/no VERIFY_OK при
architecture failure; `verification_ci_001_test.py` —9PASS, включая fail-closed
fast/aggregate и состав категорий. PHP lint и diff-check PASS.
Makefile, workflow, CI selector/aggregator и inventory не менялись.

Independent Gate5 APPROVED на8599929dbd76ebfac18fad00ed84b7496ca6e57d:
`reviews/code/WORKFORCE-ARCHITECTURE-DEDUP-001.md`. Reviewed source hash совпадает
с предварительным Gate3. Отдельная test DB после проверок не содержит fixture DB
и task-owned Compose удалён. Следующий commit добавляет только review/evidence;
полный Actions и его CI timing будут приложены к PR на точном опубликованном head.
