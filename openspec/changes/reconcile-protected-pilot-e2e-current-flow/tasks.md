## 1. Зафиксировать защищённый oracle

- [x] 1.1 Записать SHA-256 текущего `pilot_e2e_flow_001_test.php` и составить точную таблицу assertions `retained / superseded / replacement`; проверить, что каждый удаляемый assertion имеет ссылку на более новое решение владельца.
- [x] 1.2 Зафиксировать RED актуального runtime против старого protected flow отдельно от setup failures; проверить, что лог не содержит secrets/owner data и failure не превращён в skip.
- [x] 1.3 Получить независимый test-review таблицы и предлагаемого protected diff до интеграции; дополнительное owner product approval не запрашивать, поскольку manual-pilot stabilization и direct-opening decision уже авторизуют reconciliation.

## 2. Перестроить основной E2E-маршрут

- [x] 2.1 Обновить синтетические роли/fixtures для uploader, opener, viewer и construction-control engineer; проверить точное разделение capabilities и отсутствие standalone composition apply у opener.
- [x] 2.2 Заменить старую universal-list/manual-registration подготовку на текущую очередь и выбор состава; проверить search/filter/card navigation и отсутствие старых representation assertions.
- [x] 2.3 Добавить необязательную генерацию inline PDF-шаблона через текущий POST endpoint с точными disposition, length, bytes/hash, passive semantic markers и no-download; проверить, что template bytes не становятся original revision. Exact GET/HEAD сохранить на current original download endpoint, не приписывать его несохраняемому шаблону.
- [x] 2.4 Провести initial original upload/confirmation и correction с возвратом в карточку; проверить обе immutable revisions, exact current download, actor/date и отсутствие application/opening facts после GET/upload.
- [x] 2.5 Провести прямой POST `open_confirmed` из карточки; проверить trusted identity/CSRF, UUID/order/current revision/current sequence/date, один application, `installation_opened_from_original`, opening fields и template association в одной успешной операции.
- [x] 2.6 Выполнить checklist batch до 41 пунктов, 7 photo sections и 85%; проверить pending/reload/accepted revision chain, error visibility и отсутствие подтверждения rejected operations.
- [x] 2.7 Зафиксировать акт ПТО и обязательную декларацию до 100%; проверить default/editable dates, role checks, correction history и 100% после reload.

## 3. Сохранить отрицательные и append-only гарантии

- [x] 3.1 Перенести retained method/path/body/media/Origin/CSRF/forged-actor assertions на текущие endpoints; проверить точные 4xx/Allow/redirect и zero domain delta.
- [x] 3.2 Сохранить role-denial matrix для read/upload/open/checklist/completion; проверить, что broad viewer не получает mutation controls, а opener не получает standalone apply.
- [x] 3.3 Сохранить invalid date, stale revision/sequence, concurrent/replay и post-application missing-template cases; проверить exact replay и полный rollback applications, attempts, events, opening и association.
- [x] 3.4 Сохранить exact current-original download, HEAD/GET read-only, template POST inline/no-download, legacy/workforce/foreign-decoy preservation и redacted infrastructure responses; проверить byte-identical snapshots.
- [x] 3.5 Удалить только superseded expectations: старый semantic queue, manual 1С ДО registration, registration endpoint, separate apply CTA, old labels и split appendix; проверить их отсутствие source assertions и отсутствие возврата runtime к старому поведению.

## 4. Bootstrap и интеграционное доказательство

- [x] 4.1 Обновить bootstrap caller на полный canonical schema/current fixtures и обязательный protected verifier; проверить clean disposable DB setup и отсутствие skips.
- [x] 4.2 Добавить preflight checkout-local TCPDF 6.11.4 и существующих headless browser prerequisites; проверить, что missing dependency классифицируется как setup failure до behavioral test.
- [x] 4.3 Запустить protected verifier и bootstrap caller на изолированной DB; сохранить GREEN log, exact protected SHA и cleanup evidence.
- [x] 4.4 Получить независимый code/test review final diff; проверить APPROVED verdict, сохранность retained matrix и отсутствие runtime/baseline changes.
- [x] 4.5 Запустить полный `make verify` на чистом exact SHA; только literal `VERIFY_OK` закрывает этот change, а deployment/restart/golden/CI readiness остаются отдельными общими gates.
- [x] 4.6 Reconcile demo bootstrap verifier с canonical19/shared-namespace/local-actor prerequisites: exact catalogue, roles/grants, factual `status`, current selection persistence и явная superseded mapping вместо manual registration; не ослаблять nonce/foreign cleanup/permission assertions. Verification: author static checks, затем отдельный DB run и independent review.
