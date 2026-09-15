## 1. Contract and RED

- [x] 1.1 Add the stable Slice A executable specification and verify it explicitly leaves #153 Slice B/C/D open
- [x] 1.2 Add a canonical-registered regression through the real planner/prepare seam covering matrix A–L and verify the old planner admits the synthetic #148-style incomplete plan (INTENDED_RED)
- [x] 1.3 Create `verification-input.json`, run planner prepare, and obtain independent Gate 3 approval for the RED source selected by the planner

## 2. Minimal deterministic escalation

- [x] 2.1 Add the minimal closed-set semantic-surface classification to the existing verification policy and validate malformed/ambiguous metadata fail closed
- [x] 2.2 Make the existing planner derive required integration closure solely from canonical `suites.tsv`, fail closed when no registered integration verifier exists, and emit machine-readable escalation evidence
- [x] 2.3 Keep healthy FAST, presentation-only, docs-only and unrelated STANDARD/CRITICAL behavior unchanged as proven by focused regression

## 3. Delivery gates

- [x] 3.1 Run only planner-selected bounded focused checks and record results without a local full-suite run
- [x] 3.2 Obtain independent Gate 5 review of the complete exact source and address any findings through the gated correction loop
- [ ] 3.3 Run one exact-source GitHub Quality Graph CI matrix, record complete failure inventory if non-GREEN, and leave the candidate PR-ready without merge/deployment/settings changes
- [ ] 3.4 Record `#153 Slice A delivered`, `#153 remains open`, and B/C/D untouched, then stop
