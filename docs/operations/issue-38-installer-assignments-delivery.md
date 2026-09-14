# Delivery №38 — закрепления в справочнике монтажников

- Owner assignment: 2026-09-14, PR-ready от post-№40 main `cf0299d8`.
- Root author: scope, normative/OpenSpec artifacts and RED tests.
- Executor: pending separate gpt-5.6-sol/low package.
- Gate 3: independent `APPROVED` after complete correction matrix; record
  `reviews/tests/YII2-INSTALLER-DIRECTORY-ASSIGNMENTS-001.md`.
- First Gate 5: `CHANGES_REQUESTED` for mixed native/fallback classification and
  incomplete snapshot integrity; both corrections implemented. Renewed Gate 3 and
  post-approval fixture/test delta reviews: `APPROVED`. Final Gate 5 correction
  review: `APPROVED` for candidate `478b4f419f5593fd6805cbdadb4507e0741adbc964bc6abb0cb6d410118e7d2b`.
- Authoritative owner: append-only `fm2_assignment_order_applications`, latest
  `application_sequence` per installation case.
- Legacy compatibility: registered-order read only for cases without applications.
- RED: `php tests/Yii2/yii2_installer_directory_native_assignments_001_test.php`
  reached successful native selection/upload/application and failed only because
  directory HTML omitted `TEST-4512`; retained outside checkout as
  `issue-38-red-20260914.txt`.
- Focused GREEN: native end-to-end regression, existing directory HTTP, 16 change
  verification tests, 59 architecture guard tests, runtime storage, strict OpenSpec
  validation and diff check. Exact final source evidence is refreshed after records.
- Final source snapshot, PR and CI: pending; `UNKNOWN` is not approval/GREEN.
