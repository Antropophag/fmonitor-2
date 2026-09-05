# Gate 2 RED evidence: xref-stream grammar

- Дата: 2026-09-05
- Исполнитель: отдельно назначенный RED author `/root/pdf_grammar_red`
- Основание: Gate 5 review `7a2675a96046df435b8b90b3ef34759ab24f758b`
- Проверенная production база: `45eb644b784e10cffa95068af2f3710e6d0561b5`
- Scope: только deterministic PDF corpus, executable test и этот append-only record; production/spec/config не изменялись.

## Fixtures и независимость

`AssignmentOrderOriginalPdfCorpus::xrefStreamGrammar()` каждый раз заново
строит objects, object offsets, xref rows, `/Length` и `startxref`. Поэтому
ожидаемый RED не зависит от устаревших offsets после изменения dictionary или
framing. Малые positive controls фиксируют unfiltered stream с LF, CR и CRLF
границами и единственный разрешённый direct `/Filter /FlateDecode`.

Негативные fixtures изолируют утверждённые fail-closed правила:

- `/Filter [/ASCIIHexDecode]` и `/Filter 99 0 R` не являются единственным
  разрешённым direct name;
- payload без `endstream` и payload с дополнительными bytes перед `endstream`
  не имеют немедленного обязательного marker;
- dictionary с двумя одинаковыми `/Length` неоднозначен и не содержит ровно
  одну запись.

## Доказанный RED

Команда:

```text
php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
```

Завершилась exit `255` на новой общей assertion. Все positive controls были
выполнены раньше неё и приняты как `PASSIVE_PDF`. Exact actual negative map:

```text
filter_array=PASSIVE_PDF
filter_indirect=PASSIVE_PDF
missing_endstream=PASSIVE_PDF
extra_bytes_before_endstream=PASSIVE_PDF
duplicate_length=PASSIVE_PDF
```

Exact expected status для каждой записи — `INVALID_PDF`. Это intended RED
только из-за пяти наблюдаемых parser gaps; fixture setup и syntax checks GREEN:

```text
No syntax errors detected in tests/Support/AssignmentOrderOriginalPdfCorpus.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
```

## Exact hashes до commit

```text
61ad41280463bf00db819c97c4287e15dc45907606d1f9a4753b2b162cd7542f  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
f86be607dec0a8ade356107204c207f4dc25bbd2620c6e4b2cc49ad77264d7cd  tests/Support/AssignmentOrderOriginalPdfCorpus.php
6c0edebdc1e2c99ec8c342cdece4c09bc0f3c1c9cd2254044e9353df0725dcdf  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
38d6600cfdfd706417343fe92fd0ca24e2e2f338847ba7b5e2afd7902a24fbc1  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-pdf-parser-v3.md
```

Production parser hash совпадает с reviewed `45eb644`; production seam не
редактировался. Fresh independent Gate 3 обязателен до minimal GREEN.
