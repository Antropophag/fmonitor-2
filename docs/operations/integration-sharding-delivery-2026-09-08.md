# Integration sharding — ход поставки

База: main2009c9bed8003cff492b70befeb67d4f582229e5.
Owner approval: `integration-sharding-owner-decision-2026-09-08.md`.
Только стандартная matrix двух jobs и детерминированное деление validated списка.
Кеш fixtures и дальнейшая микрооптимизация не реализуются.

## Проверки до реализации

Другой агент подготовил расширение существующего verification_ci_001_test.py.
Первый RED:15тестов,5падений из-за отсутствующих --shard/Make forwarding/matrix.
Существующие проверки full/docs-only и обязательной агрегации сохранены.
Последующая независимая проверка тестов и точные hashes фиксируются в review.

Реальное доказательство после CI: обе части имеют success, объединение их
VERIFY_TIMING совпадает с полным integration inventory, каждый путь ровно один
раз и exit0. Полный список читается с того же exact head. Никакой новый receipt
framework или общий writable artifact для jobs не вводится.

Baseline для времени: run34263647931 —177файлов/546.099с, integration11:17,
workflow11:55, сумма elapsed jobs18:39. Модель двух VM даёт около4мин экономии
ожидания ценой повторной подготовки. Это не after benchmark; фактический
результат и elapsed runner time будут приложены к PR после завершения Actions.

Локальный stand/БД не используются и не изменяются. Логи/первичные measurements
хранятся вне репозитория: `~/.local/state/fmonitor2/issue55-sharding-20260908/`.

Первое Gate3 review CHANGES_REQUESTED: Make fixture из одного файла не доказывала
передачу selector; php-r probe не оставлял trace; hardcoded177 мешал росту inventory.
Все три исправлены автором: multi-entry subset, отдельный DB_TRACE и динамическое
сравнение с полным validated списком. RED15:5intended failures/10PASS на hash
`66a189937d55b9f0a1d9701cc8bd7ab3f89f02453657f17eb96683d89a22bc26`.
История verdicts сохраняется в `reviews/tests/INTEGRATION-SHARDING-001.md`.
