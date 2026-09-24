# Issue #256 — классификация backlog labels

## Authorization и authorship

- Owner authorization: явные поручения «Реализуй 256» и «применяй» в текущей
  root-session 2026-09-24.
- Root: scope, OpenSpec, `BACKLOG-ISSUE-LABELS-001`, executable acceptance и
  verification input.
- Executor: separate `gpt-5.6-sol/low` executor
  `/root/issue256_executor`, tasks 2.1–5.1.
- Independent pre-mutation classification reviewer: separate
  `gpt-5.6-sol/low`; first review returned #251, corrected plan
  `d7cd180708773a83ffd09c8335c817c3807babee4c84b44190c0ead895310f27`
  was APPROVED. Formal Gate 3 and final reviews were independently APPROVED
  with zero findings for the pre-publication candidate; their harness verdicts
  do not make PR or CI GREEN.

## Состояние

- Base: `origin/main` `199e1b38257bafd21e9254f2019f169dec641de1`.
- Before snapshot (UTC): `2026-09-24T16:27:47Z`, 11 repository labels and 38
  paginated open issues excluding pull requests.
- After reread: `2026-09-24T16:37:35Z`, 21 repository labels and the same 38
  open issues; no skipped closed or newly discovered issues.
- Exact evidence:
  `/Users/antropophag/.local/share/fmonitor-2/issue-256/final-evidence.json`,
  SHA-256 `524bca794ef2c4796b01447fae8661c4d2863b6fe724e16b0b0d0c5ad623a48a`.
- PR и exact-source CI: `UNKNOWN`.
- Merge/deployment: не разрешены.

## Охват и результат

Полный набор 38/38 классифицирован после чтения bodies, существенных comments,
timeline relations, связанных PR/issues и актуальных source consumers.

- `type:`: product 14, harness 9, tracking 9, tech-debt 6.
- `prep:`: ready 24, needs-work 3, triage 2; у девяти tracking issues prep
  отсутствует.
- `status:`: blocked 2, deferred 3, in-progress 2.
- `prep:needs-work`: #28, #29, #219. К каждой добавлено одно конкретное
  недублирующее пояснение.
- `status:blocked`: #95 зависит от #107; #255 зависит от #14. К каждой добавлено
  одно конкретное недублирующее пояснение.
- `status:deferred`: #145, #153, #233; `status:in-progress`: #197, #256.

Фактически применены 89 успешных GitHub operations: 10 `label.upsert`, 74
точечных `issue.label.add` и 5 `issue.comment`. Failed/UNKNOWN operations,
label removals, закрытые issue, pull requests и новые issue отсутствуют.
Сторонние labels и `quality-graph:*` сохранены. Exact metadata десяти labels и
кардинальность итогового набора подтверждены повторным API-чтением; dry rerun
вычислил zero mutations/comments.

Найденные stale/duplicate границы не исправлялись этой поставкой: #18, #24,
#145, #153, #169, #174, #179 и #251 классифицированы как tracking; для #29 и
#219 уже поставленные части отделены от непроработанного остатка. Issues не
закрывались и priorities не менялись.

GitHub mutations уже действуют и не откатываются автоматически исходом repository PR.
PR, exact-source CI, merge и deployment остаются `UNKNOWN` до
последующих Gates 3–5.
