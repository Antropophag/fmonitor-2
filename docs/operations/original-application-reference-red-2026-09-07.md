# Original application reference — native RED

Source HEAD185d95c616266260aafa8e874acc9b95d507c3d5; uncommitted test-only additions
for ASSIGNMENT-ORDER-ORIGINAL-APPLICATION-REFERENCE-001 v0.1. Gate1 APPROVED in
reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-APPLICATION-REFERENCE-001-gate1.md.

Команда (synthetic DB, PATH Homebrew/Docker):
`FMONITOR_TEST_DB_HOST=127.0.0.1 FMONITOR_TEST_DB_PORT=23306 FMONITOR_TEST_DB_ADMIN_USER=root FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/AssignmentOrderComposition/original_application_reference_001_test.php`.

14 cases (7 × prefix0/25) прошли real public selection→accepted original setup,
затем intended RED: `RED_ASSERTION: original application reference factory missing
after healthy native setup / Expected: true / Actual: false`. Exit1, все14
CLEANUP_OK. Production seam отсутствует, production changes не выполнялись.
Raw external log:
/Users/antropophag/.local/state/fmonitor2-verification/original-reference-20260907/red.log.

SelectedOriginalFixture получил optional prefix с прежним default0. Existing
`tests/InstallationProcess/selected_original_binding_001_test.php` regression
PASS для frozen-clock и production constructors. Default behavior не изменено.
Новые native tests и worker требуют независимого Gate3 до implementation.

Before Gate3 verdict test review feedback strengthened caller ownership: sentinel
write stays uncommitted across matched/repeated, changed and corrupt unavailable
guards, caller rollback proves no hidden commit. Worker observation uses real
original writer query `SELECT id FROM <case table> WHERE id=4512 FOR UPDATE`,
not selection-writer query. Final REDv3 repeats all14 healthy setups, intended
missing-factory failures and cleanups, exit1. Raw sibling `red-v3.log`.
Final test SHA256 aad4743bef57e1852a3245f6771df42224bcd7843e976bebca749f8f6fde9e93.
Worker SHA256042f26acbe469048cb76b6b71869a1791f94a6df1e0d4a11efffa0ee08434656.
Fixture SHA256b4ec647e49651ddfacfb16052fd43152e3c6b0ade19cde910992c16e842fbaa5.

Gate3 followup: live native connection with no selected database explicitly
requires unavailable, DATABASE() remainsNULL and idle, close in finally.
Final REDv4 exit1; all14setup/cleanup/intended assertions, raw `red-v4.log`.
Test SHA256 e61aae78ca06f11b82b658152608b1cb976cacbdb77de2c949344a64b46aec56; other two hashes unchanged.
