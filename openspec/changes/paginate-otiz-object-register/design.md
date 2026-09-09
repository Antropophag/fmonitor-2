## Context

Текущий oracle objects() материализует все строки и current progress до JS фильтрации. История/формулы и смысл общей сводки уже существуют. Новая схема усложнила бы проверенный v23 recovery и все импортеры; для read-only изменения сначала используем существующие источники.

## Goals / Non-Goals

**Goals:** страница в MariaDB, ограниченная гидратация прогресса, точные фильтры и общий финансовый fold с ограниченной памятью.

**Non-Goals:** новая formula/version, изменение баланса/выплат, кэш с неизвестной свежестью, request-time DDL, новая schema frontier, остальные списки.

## Decisions

Owner `app/Otiz/ObjectRegister` принимает actorId/query и проверяет текущие AccessPolicy grants. MariaDB read adapter владеет SELECT и стабильным snapshot чтения count/page/summary. DTO содержит query,page,pageSize,pages,total,rows,summary. Наружу ошибки `REGISTER_QUERY_INVALID`, `REGISTER_PAGE_NOT_FOUND`, `REGISTER_FORBIDDEN`.

NativePremiumNorms предоставляет единый справочник допустимых диапазонов и материалов, используемый и исходными PHP методами, и SQL read adapter для определения наличия нормы. В SQL нет второй денежной формулы; сопоставление нормализованных технических операндов использует данные этого справочника. Общая PHP ObjectEconomy проекция извлекается из objects() и сохраняет fund/deadlinePenalty/state. Требуются тесты паритета PHP/SQL на границах и malformed исходных значениях.

Main query делает count и SQL LIMIT/OFFSET после q/state, order allowlist всегда заканчивается id. Последний snapshot определяется report_date/id, closures учитываются по объекту и по конкретному snapshot. Сводка отдельно проходит компактный unbuffered result и складывает значения тем же PHP владельцем; не собирает массив объектов, не вызывает progress. Глобальная сводка остаётся O(N), но ограничивает память; measured evidence определяет необходимость индекса/агрегации. SELECT не создаёт temporary/persistent таблицы и не меняет facts.

RapidPilotOtiz — только wiring и presentation; текущая экономическая логика удаляется из objects(). GET форма с явным submit работает без JS; shlz select enhancement сохраняет keyboard behavior. Pager использует публичный PilotView::pagination. JS перестаёт фильтровать DOM. Размер50 соответствует соседним реестрам;25/100 доступны явно. Только default и regnumber asc/desc: progress sort потребовал бы вычислять live progress всего набора и не входит в существующее поведение.

Проверки: нормативный публичный seam + HTTP через disposable SelectionHttpFixture, отдельные browser/performance proofs. Architecture baseline не расширяется ради новых HTTP SQL. Source change сохраняет текущие schema/table manifests.

## Risks / Trade-offs

- SQL normalization расходится с PHP → boundary parity tests для UTF-8, whitespace,+, missing/invalid чисел и null.
- Глобальная сводка сканирует все записи → unbuffered fold, измерить30k и планы; без ложного обещания O(pageSize) для итогов.
- OFFSET на последних страницах дороже → measured late-page proof; стабильный id, без смены UI на cursor contract.
- Исторические display/global balance уже различаются → сохранить отдельно в characterization, не чинить деньги в #17.

## Migration Plan

Reviewed код публикуется обычным PR/fullCI. Миграции и переключение стендов не требуются; browser proof в отдельном fixture. Runtime rollback не меняет факты.
