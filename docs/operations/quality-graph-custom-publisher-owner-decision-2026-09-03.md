# Quality Graph custom publisher owner decision

- Date: 2026-09-03
- Specification amendment: `QUALITY-GRAPH-GOVERNANCE-001` v0.6
- Decision: `APPROVED`
- Evidence: after reviewing why compiler v0.1.7's generated `issue_comment` command job and write permissions conflict with FMonitor independent review gates, the repository owner replied `Согласован.` and then instructed `Продолжай.`
- Selected behavior: repository-owned `workflow_run` publisher using pinned upstream `watch`/`publish`, with only `actions: read`, `contents: read`, `checks: write`; no comment command or approval surface.
- Non-goals remain: no merge, branch-protection change, old-harness removal or parity waiver.
