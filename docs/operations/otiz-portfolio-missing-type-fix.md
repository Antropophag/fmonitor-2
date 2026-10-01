# ОТиЗ: импортированная карточка без типа лифта

Owner follow-up 2026-09-27 during the emergency live OTIZ repair: why is the whole portfolio fund zero? Root diagnosed and authored this bounded restoration of established behavior. The same emergency no-gates direction remains in force; no review/CI approval is claimed.

Production read-only evidence: 309 objects,309 imported technical cards,0 manual edits; all cards have floors/capacity/shaft material and omit lift_type. Existing NativePremiumNorms resolves all309 using the established missing-type passenger fallback, total20304300000 kopecks. The new v2 portfolio returned before calling that catalogue whenever parsed type was null, suppressing the existing fallback and summing unresolved funds to zero.

Normative source: `specs/OTIZ-OBJECT-REGISTER-PAGING-001.md` Appendix4 example explicitly requires a card without lift_type at320kg to use passenger fallback. The correction permits only absent/blank type to reach the existing catalogue. A nonempty unrecognized type remains unresolved, as does unsupported capacity. No new norm, formula, data repair, stored calculation or payout mutation is introduced.

Root added a real public-portfolio DB regression for missing lift_type, per-object52000000 cents and whole-filter aggregate, preserving the existing explicit-unknown-type test. RED reproduced NULL instead of52000000; focused `php tests/Otiz/settlement_v2_portfolio_001_test.php` is GREEN after the one-condition correction. Private logs remain `/tmp/fmonitor-fund-missing-type-{red,green}.log`. Deployment replaces only the portfolio read class, without stopping the site. The original class is retained privately in the production hotfix record.
