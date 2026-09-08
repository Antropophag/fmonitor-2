# Code review: PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001 v1

- Gate: 5 — fresh independent code review
- Date: `2026-09-04T17:02:00+03:00`
- Reviewer: separately tasked agent `/root/navigation_gate5_final`
- Reviewed exact HEAD: `8901c6edffe49d5c6b9b953c7ea8b33847bae0e0`
- Gate 3 review: `ff55373594794b03a96480321d6bf581ec73beae`
- Production commit: `1cb26a2b321643597dff0f7f6593f86f2871222f`
- Standards axis: **PASS**
- Specification axis: **CHANGES_REQUESTED**
- Verdict: **CHANGES_REQUESTED**

The reviewer did not author the executable specification, OpenSpec artifacts,
tests, production implementation, GREEN evidence, or full-verification record.
This append-only review is the reviewer's only change; production and tests were
not edited.

## Blocking findings

### 1. The v2 exact-hash package has no explicit owner approval

The current v2 executable package has these independently reviewed bytes:

```text
ffb72c0602a26e24aa86f7df339bcc209f6b0ce894f8a41988527c62e9db8c65  specs/PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001.md
44724732faad0fa0aae318ee64df41a53b496b1231b1997aa1f3a793903c4230  openspec/changes/remove-pilot-work-navigation-item/proposal.md
6dd91e84e023b21f82ff5884ca181e228c7e6b43f006ceec4b9490926e7d11b1  openspec/changes/remove-pilot-work-navigation-item/design.md
888bfabec7f079c9a5bc21ebf1093cded10c08dde131e6169fd9f37b24225504  openspec/changes/remove-pilot-work-navigation-item/specs/ui/pilot-work-navigation-item-removal/spec.md
```

`docs/operations/pilot-work-navigation-gate1-rereview-v2.md` approves those
hashes independently. The explicit exact-hash owner approval in
`docs/operations/pilot-work-navigation-removal-exact-hash-approval-2026-09-02.md`
instead lists the earlier v1 hashes `17d383...`, `48e95d...`, `f8d242...`, and
`321007...`. The later deep-seam decision records the owner's `ок` for the
described strategy, but does not enumerate or explicitly approve the exact v2
hash set after its independent review.

This is not sufficient for the sequence required by the current executable
specification, lines 150–164: fresh independent Gate 1 review followed by
explicit owner approval of exact reviewed hashes. Consequently the v2 Gate 2,
Gate 3, and Gate 4 lineage lacks its required exact-hash authority. The prior v1
approval is not reused for the changed integration composition.

Required correction: obtain an append-only explicit owner approval of the exact
v2 reviewed hashes before relying on this implementation lineage. Any changed
planning bytes require another fresh review and exact-hash approval.

### 2. Required exact-SHA repository-wide `VERIFY_OK` is absent

The executable specification, lines 161–164, requires focused regressions and a
full `make verify` with literal `VERIFY_OK` before fresh code approval. The only
full-verification record,
`docs/operations/pilot-work-navigation-removal-full-verification-2026-09-04-1650.md`,
is for candidate `af9f38cdce20b1ef9bfe893fc3c0980dc266dc61` and explicitly records
`FULL_VERIFICATION_FAILURE / no VERIFY_OK`. The reviewed HEAD is `8901c6e...`.

The named failures are classified as externally owned successor/integration
failures, and the navigation failure has disappeared. That supports a bounded
technical assessment of this production diff, but it is not a repository-wide
GREEN result and does not waive the executable Done contract. No integration,
CI, release-readiness, or `VERIFY_OK` claim is approved by this review.

## Standards axis — PASS

The production range `ff553735...1cb26a2` changes only
`app/PilotHttp/PilotView.php`. It removes the unused work-current expression and
the work anchor from both configured branches without adding abstraction,
duplication, domain behavior, persistence, or rapid-pilot changes. Remaining
siblings retain their predicates, order, attributes, labels, destinations, and
icon calls. No documented-standard violation or material code smell was found.

The historical evidence range contains three Markdown hard-break trailing
spaces. The append-only hygiene note accurately identifies the source record,
source hash `c82bee3a3f27b44ac489b15227a32dfcd2341f4e7d94ff2f814df2bf597fb122`,
and `git diff --check` exit `2`. The correction range `4f70942..8901c6e` is
clean. This disclosed historical evidence formatting does not alter production
semantics or silently rewrite evidence.

