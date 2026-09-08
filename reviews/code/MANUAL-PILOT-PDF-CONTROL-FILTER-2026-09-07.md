# Независимый review PDF preview и фильтра стройконтроля

Дата: 2026-09-07. Reviewer `/root`, не автор проверяемых изменений.
Авторы: PDF — `/root/architecture_diagnosis`, фильтр — `/root/auth_review`.
Base HEAD `c02f1a23121058fb0046e4b8fec859c5754ecb6d`.
Test/code verdict для обоих fixes: **APPROVED**, блокирующих замечаний нет.

## PDF-шаблон

Изменён только успешный template Content-Disposition с attachment на inline.
Form по-прежнему открывает отдельную вкладку; UTF-8 filename, MIME, length,
вызов template application, PDF validation, audit и отсутствие template storage
сохранены. OriginalHistory download остаётся attachment. Это соответствует
прямому новому решению владельца; старое attachment-ожидание было ненормативным
для нового UX и не переносится на оригиналы.

Независимо выполнен `selection_http_flow_001_test.php`: exit0, включая native
selection/replay/replacement, реальные PDF bytes/содержимое, metadata-only audit
и отсутствие сохранённого шаблона. Root дополнительно повторил synthetic
headless full Chrome flow: popup PDF200/inline, downloads0, errors[]. Скриншот
`feedback-preview-and-return-20260907/template-opened.png` inspected: видны
страницы распоряжения в настоящем PDF viewer, не пустая вкладка.

## Стройконтроль

Завершение берётся из двух native completion facts ПТО+декларация для того же
case, а не из пустого legacy ptoactdate. Существующий renderer/JS checkbox фильтр
использует исправленный boolean; финансовые/процессные факты не меняются.
Одного ПТО недостаточно. Тип SQL boolean проверяется перед отображением.

Независимо выполнен `construction_control_completed_filter_001_test.php`:
exit0. Public queue→renderer→headless browser показывает, что completed working
case скрыт по умолчанию и виден при включении toggle; случай без декларации
остаётся видимым. Факты fixture сохраняются. Root не изменял объект966.

## Exact SHA-256

```text
04579710161990afdc5626847d71c92defbbbfa5ea623680e0bf0892d4ec7ba0  app/PilotHttp/FreshOrderHttpHandler.php
70c90aeb2b79a9dda02d310ae09f3b1bccf3bfd7e8343a1e73ac6ea76bee8eed  tests/AssignmentOrderComposition/selection_http_flow_001_test.php
6099cac5eab80cdc94c3f363db94d71ede025ebf53c3853f714f8a027bbaf322  app/PilotHttp/MariaDbConstructionControlQueue.php
4c5a0c7311cb6e8b143d71f392e6bb3db7d995f6bd9b0fdef678257f1dc5beae  tests/InstallationProcess/construction_control_completed_filter_001_test.php
98a05b9ffb94f82383f3ed6ab0b55275279c8fdde176df567a71774bd1ab79b5  tests/Support/construction_control_completed_filter_browser.cjs
```

Весь public browser golden дополнительно дошёл до100% с сохранением9/9 pending
отметок и возвратом initial/correction upload в карточку. Это integration evidence,
но не подмена отдельного review redirect и не full VERIFY_OK/production claim.
