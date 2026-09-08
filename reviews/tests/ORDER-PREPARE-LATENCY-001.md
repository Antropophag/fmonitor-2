# Independent test review — ORDER-PREPARE-LATENCY-001

Verdict: **APPROVED** as focused manual regression evidence before the
deployment-only transport change. Production was not changed at review time.

Review date: 2026-09-08 Europe/Moscow. Reviewed source:
`c8e09642ff0e376963ff328e63c5b8c3422fba08`.

Reviewed inputs:

- reusable public browser probe
  `tools/diagnostics/measure-order-prepare.cjs`, SHA256
  `ea9d168a6f47ee640a04babd971ac96eccf4b216ac3d7c006d549d38d250569e`;
- private synthetic runner `synthetic-browser.php`, SHA256
  `dd02577b13e1a96d3c1fda4c8cc3aa9e5a485a9cbc4f1883148a8b555cd5004e`;
- direct evidence `browser-direct.log`, SHA256
  `1ac76db4acf5a57cb13d7640436331720c1f5869c82b7732cb26cf6a676b2ed8`;
- unchanged-proxy RED evidence `browser-proxy-red.log`, SHA256
  `3bba9b4a18a1d6053cf827eb105ff5d61e2c1359a571a4e61df0d9069467d2bc`.

The reusable probe observes the public user operation: an authenticated actor
opens the object card and order-selection form, opens the installer dialog,
searches through the real HTTP endpoint, waits for a bounded result page, and
selects one result locally. Its request guard permits only loopback same-origin
GET requests. It rejects POST and external traffic, reports timings and counts
without credentials, and treats browser, console, request, or HTTP failures as a
failed run.

The synthetic runner supplies a disposable native-auth session, canonical v19
schema, one object, eligible engineer data, and 23 unknown-employment installers
with a valid full-workforce proof. It invokes the exact reusable probe and checks
that database rows are unchanged after the browser journey. Cleanup remains in a
`finally` path. The paired database proxy and its container are task-owned and
separate from the owner stand.

The RED is sensitive to the confirmed fault. After discarding iteration zero,
the five-sample direct median `searchMs` was `394.699 ms`; the unchanged proxy
median was `875.434 ms`. The additional `480.735 ms` exceeds the specified
`200 ms` paired allowance. Both runs returned 20 selectable results and retained
fixture history. The old proxy used the same effective socat options as the
current entrypoint, with only its task-owned endpoint remapped.

The specification now makes the paired calculation explicit: six iterations,
discard cold iteration zero, compare the medians of the five retained
`searchMs` values, and require proxy overhead at most 200 ms. The probe's own
1000 ms default remains a live diagnostic threshold and is not used as the
paired regression gate.

Focused neighboring evidence reported PASS before this review: selection HTTP
admission, selection HTTP flow, and installer search HTTP. These retain the
authorization, command, result-bound, and workforce semantics around the
deployment change. This timing verifier stays outside common CI to avoid turning
host scheduling into a product failure.

No Docker infrastructure framework or source-string assertion for a particular
socat option is required. GREEN still needs the same paired verifier, unchanged,
against the candidate proxy plus the focused suites. Record exact candidate
source/image and GREEN log hashes before deployment.
