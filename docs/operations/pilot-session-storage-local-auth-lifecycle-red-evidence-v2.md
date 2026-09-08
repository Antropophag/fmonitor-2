# PILOT-SESSION-STORAGE-001 v10 LocalAuth lifecycle — Gate 2 RED v2

Date: 2026-09-03

The corrected Gate 2 set contains three independent raw-HTTP tracers:

- complete successful lifecycle through the real rapid router;
- canonical anonymous return-to commit/readback through the injected factory;
- exact return-to write, login regeneration and logout destroy fault boundaries
  through three fixed test routers and redacted external primitive traces.

```text
f8cfa30eace1eb62c8e32cc3a5d308019003088992746769e671e4635c8dcb9a  tests/InstallationProcess/pilot_session_storage_local_auth_lifecycle_001_test.php
08676b53f6052cce06f33f5e2e3c48ae1f6bb559a9338f8f1174a5cd0a5bccad  tests/InstallationProcess/pilot_session_storage_local_auth_faults_001_test.php
965c7d7a0cbe88d60fa311b8542ef8bb1173e9009cf4952f002e185e23edaf09  tests/InstallationProcess/pilot_session_storage_local_auth_return_to_001_test.php
048d764f25d9609ed2b0c899d45b5adc187dd15c62215488918d43dd6555a6cd  tests/Support/pilot_session_storage_local_auth_fault_common.php
```

Honest REDs on current production:

```text
lifecycle: GET /pilot/login expected 200, actual 503, exit 255
return-to: protected GET expected 303, actual 401, exit 255
faults: return-to write fault expected 503, actual 401, exit 255
```

The later assertions require `serialize(decoded) === raw`, reopened exact
return-to, regeneration/new cookie/old invalidation, logout destruction, full
section-6 envelopes, one exact redacted primitive tuple per fault, and preserved
old bytes. All DB tables/processes/task roots are bounded and removed; the
legacy hardcoded root is absent before and after.
