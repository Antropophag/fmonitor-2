# Independent Gate 1 operational amendment — PILOT-LOCAL-TRUSTED-SCHEME-001

- Verdict: **APPROVED**
- Reviewer: `/root/original_gate1_v3`, separately tasked agent; not an author of the reviewed amendment or configuration/test implementation.
- Date: 2026-09-07
- Reviewed HEAD: `a5419effa8a2da44edd216da0f5fc99d64bd978c`.
- Specification: version 0.2, SHA-256 `a27ea4cb389f883a72a42dcaad4ba1d7a313d741359b8b3bbda7c22f48ac1826`.
- OpenSpec design SHA-256: `1af06dd044a331acc511ef5ae6da86e4c85570e1dec9fd6783bad3151b64067c`.
- Prior record: `PILOT-LOCAL-TRUSTED-SCHEME-001-gate1.md`.

## Amendment and disposition

No blocking findings. The amendment replaces v0.1's blanket operational no-migration/no-DB-change assumption with a precise existing-startup allowance. Read-only inspection of `rapid-pilot/docker-entrypoint.sh` and `docker-bootstrap.php` confirms ordinary startup invokes the image's migration/bootstrap path and refreshes the generation sentinel nonce and matching active manifest. This must be accounted for explicitly when reusing the existing image; the v0.1 approval did not approve that additional operational effect.

Version 0.2 permits only the `manifest_nonce` field of the existing generation sentinel to differ in the database, and requires it to match the new active.json value. Generation, fingerprint, every other database row/field and all DDL retain exact preservation. A whole-table exclusion or unexplained bootstrap difference would violate the contract. Previously existing session bytes remain exact; authenticated smoke checks should use a new task-owned session so legitimate new token storage does not alter those preserved files.

The exact existing image and volumes are retained. This does not authorize a new migration, production import, grant, source build, image update or remote mutation. Existing bootstrap execution is allowable only within the stated observed preservation result; an idempotence assumption without before/after evidence is insufficient. The local configuration and native Users HTTP contract are unchanged, and all v0.1 assessments for those seams remain applicable.

Only this new review record was added after read-only inspection of the revised specification/design and startup code. No configuration, test, runtime resource or source file was changed or executed. Gate 1 v0.2 is **APPROVED**; independent test approval, GREEN and Gate 5 still precede operational recovery. Neither review record is full verification or launch approval.