## Specification axis — production behavior assessment

Apart from the two blocking delivery findings above, the implementation is
bounded and matches the removal behavior:

- both configured `PilotView::document()` branches omit the `Моя работа` item;
- compatibility renderers and `rapid-pilot/` are untouched;
- exact `/pilot/` route/content, redirects, actor permission predicates,
  remaining navigation, error handling, and persistence code are unchanged;
- no replacement root item, hidden item, renamed item, icon-only item, or
  `/pilot/` navigation destination is introduced;
- the exhaustive renderer oracle covers ten current states, minimal/broad
  actors, siblings/current/accessibility/icon bytes, repeat, and zero-write;
- root and object-list sentinels reach configured composition and pass their
  navigation assertions before their separately owned later failures.

## Independent reproduction on reviewed HEAD

```text
2026-09-04T16:59:41+03:00
PASS: PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001 configured shared navigation

2026-09-04T16:59:56+03:00
pilot_http_auth_001_test.php: navigation assertion passed; later uppercase
legacy identity successor failed (expected 403, actual 200)

2026-09-04T16:59:57+03:00
pilot_object_list_001_test.php: navigation assertion passed; later origin-filter
successor failed (expected 1, actual 0)

2026-09-04T16:59:58+03:00
PASS: PILOT-OBJECT-CARD-001 public HTTP card

2026-09-04T17:00:02+03:00
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form

2026-09-04T17:00:12+03:00
PASS: PILOT-UI-SHELL-001 public UI shell

2026-09-04T17:00:44+03:00
ARCHITECTURE CHECK PASSED (7 rules)
make lint: exit 0
openspec validate remove-pilot-work-navigation-item --strict: valid
git diff --check 4f70942..8901c6e: exit 0
```

The first DB-backed invocation used the obsolete default demo password and was
repeated immediately with the canonical test-root credential; no result from
that setup failure is counted as behavioral evidence.

## Reviewed evidence hashes

```text
77021c6243e5688d3524f405a1b4d59e60f7ce6c708bccd7a8fb771337bbfa98  app/PilotHttp/PilotView.php
3e0a910f293e4601f46b3e8e5c6a2dc3586e58f8154e79a224b13d7505cceff5  tests/InstallationProcess/pilot_work_navigation_item_removal_001_test.php
a16229cb573cf48abe743c993afdc968fc7925a92b3a0469d8ec908fcec0cf3a  tests/InstallationProcess/pilot_http_auth_001_test.php
cbd5ba188d00acff2d17485fcafdce451367a6e0354b7ac9ea167a0887f5dd7d  tests/InstallationProcess/pilot_object_list_001_test.php
82fbac131ae7200037b9a8287dca488f3fcbb0a9d83d8313643ff09f14ffdf13  tests/InstallationProcess/pilot_object_card_001_test.php
59552423291008f1fa9b42a33a5523a988522c8c8b1841c05d2496a410be7611  tests/InstallationProcess/pilot_prepare_form_001_test.php
3a882c110496772d741340b2c1f43b8725cbbbb15e0319ee5446c0d76b7bed6f  tests/InstallationProcess/pilot_ui_shell_001_test.php
6403d47ecc85923bda74e2071eb3eba5f8e801b7dc75747e4baae718e2993f00  reviews/tests/PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001-v6.md
c82bee3a3f27b44ac489b15227a32dfcd2341f4e7d94ff2f814df2bf597fb122  docs/operations/pilot-work-navigation-removal-green-v1-2026-09-04.md
7333cd5b2277782645d61f7570fa10d45240727c5e0ac631bdcfc719c2478977  docs/operations/pilot-work-navigation-removal-full-verification-2026-09-04-1650.md
8a64f1ef70529b1de7c532bb59fc4d051a90e5676c4fef78562b6e911689f7d3  docs/operations/pilot-work-navigation-removal-green-v1-hygiene-note-2026-09-04.md
```

## Gate decision

**CHANGES_REQUESTED.** The production implementation itself passes both the
standards assessment and the bounded behavior assessment. Gate 5 remains open
until the v2 exact-hash authority is repaired and the required repository-wide
`VERIFY_OK` exists on the review candidate. Task 4.2 is not checked. Task 4.3,
integration, CI, and release readiness remain out of scope.
