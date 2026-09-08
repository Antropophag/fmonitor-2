# Test review: PILOT-HTTP-AUTH-001 — original HTTP global-call regression

- Reviewer: `/root/original_gate3`, separately tasked independent agent; did not author the test or affected implementation.
- Test author: inherited previously approved test author; test is unchanged.
- Reviewed source: full verification source `7cb79d0`; current HEAD `9488814b4f1ecd16a458dc435cd2989a42349db1` has no `app/PilotHttp` diff from that source.
- Specification: `PILOT-HTTP-AUTH-001.md` v0.12, section 11.2, inherited approved contract.
- Public seam: the specified structural invariant over every production `app/PilotHttp/*.php` direct function call; existing HTTP behavior tests remain separate.
- Verdict: `APPROVED`.

## Findings

Read the normative qualification requirement, unchanged tokenizer test and prior independent `reviews/tests/PILOT-HTTP-AUTH-001.md` approval. Gate 1 can be reused: this correction introduces no new behavior or exception, and both spec and test hashes exactly match the prior reviewed artifacts. The prior review's source inventory does not automatically approve added files; this fresh independent review covers the expanded inventory.

The oracle tokenizes every PHP file in the production directory and classifies direct-call syntax before any name filtering. Bare and namespace-relative direct calls are reported; declarations, constructors, member/static calls and explicitly qualified calls are structurally excluded. Its self-contained string tokenization checks reject an unlisted name and namespace-relative name while accepting the intended exclusions. They do not define shadow functions, execute runtime probes, intercept native behavior or mutate a function table. The empty expected offender list derives directly from the inherited specification; no production-derived expected list weakens future-file/name sensitivity.

The captured RED is valid: `/Users/antropophag/.local/state/fmonitor2-verification/original-http-20260907/make-verify-7cb79d0.log` shows `VERIFY tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php`, then the complete 113-call offender list and `REGRESSION_FAILURE`. Independently counted the first complete list: ten files, all named `FreshOrder*` or `OriginalUpload*`. The tokenizer setup assertions passed before the intended empty-list assertion failed. This is a genuine invariant regression, not a setup failure. Root executed full verification; the reviewer inspected the captured evidence and source identity rather than claiming another full run.

No blocking test findings. The bounded minimal GREEN correction may fully qualify the reported built-in call sites while retaining all test expectations and runtime behavior. Do not add namespace shadows or change the oracle. Focused oracle GREEN, relevant HTTP regression and independent code review remain necessary; this record does not approve the future implementation.

## Reviewed identity

```text
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
60110b7db537c74262310a7e982e20e6945c12b49f5d1524f0b02c0a13c58271  tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php
7eeab8ae098bf6e39474796577cb9611c61ba91cb7e6f4b764af98a8a3ddfab7  app/PilotHttp/*.php sorted relative per-file SHA-256 manifest
```

Manifest construction: sort relative PHP paths, concatenate `<file SHA-256><two spaces><relative path><LF>`, then SHA-256 the resulting bytes. Only this review record was written by the reviewer.
