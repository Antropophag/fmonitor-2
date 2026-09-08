# Pilot demo bootstrap protected integration — supplemental test review

Reviewer: `/root/photo_review`; artifact author: `/root/e2e`.
Verdict: **APPROVED** for the bounded dependency, cleanup, deadline and CSS-failure slice.

The original post-contract failure was not a 12-second performance failure. Exact
diagnostic evidence showed exit 255 because the isolated checkout omitted the
current launcher's required `rapid-pilot/verify-visual-contract.php`. Both isolated
checkouts now expose the same task-read-only `app`, `public` and `rapid-pilot`
dependencies. Cleanup distinguishes directory symlinks before `rmdir`, and the
iterator does not follow them, so it unlinks fixture links without traversing or
deleting shared targets.

Measured healthy child durations justify bounded per-contract limits:
production migration 30.95s→60s, case import 44.66s→90s, protected flow→300s;
the fast artifact and shlz contracts remain 25s. The independent deadline self-check,
collector termination/reaping, and CSS bind deadline of 12 seconds are unchanged.

Exact reviewed artifacts:

```text
a13b352f6d041e4a85d896fe00df3efce45a661473eeef697ed904dee4f93bbd  tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
166eab6b56e1f259c592f940c194d027f0027bc8566c034137c44fa8a5ce23ab  bin/fmonitor2-pilot-demo.php
```

The current launcher returns a total three-element HTTP probe tuple. On failed
startup it rechecks the exact official CSS graph, mapping changed/unreadable graph
to `SHLZ_ASSETS_UNAVAILABLE` while leaving an unchanged-graph startup failure generic.

Private focused body evidence
`bootstrap-post-contract-body-final-2.log` (mode 0600, SHA-256
`3817139e07c9c20ebd13fc0f048b5de152aecc63daa536cc2aee62aad14afa0c`)
advanced through the post-spawn mutation and all isolated CSS rejection assertions,
then stopped at a later normal-startup failure. Therefore the scoped CSS/dependency/
cleanup slice is GREEN; the whole bootstrap is not claimed GREEN. PHP lint and
focused diff-check PASS.
