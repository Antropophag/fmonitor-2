---
status: accepted
---
# Один владелец планирования инспекции

При переносе действующего пилота на Yii2 по #76 назначение инспекции принадлежит
InstallationProcess::YiiInspectionPlanning::scheduleInspection. Оно объединяет
актуальный допуск, выбор дела/инженера, schedule и append-only event одной Yii DAO
транзакцией; HTTP отвечает за форму, CSRF и возврат, но не владеет фактами.

Уникальная тройка дело/инженер/дата сохраняет текущую совместимость и успешный
повтор. Отдельная операция не превращается в изменение объекта, фактическую
инспекцию или новую политику периодичности. Инъекция persistence/clock в composition
сохраняет модульную границу; второе mysqli-соединение внутри операции запрещено.

Это намеренно новый state-changing public seam, который checker должен увидеть.
После независимого архитектурного review baseline получает ровно его запись;
hotspot/SQL/dependency allowances не расширяются. Существующий rapid handler
остаётся временным oracle/stand adapter до общего переключения по inventory,
после переключения удаляется. Независимый architecture design review2026-09-10 APPROVED ровно этот seam;
см. reviews/tests/YII2-OBJECT-QUEUE-001.md. Gate3 тестов ещё требует исправлений.
