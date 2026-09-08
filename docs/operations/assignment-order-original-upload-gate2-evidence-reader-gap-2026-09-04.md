# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — Gate 2 evidence-reader constructibility gap

Date: `2026-09-04`

RED author: separately tasked agent `/root/assignment_original_red`

Outcome: **GATE 1 AMENDMENT REQUIRED FOR REMAINING TASK 2.2**

Task 2.2 part 1 is committed at `b0f1e60`. While constructing the required
MariaDB/five-FD/fault/maintenance matrix, the approved PHP contract exposes
`AssignmentOrderOriginalEvidenceReader` only as an interface. It declares the
required domain/request/fingerprint/event/audit/process/blob/log reads, but no
production or verification factory, constructor, configuration, or callable
adapter owner is declared.

This is blocking because section 16 simultaneously requires MariaDB acceptance
to use the production repository plus this read-only evidence adapter on a fresh
connection, forbids tests from querying private tables or inferring a future
schema, and requires exact observation of all eight evidence families. The
command worker bootstrap returns only a Result line; it does not expose an
evidence reader. `ProductionAssignmentOrderOriginalFactory` returns only the
application, and the repository's public evidence method exposes only domain
JSON. Storage exposes only blob inventory. Requests, fingerprints, events,
audits, unchanged process and safe logs therefore have no approved constructible
MariaDB observation path.

A RED author cannot close this by implementing the interface over guessed table
names: the additive schema is deliberately deferred to task 3.1, and such a
reader would encode private implementation. An in-memory reader cannot prove
the mandatory shared MariaDB/CAS/commit-loss/maintenance outcomes. A reader
backed by the same fake repository would self-attest the mutations it is meant
to observe.

The smallest Gate 1 amendment is an exact factory signature for the independent
evidence adapter, including its `mysqli`/prefix/private-root/log-observer inputs
and whether it owns or borrows the connection. If separate production factories
for repository/storage are intended for parent-process fault verification, their
signatures must also be explicit; otherwise the evidence factory must be
sufficient beside the existing application and worker factories.

No production, test, OpenSpec or review file was changed for this finding. The
remaining task 2.2 matrix is not claimed complete and must not proceed by
inventing the missing public construction seam.
