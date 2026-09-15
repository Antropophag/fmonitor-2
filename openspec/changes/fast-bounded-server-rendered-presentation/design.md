# Design

Reuse the existing boundary entries and their `tests` ownership. A boundary may
declare the single supported `fast_class`; policy validation requires exact known
metadata and registered tests. Classification admits the new class only when all
effective boundaries are declared presentation owners or the existing bounded-ui
companion, and exactly one boundary-owned public oracle is selected. Acceptance
input alone cannot nominate the proof oracle.

The plan records the class, reason, oracle and the closed negative boundary list.
Existing critical boundary selection runs first. Semantic surfaces from #153A and
sensitive offline boundaries from #132 remain authoritative and prevent FAST.
Unknown, controller, query, route/config, test, spec and policy paths retain their
current conservative behavior. This is a closed policy extension, not a source
semantic analyzer; adding another owner/oracle requires a separately reviewed
policy change.

Historical replay uses product-only snapshots from prior completed work so that
delivery artifacts do not distort the classifier comparison. The actual #160
policy implementation itself is CRITICAL and follows Gates 1–5.
