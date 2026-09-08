# Gate 5 code review — ORDER-PREPARE-LATENCY-001

Verdict: **APPROVED** for the deployment candidate. No blocking code finding.

Review date: 2026-09-08 Europe/Moscow. Exact candidate:
`0266baaaaf2225188e8984363af4c73b37d5dc11`; comparison base:
`c8e09642ff0e376963ff328e63c5b8c3422fba08`.

## Scope and implementation

The production diff is confined to `rapid-pilot/docker-entrypoint.sh`. It adds
`nodelay` to both the listening and destination TCP addresses of the existing
MariaDB socat relay. The loopback bind, port 23306, fork/reuse behavior,
destination name and port, migration/bootstrap order, credentials, and web relay
are unchanged. No SQL, domain rule, authorization rule, data mutation, public
route, or response shape changed.

Applying the socket option to both independently created TCP legs matches the
observed failure: many small prepared-statement exchanges crossed the relay while
server-side SQL remained fast. The option does not expose a new listener or
weaken connection admission. Unsupported-option failure remains fail-fast under
the entrypoint's existing `set -e` startup behavior.

## Regression evidence

The committed browser probe is unchanged from independent test review, SHA256
`ea9d168a6f47ee640a04babd971ac96eccf4b216ac3d7c006d549d38d250569e`.
The private synthetic runner is also unchanged, SHA256
`dd02577b13e1a96d3c1fda4c8cc3aa9e5a485a9cbc4f1883148a8b555cd5004e`.
It uses a disposable canonical-v19 fixture, native authenticated session, 23
full-proof unknown-employment workforce rows, and the public browser operation.
It compares all fixture rows before and after and closes the fixture in `finally`.

The direct control log remains SHA256
`1ac76db4acf5a57cb13d7640436331720c1f5869c82b7732cb26cf6a676b2ed8`.
Candidate proxy GREEN log SHA256 is
`df07d8a753f7ae5e842241ce3c8abc577883ac233cd0133421e020166385e15e`.
Both contain all six iteration rows, `browserExit: 0`, 20 results per search,
zero reported browser errors, and `historyPreserved: true`.

Using the specified paired calculation after discarding iteration zero, direct
median search readiness was 394.699 ms and candidate-proxy median was 366.626 ms.
The -28.073 ms measured overhead passes the maximum +200 ms allowance. Median
form readiness was 197.76 ms direct and 211.67 ms through the candidate proxy.
The negative search difference is ordinary run variance and is not interpreted
as a speedup over a physical direct connection.

The earlier unchanged-proxy RED was 875.434 ms, +480.735 ms over direct, so the
same measurement is sensitive to the transport fault. The candidate proxy used
the same socat build and exact candidate address options, with only the isolated
test destination remapped. This proves the changed transport behavior without a
source-string mirror test. It does not by itself claim that a built/deployed
candidate entrypoint has started successfully; the planned architecture/E2E,
image startup, and post-deployment browser check cover that remaining boundary.

Focused suites reported PASS on the isolated database: selection HTTP admission,
flow and failures; native selection outcomes; installer-search HTTP;
unknown-employment selection; and pilot bootstrap. These are sufficient adjacent
guards for a transport-only diff. Repeating unrelated test categories before the
planned full CI run is not required by this review.

## Operations and cleanup

The synthetic browser runner's fixture cleanup and database history assertion are
sufficient for the application-owned evidence. The separately owned proxy
container/network are outside that runner; their exact names and isolated ports
are recorded, and root remains responsible for removing them after the candidate
measurements. This is acceptable because they contain no primary data and do not
share the owner stand, but cleanup should be recorded in the operational handoff.

Before a deployment/readiness claim, append the exact candidate commit, GREEN log
hash, this review verdict, built image identity, proxy cleanup result, and actual
candidate startup/browser evidence to the operational report. The current report
already contains the measured GREEN values but its closing text still says the
production change and code review will be recorded later. This documentation
handoff is non-blocking for the code verdict and should not remain stale at final
handoff.
