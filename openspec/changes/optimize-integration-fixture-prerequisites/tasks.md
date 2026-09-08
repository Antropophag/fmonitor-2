## 1. Измерения и контракт наблюдателя

- [x] 1.1 Сохранить три baseline прогона тяжёлого теста и фазовый профиль fixtures; свести длительности в operational report.
- [x] 1.2 Написать независимый DB regression точной формы и свежести snapshot, получить RED и отдельный Gate3 review.

## 2. Пакетное чтение

- [ ] 2.1 Реализовать пять свежих metadata запросов с прежней нормализацией; regression GREEN и эквивалентность старому snapshot на полном matrix.
- [x] 2.2 Подключить helper только в schema snapshot; три after прогона, сохранённые assertions, schema/data isolation и cleanup.

## 3. Поставка

- [ ] 3.1 Получить независимый Gate5, focused GREEN и architecture PASS; зафиксировать экономию и остаток issue55.
- [ ] 3.2 Подготовить PR с полным зелёным Actions на итоговом head; отдельно показать test time, wall-clock job и runner minutes.
