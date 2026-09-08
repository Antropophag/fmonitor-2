# Protected current E2E — focused evidence

Дата: 2026-09-07. Scope: synthetic disposable DB/filesystem и headless Chrome;
manual-pilot stand и object 966 не использовались.

## Результат

- Browser-only diagnostic прошёл очередь, composition, POST-only inline template,
  две immutable original revisions, exact original GET/HEAD, distinct opener,
  41 checklist item, 7 фото, 85%, акт ПТО, декларацию и reload 100%.
- Final browser trace завершился без 4xx/browser errors и сохранил accepted
  checklist revision chain до 55, включая семь `section_completed`.
- Полный protected verifier на pre-readonly bytes был GREEN. После добавления
  GET/HEAD no-write assertion bootstrap `final-4` выполнил protected child с
  exit 0, пустым stderr и PASS (caller прошёл `pdbContract` и продолжил до
  line 149).
- Весь bootstrap caller не GREEN: после protected child он остановился на
  независимом post-spawn CSS adversary bind deadline. Этот failure не превращён
  в skip и не выдаётся за E2E failure или `VERIFY_OK`.

## Evidence

- `runtime/protected-current-browser-diagnostic.log`, SHA-256
  `6e816654002481f40da0af185f363977093f5a5577046251eb65f923eb2e88fa`.
- `runtime/protected-current-e2e-full-pre-readonly.log`, SHA-256
  `9f0d3e75579b58f92927c4ae39958d181eed7bf2b49cee48380738999be950fb`.
- `runtime/protected-current-bootstrap-final-4.log`, SHA-256
  `15345f96643b7966fc3be3b7d51d2d70bdb21c1ecd74c9bcf3fdf2d6401731ad`.
- Private evidence directory is mode 0700; logs, result and screenshots are 0600.

## Frozen artifact hashes

- `pilot_e2e_flow_001_test.php`: `71d02b054b42ef63483ce49d4494d21f08e4b62b7362f8eb1ea71e04a2590deb`
- `pilot_current_flow_browser.cjs`: `9193e3454f758af023d8e40499139ec9b122d1f876a906a8fd03c00bd469eae7`
- `SelectionHttpFixture.php`: `b59339b637336017306aa97ac8a23957209d86c42351a4ca4034adddacb9108f`
- `pilot_demo_bootstrap_001_test.php`: `d90abbf0959268f63854ce0304544d2aeacf5646fd18e6d416849ba1eaf87f34`

Historical protected source SHA
`8f0d3626401b4a638bb56be40e17129626f1fc320ceeda6be798e3079294909b`
remains recorded in `assertion-map.md`.

## Follow-up bootstrap diagnosis

Private normal-start instrumentation (copied launcher only; production logging was
not changed) observed the repeated vector `queue=401`, `card=503`, `form=401`,
`foreign=503`, while pilot/SHLZ CSS were 200, CSS HEAD was 200, unknown asset was
404 and graph comparison was true. This classifies the remaining bootstrap work as
v4 fixture/local-identity drift, not CSS or timing. Diagnostic DBs, processes and
copied trees were removed; redacted private logs remain mode 0600.
