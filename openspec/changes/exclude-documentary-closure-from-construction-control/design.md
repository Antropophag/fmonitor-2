## Context

См. `proposal.md`. Yii2-маршрут уже принадлежит `ChecklistController`, а чтение
очереди — модулю `InspectionEvidence`. Текущая проекция выбирает все `working`
cases и лишь помечает полностью завершённые строки для клиентского фильтра, из-за
чего начало документарного закрытия остаётся в переданном браузеру рабочем наборе.

## Goals / Non-Goals

**Goals:**

- применить один server-side предикат к count и page selection;
- сохранить Yii2 controller/view простыми адаптерами;
- доказать публичным HTTP-тестом состав выдачи и read-only историю.

**Non-Goals:**

- менять completion lifecycle, состояние case или данные ОТиЗ;
- удалять UI-control отдельным визуальным рефакторингом;
- изменять schema, `rapid-pilot` или deployment.

## Decisions

Owning module — `InspectionEvidence`; persistence owner — существующий Yii DAO
read boundary. Предикат «нет `pto_act`» применяется одинаково в COUNT и SELECT.
Это выбрано вместо client-side скрытия, потому что pagination и total обязаны
описывать рабочие задачи, а исключённые данные не должны передаваться браузеру.

Контроллер и route config не меняются: они уже являются Yii2 public adapter.
`rapid-pilot` остаётся историческим oracle и не получает новую логику. Новых
зависимостей и schema migrations нет; architecture inventory меняется только при
добавлении обязательного focused test.

## Risks / Trade-offs

- [Разный предикат в COUNT и SELECT] → одна и та же SQL-формула используется в
  обоих запросах и проверяется границей страниц.
- [Неправильное толкование завершения] → исключение начинается на `pto_act`, как
  прямо требует этап документарного закрытия в issue #39; декларация не является
  prerequisite исключения.
- [Скрытая запись при чтении] → HTTP-тест сравнивает факты до и после повторных GET.

## Migration Plan

Schema/data migration не требуется. Изменение разворачивается вместе с Yii2
кодом; rollback возвращает прежний read predicate и не требует восстановления
данных, поскольку срез ничего не записывает.
