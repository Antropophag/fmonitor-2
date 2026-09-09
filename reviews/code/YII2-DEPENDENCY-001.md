# Independent code review: YII2-DEPENDENCY-001

- Reviewer: root agent, not implementation/test author.
- Implementation/test author: /root/inventory_review, gpt-5.6-sol/low.
- Reviewed source: e24a153288c6a643f1b1217e691bd85a7c5806dc.
- Specification: specs/YII2-DEPENDENCY-001.md.
- Verdict: `APPROVED`.

## Findings and resolved changes

Pinned versioned Composer phar is digest-verified before execution; full setup and
CI share a lightweight bootstrap, without repeated Docker/shlz setup in each job.
Staging is a sibling of vendor, preserving Composer's relative app autoload paths
on publication. Candidate loading, locked package versions/references/paths and
platform checks precede publication. Existing incompatible vendor is rejected and
preserved; --check does not download or create files. macOS shasum fallback and
safe numeric pin-key support retain the existing manifest injection protection.

Code review found and fixed missing platform checks on normal existing-vendor
reuse and missing transitive locked graph validation. Supplemental platform RED:
5 tests, 1 failure, existing-vendor setup incorrectly returned0 with platform probe
forced to fail. Root approved the added public failure expectation before the fix.
For graph validation root independently ran a guard-removal mutation in an isolated
copy: 5 tests, 1 failure at stale-vendor expected nonzero vs0. This is mutation RED,
not falsely described as a historical source run. Unmodified source is GREEN.

Test changes were independently reviewed: metadata probe failure toggles preserve
vendor fingerprints and verify the probe actually ran; trace is cleared before
those branches. Earlier no-PHP assertion was corrected to no-unverified-phar
execution (ordinary manifest parsing is permitted), and a variable-name-dependent
shell regex was loosened without weakening behavioral checks.

## Verification

- Dependency suite5/5 and development setup8/8 GREEN.
- Real isolated fresh Composer install (9 locked packages), app autoload after stage
  rename, repeat and platform checks GREEN (author evidence).
- Root repeated dependency suite and real --check: GREEN.
- Renderer --check, Bash syntax, git diff --check GREEN.
- Root verification CI selection/aggregation suite15/15 GREEN.

No blocking finding remains. Full exact-source CI is still required. Review covers
bootstrap/CI dependency wiring; root-authored runtime is independently approved in
YII2-RUNTIME-001.md. Test fixture/data preservation relaxation does not authorize
modifying unrelated local dependency trees.
