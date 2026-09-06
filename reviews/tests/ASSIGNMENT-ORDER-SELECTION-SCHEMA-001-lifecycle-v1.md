# Selection schema lifecycle Gate3 v1

Reviewer `/root/selection_lifecycle_gate3`, новый gpt-5.6-sol low/fork none;
author root. Verdict **CHANGES_REQUESTED**, reviewed commit ae049f0.

Оба permission fixtures давали SELECT-only principal. Он не видит все
REFERENTIAL_CONSTRAINTS, поэтому approved registry public completion false.
Это не valid prerequisite для проверки denied CREATE или successful repeat.
Нужны SELECT,REFERENCES с подтверждением registry completion и реального DML
denial до target action. Остальные scope findings не блокируют.

Original archive `selection-schema-lifecycle-red-0dpq1lhg` под external
`/Users/antropophag/.local/state/fmonitor2-verification/`, manifest SHA256
278221d5c316ae8edab766458dbbbed6371a189b09afb40e15a5ae6f8005b346.
Исходные20 lifecycle RED guards не доказывают validity post-guard expectations;
поэтому этот capture не является одобрением ошибочной fixture authority.
Reviewer файлов не менял. Поправка требует отдельного exact Gate3 verdict.
