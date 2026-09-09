# OTIZ-SETTLEMENT-001 — GET/read-return Gate 3 v2

- Date: `2026-09-10`
- Reviewer: separately tasked agent `/root/settlement_review`
- Reviewed test-only candidate: `8d975af45c4d587343446301466000cccf657b0e`
- Prior review: `reviews/tests/OTIZ-SETTLEMENT-001-get-read-return-v1.md`
- Verdict: **CHANGES_REQUESTED**

The candidate honestly records chronology: the strengthened oracle was written
after an uncommitted simplified prototype exposed the earlier weakness. That
prototype is not treated as test-first production or as the reviewed source.
The committed preimplementation missing-route RED remains valid, and the WIP run
provides useful sensitivity evidence by failing at the absent retained heading.

The correction now protects the retained heading/date, accepted status, object
identity/address, exact displayed amount, primary OTIZ navigation, export link,
command actions, and the presence of command field names. This closes the
stripped-page content finding in part.

Two blocking parts of the v1 finding remain:

1. After successful discipline POST the test only requires the redirect target
   to return 200. It does not require the retained `closed` success flash, the
   newly appended closure's displayed amount/basis/history row, or the reverse
   form/action. A read-return implementation that discards the command result
   from the page still passes.
2. Form controls are global substring searches. `_csrf`, `operationId`, `basis`
   and other names may occur once anywhere in the document, or all controls may
   be placed in the wrong form. Parse the bounded page DOM and associate exact
   method/action/required fields with the closure and complete forms; after the
   closure exists, do the same for its exact reverse form.

Add these bounded semantic associations without freezing irrelevant whitespace,
retain the honest RED chronology, and request another review. Full browser flow
remains a later obligation.

Reviewed committed identities:

```text
c8434d00a7158dfa5d61b1f792263159b0cd48c6f1d09acf92b8166abf4e0e87  tests/Yii2/yii2_otiz_settlement_001_test.php
24604755245ee9b034d7840472eb07160e7190de1f947e87479f5c01d580c506  docs/operations/otiz-settlement-red-evidence-2026-09-09.md
```

Gate 3 remains **CHANGES_REQUESTED** for GET/read-return. The independent POST
approval and bounded core approval remain unaffected.
