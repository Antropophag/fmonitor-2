# ARCHITECTURE-NATIVE-SELECT-MARKUP — дополнительное независимое ревью

Дата: 7 сентября 2026. Автор detector/test изменений — root-поток; автор этого
ревью не изменял implementation, tests или architecture baseline. База сравнения:
`c02f1a23121058fb0046e4b8fec859c5754ecb6d`.

## Вердикт

**Code: APPROVED. Tests: APPROVED.** Блокирующих замечаний нет.

Новый `PHP_HTML_SELECT_TAG` удаляет из detector view только открывающий или
закрывающий HTML-тег `select`, только внутри PHP quoted string и только когда за
именем следует whitespace, `/` или `>`. Слова с более длинным identifier suffix
не совпадают. Source-файл и строка для fingerprint не переписываются.

SQL-защита сохраняется на той же строке и в том же quoted literal: после замены
HTML-тегов слово `SELECT` в SQL остаётся и продолжает давать
`sql_ownership: new violation`. Новый отрицательный тест прямо использует
`SELECT ... widget='<select></select>'`, поэтому HTML exemption не может скрыть
SQL, содержащий тот же tag. Существующие проверки `UPDATE`, `INSERT INTO`,
`DELETE FROM`, split SQL и same-line SQL не ослаблены. Baseline не менялся.

Сфокусированная независимая проверка:

```text
test_09_public_select_markup_is_not_sql ... ok
test_11_native_html_does_not_hide_sql ... ok
Ran 2 tests in 0.426s — OK
```

`python3 -m py_compile` и `git diff --check` прошли. Авторское evidence полного
набора фиксирует прежние 43 проверки плюс новую, без изменения baseline; это
дополнительное ревью самостоятельно повторило только две непосредственно
затронутые границы.

## Точные идентичности

```text
1984a86eb6527bba5e7776086869f4c3238362dbbe9da3afa365bf40bfdf9d30  tools/architecture/check.py
16f99a3505ca5597b1b36d7a409b7bd20d5c8998e1e927e85131be746c979a9b  tools/architecture/tests/test_php_select_tokens.py
9a67b19242bc1609d00c8a9e923246096b9730a89c988af6390ceb6541b5a6c8  tools/architecture/baseline.json
```

SHA-256 точных diff streams относительно базы:

```text
2ce15d15e1c5a0f170f3236b4c31197cb6e1341fabf5a474058ac3c4d6533cdb  tools/architecture/check.py.diff
b2ae4495e9f04527e7d1c63f85c325f8087154faac9234f44afb46c9405a953a  tools/architecture/tests/test_php_select_tokens.py.diff
519326fa521fe2c84760a0d765c042392ed86c087b784069227b4fe1dc887439  combined.diff
```

Режимы файлов сохранены: detector 0755, test 0644. Вердикт не является
`VERIFY_OK` или заявлением production readiness.
