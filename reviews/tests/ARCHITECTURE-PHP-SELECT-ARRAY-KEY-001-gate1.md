# Independent Gate 1 review — ARCHITECTURE-PHP-SELECT-ARRAY-KEY-001

- Verdict: **APPROVED**
- Reviewer: `/root/bitrix_gate3`, separately tasked agent; not an author of the specification, tests or implementation.
- Date: 2026-09-07.
- Reviewed HEAD: `c2df5a3f8d428c68e8971375383529c34e5bf752`.
- Specification: version 0.1, SHA-256 `97eb3e3e7e141a5318d102aa81f58957da07c320c13dc2e2eb66eaca4047b235`.
- Inherited contract: `ARCHITECTURE-PHP-SELECT-TOKENS-001`, SHA-256 `1c6572962a4b2cc9830bb6bfc22f4db2c3c58a3e55491c156586f73d5e1500a1`.
- Public seam: `python3 tools/architecture/check.py --json` over an isolated repository fixture with the existing baseline unchanged.

## Findings

The specification resolves the measured false positive with a sufficiently narrow lexical rule. Only a whole single- or double-quoted `select` literal, matched ASCII case-insensitively and immediately followed on the same line by optional whitespace and PHP `=>`, is excluded as an array-key token. The contract does not exempt its value, another literal, or the rest of the line. Nested arrays and both accepted quote styles have an exact positive outcome: exit 0 and the normal successful JSON envelope.

The rejection examples make the safety boundary independently executable. A bare quoted SELECT, concatenated SQL, SQL in the keyed value, and SQL later on the same source line must still return exit 1 with `sql_ownership`. A longer SQL literal containing `select =>` cannot be removed. DDL and rapid-pilot mutation remain detectable beside the key. These cases would catch whole-line suppression, broad removal of quoted SELECT literals, and a scanner that stops after recognizing the key.

Fingerprint behavior is explicit: genuine violations retain the fingerprint of the original normalized source. This aligns with the inherited SELECT-token contract and prevents lexical masking from becoming a baseline or debt-identity rewrite. The unchanged-baseline requirement, prohibition on file allowlists, readonly fixture-only CLI tests, and preservation of all other architecture rules keep authorization, mutation and repository scope unambiguous.

Expected results come from literal worked examples rather than the current detector implementation. No product behavior, SQL ownership boundary, baseline entry or public capability name changes in this slice. The unrelated Bitrix production working-tree changes were not reviewed and do not affect this Gate 1 verdict.

## OpenSpec alignment

The proposal, design, tasks and delta specification consistently describe the same surgical array-key recognition and preserved SQL enforcement. Independently ran:

```text
openspec validate recognize-php-select-array-key --strict
Change 'recognize-php-select-array-key' is valid
```

Reviewed OpenSpec hashes:

```text
34b4c047de108e28181efeadbf723ed90d3bf0fefd64764470453e3f02dbf0b9  openspec/changes/recognize-php-select-array-key/proposal.md
2d349215e404ad0ca92b102934e785c858cd4fb6e0f3b734ed0d98a1b293f5f4  openspec/changes/recognize-php-select-array-key/design.md
fc050106d2321e8c712f58ff75d0b81d3bbe0eb719e07217776d9802742df953  openspec/changes/recognize-php-select-array-key/tasks.md
99f539cbd06cd4066e221c68b69114eff3b13e268b4eea78e384e3a6a9cba444  openspec/changes/recognize-php-select-array-key/specs/architecture/php-array-key/spec.md
```

No blocking ambiguity remains. Gate 2 may add the smallest public-CLI RED fixtures that prove the positive array-key cases and every stated same-line/concatenation/fingerprint negative boundary. Independent Gate 3 remains required before scanner implementation. Only this Gate 1 review record was added.
