# Object-detail native-false CREATE correction GREEN

Date: 2026-09-05. Implementer: `/root`.
RED: `dc4b701cee160166edad31ad27c592e4bf20a980`.
Independent Gate 3: `1e7e43a4375faab3c08d07236c2d40fc54e20c0a`.
Implementation: `b7bc649cb6d918dffa947ff0068bddfe17d31d5b`.

The engine requires query(CREATE) to return true before recording the created
table or emitting its phase event. Native false now immediately gives typed
DatabaseUnavailable through the existing lock-release path. No test/spec or
configuration was changed.

Verified production file SHA-256:
`2ae45e43084c56858d589eb98362c61415b13a92cf1ffba0781a9936c9b82eff`.

All focused checks passed against these bytes:

- native-false CREATE event regression;
- observer phases and interruption recovery;
- real DDL privilege denial/retry;
- causal two-creator and independent-prefix concurrency;
- base schema corpus, held-lock/retry and composed runner;
- architecture check (7 rules), PHP lint and diff-check.

Fresh independent code review remains required. Importer no-DDL and full
integration/VERIFY_OK are not established by this correction or its checks.
