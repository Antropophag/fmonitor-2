# Original HTTP readers — owner decision

Date: 2026-09-05. Recorded by `/root`.
Repository base: `e9d94f3c48a92e7865e3ba7b739a03ac88e1bafd`.

## Explicit approval

The owner answered: «Все как ты сказал + ОТиЗ должен иметь доступ ко всем
распоряжениям» to the proposed original/history download policy.

Approved scope includes viewing/downloading original evidence and prior
immutable revisions:

- FKR operator and FKR manager: objects accessible to them.
- Construction-control engineer: objects assigned to that engineer.
- OTIZ specialist: all assignment orders, without an engineer-assignment
  restriction.
- Administration alone does not confer document-read access.

The HTTP contract binds this scope to explicit
`assignment_order.original.read`; upload/correction/opening are not implied.
Exact runtime grants, scope predicates and admission still require executable
Gate 1, RED and independent reviews. This decision resolves the owner-choice
blocker in the prior read-grants evidence without rewriting that record.

## Amended planning artifact SHA-256

```text
1baee847d605dfc70cb4fbe3cabe624f7a8124b232dc3ccc63229456af6e3b0e  openspec/changes/expose-assignment-order-original-http/proposal.md
9a609815fb6196718aef598983f6c2b64feaa6b74b23ce6c505be47d4b5da680  openspec/changes/expose-assignment-order-original-http/design.md
e15bd9f17540fc4198bd89aaac68a2e1ebf4bb3a1bb4ad88ab4b7a7dc2485816  openspec/changes/expose-assignment-order-original-http/tasks.md
e7e48ffe60acc90db39018ac6ac1ff47ed385f567d4ccdee57efbe41de35a904  openspec/changes/expose-assignment-order-original-http/specs/pilot/assignment-order-original-http/spec.md
```

Strict OpenSpec validation passed. No production, executable tests or actual
permissions were changed. Full command Gate 5 remains a predecessor for HTTP
implementation. This is owner policy evidence, not Gate 1 or launch approval.
