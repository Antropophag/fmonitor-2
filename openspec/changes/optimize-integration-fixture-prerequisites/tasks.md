## 1. Измерения и контракт наблюдателя

- [x] 1.1 Сохранить три baseline прогона тяжёлого теста и фазовый профиль fixtures; свести длительности в operational report.
- [x] 1.2 Написать независимый DB regression точной формы и свежести snapshot, получить RED и отдельный Gate3 review.

## 2. Пакетное чтение

- [x] 2.1 Реализовать пять свежих metadata запросов с прежней нормализацией; regression GREEN и эквивалентность старому snapshot на полном matrix.
- [x] 2.2 Подключить helper только в schema snapshot; три after прогона, сохранённые assertions, schema/data isolation и cleanup.

## 3. Поставка

- [x] 3.1 Получить независимый Gate5, focused GREEN и architecture PASS; зафиксировать экономию и остаток issue55.
- [x] 3.2 Подготовить итоговый пакет для PR: зарегистрировать verifier в integration, сохранить baseline/after и команду полного CI; проверка — согласованный inventory и готовая PR description.

Полный зелёный Actions на точном итоговом head обязателен перед merge. Это внешнее
доказательство фиксируется в PR после прогона; длительности test/job/workflow и runner
minutes сравниваются отдельно. Локальное завершение checklist не заменяет этот gate.
