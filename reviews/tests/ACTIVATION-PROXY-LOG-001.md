# Independent Gate 3 test review — ACTIVATION-PROXY-LOG-001

- Date: 2026-09-10
- Reviewer: independently tasked agent `/root/review76_proxy`
- Specification/test author: root agent
- Reviewed commit: `70cd32807b0bf598f1ba331000125f4d3985ba61`
- Fixed base: `8c4468738a96953055d35fe6c05bd0c5d26539e4`
- Verdict: **CHANGES_REQUESTED**

The reviewer authored neither the specification, OpenSpec artifacts nor the
test. No production, specification or test file was edited during review. This
review record is the reviewer's only repository change.

## Findings

### G3-1 — HIGH — the required plan does not map each normative acceptance

`openspec/changes/activation-proxy-log-privacy/verification-input.json:1`
collapses the seven normative rows `syntax`, `access`, `error`, `diagnostics`,
`parity`, `setup` and `isolation` into one synthetic acceptance ID. The required
planning procedure says to map every acceptance statement to its public seam and
test path. The combined entry hides which existing regression owns each
obligation and prevents the generated plan from exposing an omitted row.

Correction: create one verification-input acceptance entry for every normative
acceptance ID, assign the actual seam and test paths for each row, regenerate the
plan, inspect its obligations and preserve its new digest.

### G3-2 — MEDIUM — RED evidence is not bound to the reviewed plan

`docs/operations/activation-proxy-log-evidence-2026-09-10.md:9-11` names the
input and ignored output path but does not record the generated plan SHA-256 or
an exact source binding. The compact process requires the reviewed plan digest
to be preserved with evidence; without it a later regeneration cannot be shown
to be the plan inspected for this RED candidate.

Correction: after correcting the mapping, record the plan digest and exact
commit/source binding in the evidence record.

### G3-3 — HIGH — cleanup is not fail-safe across the promised failure modes

`tests/Runtime/activation_proxy_log_001_test.py:102-104` stores the container ID
only after `docker run --detach` returns successfully. If Docker creates or
starts the uniquely named container but the client times out or returns nonzero
before `require()` yields stdout, `container` remains `None` and the `finally`
block at lines 158-160 skips cleanup. That contradicts the normative `isolation`
row, which requires cleanup on failures, and could leave a test-owned running or
stopped container behind.

Correction: generate and retain the unique container name before invoking
Docker; in `finally`, inspect and remove that exact owned name while tolerating
its absence. Preserve strict ownership and add a deterministic failure-path
assertion that proves the created test container is removed.

## Traceability and test assessment

The test otherwise exercises the correct public seam with a real nginx binary,
candidate-owned read-only configurations and deliberately absent FPM. It covers
both current configurations, real syntax, invalid-config diagnostics, GET and
POST activation requests, query/Referer/User-Agent/body canaries, access and
error streams, 502 preservation, safe JSON fields, unique request IDs, ordinary
upstream diagnostics, exact activation routing and static FastCGI handoff parity.
Expected paths, methods, statuses and canaries are independently constructed.
No database, application state, production stand, image mutation or hidden pull
is involved in the override run.

The successful-path cleanup observed during review is sound, but it does not
close G3-3 because the untested pre-assignment failure window remains.

## Verification evidence

The required plan was regenerated from the reviewed commit and checked:

```text
python3 tools/delivery/change-verification.py plan --base 8c446873 \
  --input openspec/changes/activation-proxy-log-privacy/verification-input.json \
  --output .local/verification/privacy-plan.json
python3 tools/delivery/change-verification.py check \
  --plan .local/verification/privacy-plan.json
CHANGE_VERIFICATION_OK

22a4f8819fedbd33f4f4afa6dde90160b29b2bbcb4585cbe8a4162fd8662a8d5  .local/verification/privacy-plan.json
```

That check establishes schema/current-source consistency; it does not cure the
collapsed acceptance mapping in G3-1.

