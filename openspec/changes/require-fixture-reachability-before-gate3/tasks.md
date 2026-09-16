## 1. Gate 1–2 contract and RED

- [x] 1.1 Create the normative tooling specification and verification input for the complete acceptance matrix, then run `python3 tools/delivery/change-verification.py plan --input openspec/changes/require-fixture-reachability-before-gate3/verification-input.json --base 764f2c0f2118a8c8f8cdb7b8235fb360e982bb13` and verify every obligation is resolved before Gate 2.
- [x] 1.2 Author bounded tests for declaration validation, exact-source/identity binding, healthy dual evidence, missing evidence, `SETUP_FAILURE`, arbitrary crash/marker and non-destructive constraints; verify the focused test fails on current `origin/main` for the missing safeguard.
- [x] 1.3 Add sensitivity fixtures for missing table/column, wrong helper argument, malformed provider/index, invalid CSRF/setup source and broken post-fork DB fixture, including one synthetic defective test and one realistic #20-shaped healthy/defective control; verify each defective variant is rejected before Gate 3 while ordinary healthy execution remains genuine `INTENDED_RED`.
- [x] 1.4 Record RED command/output in `reviews/tests/` and use `harness.py prepare` with the active verification plan to create the exact-source Gate-3 reviewer package; verify it remains `NOT_REVIEWED`.

## 2. Independent Gate 3 and minimal safeguard

- [x] 2.1 Obtain an independent Gate-3 review from a non-author gpt-5.6-sol/low reviewer against the prepared package; verify the review record has an explicit verdict and resolve all findings before implementation.
- [ ] 2.2 Have a separate gpt-5.6-sol/low executor add the minimal opt-in reachability declaration and planner propagation; verify malformed/unknown declarations fail closed and undeclared non-applicable tests preserve compatibility.
- [ ] 2.3 Extend the existing runner record with a bounded reachability mode/outcome bound to command, acceptance, source, environment, test blob and boundary; verify marker-only, nonzero, timeout, signal and control-marker executions never produce healthy reachability.
- [ ] 2.4 Extend Gate-3 `prepare` admission to require both `INTENDED_RED` and matching healthy reachability evidence for applicable acceptances; verify package approval remains `NOT_REVIEWED` and missing/stale/foreign evidence is rejected.

## 3. Focused verification and review

- [ ] 3.1 Run only the planner-selected focused tooling checks plus OpenSpec strict validation and relevant architecture check; verify the five defect classes block, healthy intended RED advances, existing intended-RED provenance tests stay green, and no full local `make test`/`make verify` is run.
- [ ] 3.2 Capture the complete exact-source candidate/package and obtain the planner-required independent final review from a non-author gpt-5.6-sol/low reviewer; verify all findings are resolved and the final verdict is explicit `APPROVED`.
- [ ] 3.3 Push the separate branch and run the selected existing GitHub CI consumer once on the exact reviewed source; inventory every failed job and `REGRESSION_FAILURE` before any correction, and verify final CI is GREEN for that exact source.
- [ ] 3.4 Prepare a separate PR-ready handoff recording base/source, actual authors, RED/GREEN evidence, review counts, focused commands, CI URL/status and exclusions; verify PR remains unmerged and then STOP.
