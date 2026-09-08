# Чистый запуск — controlling owner clarification

Owner2026-09-06: **«да нет никаких исторических пдф, вообще никаких исторических
данных нет. не понимаю, зачем ты постоянно прорабатываешь какую-то историческую
совместимость»**.

## Scope correction

Запуск выполняется без исторических процессных данных/PDF. Перенос истории,
registry-aware переделка старого prepare writer, поддержка смешанного N−1/N
контура и backward-compatible artifact storage не являются launch blockers.
Их вывод из прежних handoff был ошибочным применением migration planning к
чистому запуску. Более ранние operational reviews сохраняются как records своего
scope, но больше не задают эти prerequisites для текущего запуска.

Новый процесс имеет один production writer выбора состава. Старые prepare/
registration/direct signed-original paths не должны оставаться альтернативными
входами нового портала; их перевод на registry ради совместимости не нужен.
Fresh installation связывает только согласованные новые application seams.

Обязательны: оба selection modes; PDF по запросу без хранения, дата последнего
формирования и audit; подписанный original/append-only correction; применение
состава и отдельное открытие; native public golden path; full exact-SHA VERIFY_OK,
CI и clean deployment/restart/persistence. Delivery gates и запреты PR10/ранней
CI publication сохраняются. Append-only относится к новым фактам после запуска.
Отсутствие истории не разрешает стирать произвольные DB/volumes без ownership.

## Revised critical path

1. Закончить on-demand PDF/date/audit contract без artifact storage.
2. Утвердить и реализовать selection command на уже одобренных schema/registry,
   затем связать original read и locked composition validation с новым источником.
3. HTTP selection/PDF/original, применение состава, отдельное открытие.
4. Fresh bootstrap с fictional TESTUSER; отключённые старые entry paths; полный
   exact-SHA VERIFY_OK, затем разрешённые CI/deploy/restart/golden-path и audit.

Уже завершённые disabled schema и registered reader переиспользуются. Новые
матрицы исторической совместимости, legacy writer migration, template file
history или версионированное PDF storage не создаются.
