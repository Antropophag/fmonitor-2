# Object actual dates column — independent code review

Reviewer: `/root/otiz_ui_review`, not an author of code or tests.
Verdict: **APPROVED**, 2026-10-30.

The new `Факт` column uses durable facts only: `actual_start_date` from installation
opening and the current PTO fact date after the highest versioned correction. Planned
dates and declaration dates cannot substitute for missing actual dates; unknown values
remain `—`. The completion read is batched for the visible queue cases, relies on the
canonical unique case/fact root, fails closed on malformed or duplicate evidence, and
does not write facts or schema. Existing status/progress/count/filter logic is unchanged.

Reviewer reran `yii2_object_actual_dates_20261030_test.php` on port 33433: PASS.
PHP lint and `git diff --check` pass. Full local suite/CI was not run under the
owner-authorized short production cycle.

## Verification registration follow-up

The administrative delta is also approved. `yii-object-read-presentation` now owns
`MariaDbYiiObjectQueueProjection.php` and routes the new focused regression through
that capability; `tools/verification/suites.tsv` registers the same test as a DB
integration check. These entries match the changed read boundary, add no exemption or
policy weakening, and do not change runtime behavior or the hash binding below.

## Exact runtime binding

- `d1e8bf5ea2595531f924df6593519cec08891356bbb01b38ad8580eb53c9838b` `app/InstallationProcess/MariaDbYiiObjectQueue.php`
- `299cfef37c348f6ef06388d687ec7ae7327a4eff29b30f1f3be9c3e3b4e8f597` `app/InstallationProcess/MariaDbYiiObjectQueueProjection.php`
- `e2cf69b183a904db3b886ab6f5513050ce353d7f53aa5140a3008480dd061847` `app/YiiRuntime/Views/objects.php`
