# OTIZ final integrated correction — one root-authored test delta

This delta addresses the final reviewer's complete blocker list in one candidate.
It does not introduce a new financial writer or expand #70 to all of #76.

- Retained runtime: extract the actual complete/discipline/reverse form UUIDs;
  require distinct canonical values. Submit missing/malformed IDs to all three
  routes and require error/no financial facts, then retain A02/replay checks.
- Browser: click every newly rendered financial navigation link and return to the
  real snapshot. Click/download XLSX and inspect workbook contents independently
  using PharData; accepted snapshot/objects/allocations/issues remain unchanged.
- HTTP: follow the existing reverse-error redirect and require a functioning
  message page with no facts. A separate historical fixture verifies bp85%,
  admission/exclusion notes, legacy-compatible KTU1.00, and date/all three money
  components (70000/20000/10000) in history without financial writes.

The intermediate Yii navigation pages are read-only and expose working snapshot
links. Publication/calculate/accept remain the existing native behavior outside
#70; no dead action forms are introduced. The base financial alias preserves the
already reviewed reverse error URL. This bounded scope was agreed with the final
reviewer before the correction.

Observed RED commands (parent exit255):

1. `php tests/Yii2/yii2_otiz_settlement_001_test.php`: reverse-error return expected
   200, actual404. Log `/tmp/fm2-final-http-correction-red.log`.
2. `php tests/Yii2/yii2_otiz_settlement_browser_001_test.php`: real navigation click
   expected200, actual404 at `/pilot/otiz/objects`. The assertion checks the actual
   response directly rather than timing out looking for a missing heading.
   Log `/tmp/fm2-final-browser-correction-red.log`.
3. `php tests/Runtime/runtime_settlement_compatibility_001_test.php`: built-image
   packaging/platform/login completed, then the retained form lacked operationId.
   Log `/tmp/fm2-final-runtime-correction-red.log`. Tests-only mount and DML runtime
   remain; owned container/image/database/user are cleaned by the harness.

Previous owner/browser/recovery/packaging GREEN remains historical evidence for
its exact source. These stronger tests are not claimed GREEN until implementation
corrections and independent final review complete. Test author: root; production
corrections remain assigned to subagents.
