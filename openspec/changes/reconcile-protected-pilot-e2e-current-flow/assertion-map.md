# Карта защищённых assertions

Исходный SHA-256 `pilot_e2e_flow_001_test.php`:
`8f0d3626401b4a638bb56be40e17129626f1fc320ceeda6be798e3079294909b`.

| Старый блок | Решение | Текущая замена |
|---|---|---|
| local actor admission, revoke, inactive/missing identity | retained | `local_rbac_objects_route_admission_001_test.php` и browser login |
| universal semantic queue без таблицы/фильтров | superseded | текущая `/pilot/objects`, поиск объекта и переход в карточку |
| combined legacy order+appendix PDF | superseded representation | optional inline template и exact accepted-original download |
| manual registration number/endpoint/status | superseded | original confirmation/correction, без номера 1С ДО |
| отдельный apply/reapply UI | superseded | card POST `open_confirmed`, application+opening атомарно |
| method/path/media/body/Origin/CSRF checks | retained | focused original/opening/checklist HTTP contracts |
| stale/concurrent/replay/no-partial writes | retained | focused original and confirmed-opening contracts |
| exact GET/HEAD bytes, media, disposition, read-only | retained and endpoint-adjusted | `original_upload_http_flow_001_test.php`, `confirmed_original_opening_http_001_test.php` и `original_history_download_001_test.php` сохраняют read-only/history contracts; browser проверяет exact current-original GET/HEAD headers и bytes/hash. Не сохраняемый optional template выдаётся только POST: browser проверяет его 200/media/inline/length/bytes/hash/no-download, а `generated_template_passive_pdf_manual_pilot_test.php` — passive semantic profile. Старое GET/HEAD требование относилось к superseded persisted combined artifact и не переносится на новый POST-only template endpoint |
| legacy/workforce/foreign-decoy preservation | retained | `pefProtectedAuthoritySnapshot()` фиксирует все строки synthetic `fm_maintable`, workforce catalogue/runs/metadata до browser journey и сравнивает их через fresh connection после 100%; отдельный sibling sentinel проверяется byte-identical и удаляется только fixture cleanup. `confirmed_original_opening_001_test.php` отдельно доказывает rollback пяти command-owned business tables для rejected opening; final current-flow counts не подменяют этот negative contract |
| checklist progress/photos | retained and updated | browser journey доказывает 41 монтажный пункт, 7 фото, 85% и reload принятой проекции; `checklist_bulk_online_sequence_manual_test.php` отдельно доказывает durable pending до первого ответа и продолжение predecessor sequence после reload |
| completion | expanded | акт ПТО + обязательная декларация, 100% после reload |
| fresh connection durable projection | retained | final MariaDB reconnect and exact counts/state |

Уточнение route oracle: canonical rapid root — `/`, он отвечает 302 на
`/pilot/objects`. `/pilot/` остаётся compatibility shell с 200 и ссылкой на
объекты; требование redirect для `/pilot/` не вводится.

Старый RED актуального runtime: `actor18 admission preserves approved semantic-list fixture`.
Он был behavioral mismatch старого representation oracle, а не setup failure и не
authorization failure. Отдельный clean-checkout прогон `6aa39aa` имел более ранний
setup blocker — отсутствующий checkout-local TCPDF; его нельзя использовать как
RED новой E2E-семантики до provisioning dependency.
