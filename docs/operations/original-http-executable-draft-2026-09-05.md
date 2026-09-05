# Original HTTP executable draft — 2026-09-05

Author: `/root`. Base: `eaef9ebbd14cd34e1fe7faf85bde0a6968c0d0db`.

Created `ASSIGNMENT-ORDER-ORIGINAL-HTTP-001` v0.1 from the approved application
command and reader owner decision. It covers proposed POST/metadata/download
routes, actor admission, command Result mapping, immutable revision reads,
OTIZ global scope, assigned-engineer scope and original preservation.

The draft is not Gate 1 ready: exact transport error envelope/precedence,
revision URI grammar, context resolver/read API, object-scope source,
stream-integrity/conditional response behavior and legacy route disposition
remain explicit. No production or tests changed and no route became live.

The direct-upload dependency is now explicit. Inspected
`InstallationProcess::prepareAssignmentOrder` calls
`environment->renderAssignmentOrder` before storing prepared artifacts.
The original upload command expects an existing order/composition. These
observations do not prove a render-free composition-selection seam exists.
Before claiming direct-upload HTTP parity, the implementation must identify
or deliver that public seam through its own gates. An implicit template render
or test-created order is not proof of the end-user direct-upload scenario.

Example digest/size in the draft are copied from the normative independently
fixed command corpus (327 bytes, SHA-256 4028af37...), not calculated from
production output. The proposed HTTP mapping does not self-approve those
transport decisions.

Exact draft SHA-256:
`a20d0010a8397512332aec905ba686b6ba7444dbf2e805213beea5f4edff9fa7`.
`git diff --check`: exit 0. Full launch goal remains incomplete.