Focused structural checks were green:

```text
python3 -m py_compile tests/Runtime/activation_proxy_log_001_test.py
# exit 0

python3 tests/Verification/verification_inventory_001_test.py
Ran 15 tests in 6.102s
OK

openspec validate activation-proxy-log-privacy --strict
Change 'activation-proxy-log-privacy' is valid

git diff --check
# exit 0
```

The prescribed cached-image command reproduced the intended product RED:

```text
FMONITOR_TEST_NGINX_IMAGE=fmonitor2-yii2:auth-pr \
  python3 tests/Runtime/activation_proxy_log_001_test.py
# exit 1
```

Both configurations passed nginx syntax and returned HTTP 502 for the exercised
requests. Both leaked query token A and Referer token B to access and error logs;
User-Agent token C leaked to access logs. Both lacked the exact activation
boundary and structured safe access records. No setup failure was reported.
Container IDs before and after the run were identical, and no `fm2-log-test-*`
container remained. Full output is retained at
`/tmp/76-proxy-gate3-red-final.log`; synthetic request evidence is at
`/var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/fmonitor-activation-proxy-1wsnxt22`.

## Reviewed artifact hashes

```text
fac1fde8b01cbbd1551a066b89a785253c30efeddb93c324bc54311a59ea1f8c  specs/ACTIVATION-PROXY-LOG-001.md
f0eba8fa81f0d3695fd165e75c5649c642828112d0eab34b4d7a7b2c8a9efa19  openspec/changes/activation-proxy-log-privacy/design.md
91633ca632b5ba930a03c643886242be1478ff617d23ef3e05eaae0681805b0b  openspec/changes/activation-proxy-log-privacy/verification-input.json
cad4609b6e64b7dea98a3a58c5928d3d0a76022a5b4cee22c0183dc603ef43ba  tests/Runtime/activation_proxy_log_001_test.py
9f2caba074ea9c1fba8ff87636495232bbbdb7b2550b8faa1c670ccd83cefd89  docs/operations/activation-proxy-log-evidence-2026-09-10.md
```

## Gate decision

Gate 3 is **CHANGES_REQUESTED**. Return to Gate 2 to correct the acceptance
mapping, bind the regenerated plan digest in evidence, and prove cleanup across
the detached-start failure window. Gate 4 is not authorized from this review.

---

# Independent Gate 3 rereview — corrected snapshot

- Date: 2026-09-10
- Reviewer: independently tasked agent `/root/review76_proxy`
- Specification/test correction author: root agent
- Corrected source base: `70cd32807b0bf598f1ba331000125f4d3985ba61`
- Corrected source patch SHA-256: `2938248d456ee11631af412d057a94c75b7d47a4a4f9321029ae6a4bc2f66dfa`
- Binding manifest: `/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-proxy-gate3-corrected-binding.json`
- Verdict: **APPROVED**

The reviewer authored none of the corrected specification, OpenSpec, evidence or
test artifacts. The original return above remains immutable. This appended
rereview is the reviewer's only additional repository change.

## Finding resolution

### G3-1 resolved — one composed acceptance is required by the planner contract

The original request for seven input mappings was incorrect. The controlling
`specs/CHANGE-VERIFICATION-001.md` requirement 2 expressly rejects assigning one
test path to two mappings. The corrected normative specification now defines the
stable acceptance ID `private-and-diagnosable-activation-proxy` as one composed
scenario with seven mandatory checks. The design maps every check—syntax,
access, error, diagnostics, parity, setup and isolation—to its concrete section
of the one executable test. The verification input names that composed
acceptance and retains all four applicable test paths. No obligation was dropped
or hidden behind a dummy wrapper.

### G3-2 resolved — source, plan and RED are cryptographically bound

