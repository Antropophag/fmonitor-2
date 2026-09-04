# PILOT-OBJECT-LIST integration RED v4 hygiene disclosure

- Date: `2026-09-04`
- Author: separately tasked Gate 2 agent `/root/object_list_integration_red`
- Immutable source: `docs/operations/pilot-object-list-integration-red-correction-v4-2026-09-04.md`
- Immutable source SHA-256: `a2a3c4e24ef73799021ff5de5923d267e62df35e9832aeffea2cbce9749704b4`
- Source range: `c35a7246231c86a789db3e234a588e1eeb9106ad..5d1dd4e6f581de1bc6073fa5d4840d9fad08fbd2`

The append-only v4 record says `git diff --check` exited zero. Independent Gate
3 reproduction found one evidence-only formatting defect:

```text
$ git diff --check c35a7246231c86a789db3e234a588e1eeb9106ad..5d1dd4e6f581de1bc6073fa5d4840d9fad08fbd2
docs/operations/pilot-object-list-integration-red-correction-v4-2026-09-04.md:85: new blank line at EOF.
exit 2
```

The immutable source record is not rewritten. The defect is its final extra
blank line only; it does not change executable test or production semantics.
The v5 executable/test diff and all new v5 records are checked separately and
must contain neither trailing whitespace nor a blank line at EOF.
