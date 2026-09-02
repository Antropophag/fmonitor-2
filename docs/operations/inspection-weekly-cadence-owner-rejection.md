# Owner decision — weekly inspection cadence rejected

Date: 2026-09-02  
Decision: **REJECTED**

Владелец продукта полностью отверг утверждение, что инспектирование должно
проводиться не реже одного раза в неделю. Следовательно:

- weekly cadence не является product requirement;
- автоматические due dates `opening/last inspection + 7 calendar days`
  запрещено выводить из существующих документов;
- overdue по недельной периодичности не существует;
- historical repository text и ссылка на протокол не заменяют owner approval;
- scheduling, reschedule и cancellation требуют отдельных product semantics,
  но не должны обеспечивать недельный invariant.

Решение исправляет ошибочно повышенную до product truth интерпретацию. Оно не
отменяет саму capability фиксации состоявшейся инспекции, прогресса и
наблюдаемого состава.
