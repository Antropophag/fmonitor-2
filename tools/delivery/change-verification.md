# Required verification plan before Gate 2

This is FMonitor's repository-owned Quality Graph planning extension. The stock
publisher continues reporting CI results; it does not infer acceptance statements
from prose. The accepted specification and reviewed policy own the obligations.

1. Before writing RED tests, create a change input JSON. Declare planned source
   paths and map every acceptance statement to its public seam and test path.
   Tests may be planned before the files exist. The independent reviewer checks
   mapping completeness against the normative specification.
2. Generate the plan into an ignored local directory and read its commands and
   rationales. Unknown or ambiguous boundaries and missing obligations block
   Gate 2. Resolve the policy/contract, then regenerate; do not bypass the error.
3. Write the RED test. Regenerate for the new source snapshot, inspect obligation
   changes, and run the focused phase. Preserve the actual failing command and
   intended failure in the Gate 3 review record. Setup failure is not valid RED.
4. Recompute after bound source/input/spec/policy changes. Review any obligation
   changes. Use `check` before consuming a saved plan; `run` checks automatically.
   Gate 3 and Gate 5 independently inspect the specification, plan and evidence.
5. Run full integration once on the exact candidate in CI through the existing
   workflow. The plan's integration command records that obligation. Running the
   focused phase does not execute the full command or prepare/reset a database.

Example input (use the real specification and paths for the change):

```json
{
  "change": "example-change",
  "planned_paths": ["app/PilotHttp/Action.php"],
  "acceptances": [{
    "spec_id": "EXAMPLE-001",
    "acceptance_id": "authorized-command",
    "spec_path": "specs/EXAMPLE-001.md",
    "seam": "POST /example",
    "tests": ["tests/InstallationProcess/example_001_test.php"]
  }]
}
```

From the repository root, with the input at the change's `verification-input.json`:

```sh
mkdir -p .local/verification
python3 tools/delivery/change-verification.py plan --base origin/main \
  --input openspec/changes/example-change/verification-input.json \
  --output .local/verification/plan.json > /dev/null
python3 -c 'import json; p=json.load(open(".local/verification/plan.json")); print(json.dumps({k:p[k] for k in ("change","acceptances","required_categories","commands")}, indent=2))'
python3 tools/delivery/change-verification.py check --plan .local/verification/plan.json
python3 tools/delivery/change-verification.py run --plan .local/verification/plan.json --phase focused
```

Include the input and generated-plan location in the compact executor/reviewer
handoff. Preserve the reviewed plan digest with evidence; regenerating a plan
never approves a gate. Direct test invocation is still possible, but does not
satisfy the process's required-plan and review obligations by itself.

The planner's own initial RED used an explicitly enumerated bootstrap plan because
this CLI did not yet exist. See `reviews/tests/CHANGE-VERIFICATION-001.md`; this is
historical evidence, not an exemption for subsequent changes.
