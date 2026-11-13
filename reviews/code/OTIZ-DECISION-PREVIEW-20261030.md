# OTIZ decision preview — independent final review

Reviewer: `/root/otiz_preview_review`, gpt-5.6-sol / low; not an author of the
contract, regression test, or runtime implementation. Root authored all three
under the declared bounded established-behavior correction route permitted by
`AGENTS.md`.

Verdict: **APPROVED** for the owner-authorized bounded production delivery,
2026-10-30. This is not overall CI or architecture-check GREEN.

## Findings

No blocking finding remains. Before implementation, review identified two test
coverage gaps: the regression did not prove that co-worker allocation rows remain
visible on affected objects, and it did not exercise the reported object-group
entry path. The final test covers both, including exact unchanged co-worker
amounts, four scoped rows, and both object- and employee-group entry paths.

## Reviewed behavior

Numeric employee identifiers are normalized back to strings before strict recovery
matching. Successful preview and stale-revision recovery therefore reopen only the
submitted employee editor and retain decision, reason, operation ID and expected
revision. Preview HTML carries explicit employee/object context; JavaScript resolves
that context through the existing editor map and has no first-editor fallback.

Decision preview selects the saved calculation objects in which the submitted
employee participates, retains every recipient row on those objects, excludes the
unrelated object and recipient, and derives displayed payable/reserved totals from
those scoped rows. Deduction preview retains its exact object context. Preview uses
the existing read/preview seams and does not invoke a save command or alter durable
calculation revisions.

The final real Yii auth/DB browser test is GREEN on the exact runtime hashes below.
It covers numeric employee `202` from the object grouping across two shared objects,
co-workers `101` and `303`, unrelated employee/object `404`/`4514`, scoped totals,
posted-field recovery, a second employee preview, second-object deduction preview,
stale numeric recovery, desktop/narrow rendering, no browser/server errors, and
unchanged durable revision rows. Root inspected the desktop and narrow captures;
the selected title, decision and reason matched, scoped rows were visible, and the
wide preview table remained contained.

Retained settlement real-HTTP, filters/decision-chooser browser, and pure report
filter checks passed. PHP lint, JavaScript syntax checks, and `git diff --check`
passed. Primary evidence is outside Git at
`/Users/antropophag/.local/share/fmonitor-2/otiz-decision-preview-20261030/`;
final browser artifacts are under
`/var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/fm2-otiz-v2-browser-4e4a3e684eda/`.

## Exact runtime review binding

- `18b51bc748bc580c93f3bc693ef5fa7acc1e27e8ba8d753d1d3b02506ba1f3ed` `app/YiiRuntime/Assets/otiz.js`
- `b56491bf96d50a3aefe8a4ee03a0e5ed00466194af30070446a75271359c2d11` `app/YiiRuntime/Controllers/OtizSettlementV2Controller.php`
- `dc8d8d182a09941e2f51d75374824163d601d7997f852a09e2f3523cd018fccc` `app/YiiRuntime/Views/otiz-calculation-v2.php`

## Deferred and non-GREEN gates

The owner explicitly authorized the short code → independent review → production
cycle and deferred ordinary ceremony and full CI. No full local `make test` or
`make verify` was run.

No new architecture check was run for this correction. The known prior check has
two reviewed UI `SELECT`-token false positives, so architecture status remains
non-GREEN. This approval is limited to the exact bounded candidate and evidence
above; it is not CI, merge-admission, or general architecture approval.