The external binding manifest records exact base, patch, plan, RED, spec and test
digests. Independent comparison confirmed the retained source patch, current and
retained plan copies, and retained RED copy match the manifest. This is the
repository-defined reconstructible snapshot form and avoids self-referential
digest churn in the candidate source.

### G3-3 resolved — cleanup ownership exists before Docker creation

The corrected test generates a unique container name and ownership marker before
calling Docker. `owned_container()` inspects that exact name and removes it only
when both its label marker and 64-hex Docker ID match. It tolerates absence and
refuses to remove a name with different ownership.

The deterministic failure probe performs a real `docker create`, raises a
simulated client failure before assigning its returned ID to the cleanup path,
then asserts that the owned container disappeared while a separately labelled
fixture survived. The detached nginx path uses the same context. This covers the
precise failure window from the first review while preserving unrelated
containers.

## Corrected verification evidence

Binding verification:

```text
2938248d456ee11631af412d057a94c75b7d47a4a4f9321029ae6a4bc2f66dfa  source.patch
c2c214f38dd6e15a0e17755153316237b5cfe184e2b6372bcce833096169ebe2  privacy-plan.json
e111a7e1b51d59e2a45d752d20c18c934d4360f6ff2de143f71d23d3bf99c826  corrected-red.log
PLAN_COPY_MATCH
RED_COPY_MATCH
```

The bound current plan is valid for the corrected source:

```text
python3 tools/delivery/change-verification.py check \
  --plan .local/verification/privacy-plan.json
CHANGE_VERIFICATION_OK

python3 -m py_compile tests/Runtime/activation_proxy_log_001_test.py
# exit 0

openspec validate activation-proxy-log-privacy --strict
Change 'activation-proxy-log-privacy' is valid

git diff --check
# exit 0
```

The reviewer freshly reproduced the corrected test:

```text
FMONITOR_TEST_NGINX_IMAGE=fmonitor2-yii2:auth-pr \
  python3 tests/Runtime/activation_proxy_log_001_test.py
# exit 1
```

The cleanup probe passed silently. Both nginx configurations then reached the
same intended RED inventory as before: exact activation boundary and structured
safe access records absent; query A and Referer B leaked through access and error
streams; User-Agent C leaked through access. HTTP remained 502 and there was no
setup failure. Complete output is retained at
`/tmp/76-proxy-gate3-corrected-rereview.log`, with synthetic evidence at
`/var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/fmonitor-activation-proxy-onrwy9_z`.
The complete Docker container inventory was byte-identical before and after;
no ownership-labelled fixture remained.

The unchanged inventory integration had already passed 15/15 in the first
review. None of the corrected snapshot paths changes that inventory mapping.

## Corrected reviewed hashes

```text
21393bfa7bb1792c6b81aca0514a248fde118cafdb5a2b0743f85a2387ad865b  specs/ACTIVATION-PROXY-LOG-001.md
c6587ff395966b0d6b43e896941858c9009d702114788d1e32b40bbbbd1c7b95  openspec/changes/activation-proxy-log-privacy/design.md
5167b1b9bfce7c7a0a9b874430fcb6de0ed5e01b58cb10cc32a6cfdf579efeb7  openspec/changes/activation-proxy-log-privacy/verification-input.json
aa68a919088c2640fdf892b7479aaddcd661d1b1dd473d999132abedfe45ed3b  tests/Runtime/activation_proxy_log_001_test.py
27eec62411689c6460951e0e85180b21c78386c84d429ba4e7e69fef345ddcfa  docs/operations/activation-proxy-log-evidence-2026-09-10.md
```

## Rereview gate decision

Gate 3 is **APPROVED** for the exact corrected snapshot and bindings above. The
test is traceable, sensitive to the privacy and diagnostic requirements,
deterministic at the real nginx/Docker seam, independently expected, and isolated
from the production stand. Minimal Gate 4 implementation may proceed. Any later
change to the approved spec, test, verification input or bound source restarts
the applicable gate review.
