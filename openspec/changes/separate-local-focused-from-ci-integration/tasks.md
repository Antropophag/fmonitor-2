## 1. Contract and RED

- [x] 1.1 Record #181 scope, stable specification, lifecycle artifacts and #187 comparison input
- [x] 1.2 Add executable RED covering placement, multi-reason promotion, focused failure, reviewer package and unchanged CI failure admission
- [x] 1.3 Prepare the plan and obtain independent Gate 3 approval

## 2. Implementation

- [x] 2.1 Extend the existing planner command model and deduplication with local/CI placement and preserved reasons
- [x] 2.2 Update focused runner and prepared-package consumers to respect placement without fake evidence
- [x] 2.3 Preserve existing CI selection/aggregate and fail-closed policy/ownership behavior

## 3. Delivery

- [x] 3.1 Run planner-selected bounded checks and record #187 before/after local counts plus unchanged CI obligations
- [x] 3.2 Obtain independent Gate 5 approval of exact source
- [ ] 3.3 Publish one PR and run one exact-source GitHub CI; do not merge/deploy/change settings
