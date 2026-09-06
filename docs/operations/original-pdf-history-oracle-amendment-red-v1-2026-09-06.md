# PDF legacy-oracle amendment — unapplied RED

Approved PDF-HISTORY-001 corrects two contradictions: unreachable forbidden
dictionary must be unsafe; decoded-budget oracle must use structural streams.
Patch adds only the new pure fixture import, changes that one status/message,
and replaces the two aggregate inputs by actual ObjStm streams totaling exactly
67108864 and67108865 decoded bytes. PASS/INVALID structural boundaries and all
other cases remain intact. No protected E2E edits.

Private byte-identical Original source copy runs the candidate under256MiB;
first intended failure is unsafe-required/unreachable actualpassive, exit255.
Source/helper hash manifest and raw candidate transcript in archive `/Users/antropophag/.local/state/fmonitor2-verification/original-pdf-history-red-w81_smc8`.

```json
{
  "baseHead": "2de38cb0c5c1dea21914bdc67d32ea3c83ffcf1b",
  "patchSha256": "adc5e3ed15f3ef5d58e88dad1e61d2cdbd9a07c8ab4539531d2d9d67901b7b2b",
  "beforeSha256": "37254fb8319093d958ae8138faa7d600d7236035392655e1b711b8959347f55a",
  "candidateSha256": "3acfed07b98126d97d9556e359584c2a9b8cdecd092b42cd624c8bf75908c6d8",
  "exit": 255,
  "logSha256": "57c2634453de89b0e5330df587967cb5ee06a2abe9ef3363d11048b8c279ac15"
}
```

Patch remains UNAPPLIED; independent Gate3 required. New structural fixture
construction is independently fixed and full new suite demonstrates both exact
budget outcomes even though the legacy candidate stops at its first mismatch.
