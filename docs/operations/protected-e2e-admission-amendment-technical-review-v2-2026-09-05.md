# Protected E2E admission amendment — independent technical rereview v2

- Recorded: `2026-09-05`
- Reviewer: `/root/protected_e2e_gate1_scope_audit`
- Candidate SHA-256: `447c9197d0cb6f99821aef8f0c973163e41f1d51609c898982ffbc49d8b16d63`
- Verdict: **CHANGES_REQUIRED**
- Scope: planning/technical Gate 1 readiness only; no owner approval claim and
  no candidate, specification, test or production edit.

Revision 2 resolves the previous gate blocker. It defines an independently
executable public test-support oracle with an explicit missing-oracle assertion
RED, independent Gate 3, minimal test-support GREEN and Gate 5. It also keeps
the full protected E2E unskipped and classifies its later failure separately.
This is compatible with the mandatory gates and requests no waiver. Its
semantic `ul|ol`/one-`li`/numeric-link/no-table representation remains correctly
derived from `PILOT-UI-SHELL-001` section 5.

## Blocking finding — fictional positive does not constrain real approved markup

The candidate says facts are matched as “separate values,” but its positive
literal places address and entrance in separate `span` elements. The current
approved configured response places
`Москва, ул. Примерная, д. 10 · Подъезд 2` in one descendant text node, while
the dates occupy another node. An implementation can therefore pass both
fictional positives by requiring an exact normalized descendant-node value for
the address and still reject the real approved HTTP response. Conversely,
concatenating all `li.textContent` without defined inter-node separators can
join `Подъезд 2` directly to the following date and make boundary checks
implementation-dependent.

Revise the appendix to specify a markup-independent DOM-text algorithm that
accepts both the literal positives and the approved production composition.
For example, normalize each descendant text-node run independently and require
the fixed facts within those runs with explicit Unicode token/delimiter
boundaries; do not require fixture-only element boundaries or CSS classes.
Define the entrance token so `Подъезд 2` is accepted at a text-run boundary or
before/after an approved separator, while `Подъезд 22`, `Подъезд 20` and bare
numeric substrings cannot satisfy it. Apply equally explicit boundaries to the
ID, registration number, address and both dates.

The literal sensitivity matrix must then include actual one-change negatives
for `Подъезд 22` and `Подъезд 20`. The prose currently claims those values
cannot pass, but neither appears in the enumerated negative inputs. Add a
positive shaped like the approved response, with address plus entrance in one
text container and the date range in another, so Gate 3 can prove the oracle is
independent of the first fictional fixture's markup.

All other reviewed boundaries are ready: missing/duplicate/wrong object,
wrong link placement, missing facts, duplicate main, forbidden table/wrapper,
literal expectations independent of renderer output, two real HTTP
observations, separate protected unapplied-patch review, and unchanged
downstream execution. After the text-matching algorithm and matrix are made
exact, return the new candidate hash for rereview.

## Exact reviewed bytes

```text
447c9197d0cb6f99821aef8f0c973163e41f1d51609c898982ffbc49d8b16d63  docs/operations/proposed-protected-e2e-admission-amendment-2026-09-05.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
d2b98ae8103feabbc3511e4f5394dd580c790a74f64c66d0f8f9e6d4acfb069b  app/PilotHttp/ObjectListView.php
a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```
