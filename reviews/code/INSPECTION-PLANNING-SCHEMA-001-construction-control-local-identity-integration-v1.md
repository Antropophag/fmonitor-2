# INSPECTION-PLANNING-SCHEMA-001 — construction-control local identity Gate 5

Date: 2026-09-04  
Reviewer: `/root/inspection_planning_local_gate5` (independently tasked; did
not author or edit the reviewed specification, tests, production, or GREEN
evidence)  
Reviewed production commit: `07526e28ce6349bdd06e8334c09122fb8e846a02`  
Reviewed Gate 3 commit: `76a69c1ef040568b4ee3d139f9b26f79c9139ec7`  
Verdict: **APPROVED**

## Exact reviewed artifacts

```text
32ce40ab118f1c39e90000c26753efd510d9b0cf4e3c6285c2de61eddc8534cb  app/PilotHttp/PilotE2ECoordinator.php
910fce64bfc23ec73a4b813effabe6ab1b1dfb503963eafd6c402dd06ebbb9ba  app/PilotHttp/LocalAuthenticatedHttpUser.php
7bcaf243ed9c98d05ba7a3884f7a325f6d7dec46b955545d72ecaa37b6cb4931  tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php
e0d9f2204a4df01bd44bb2a5af60827e6f6a4a4ad9b4b4bf97eb33cfaf71e2fb  tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
2c1ad6eb756f93dbec58b6d45833aaa756087a7c5eaa8075078cee78a4972fa4  reviews/tests/INSPECTION-PLANNING-SCHEMA-001-construction-control-local-identity-integration-v1.md
5937d7a031c63f6a556d55d24ccf0ffb5852487c80ea98d14dacbb9bb887d093  docs/operations/inspection-planning-local-identity-green-2026-09-04.md
```

The production commit changes one expression in the ordinary read-route branch
and adds the GREEN evidence record. It changes no test, schema, domain command,
root/card rendering, or rapid-pilot implementation.

## Standards axis

**APPROVED.** The construction-control route delegates to the already reviewed
`LocalAuthenticatedHttpUser::resolve(...)` seam instead of duplicating local
profile or permission rules. It supplies the literal
`AccessPolicy::CONSTRUCTION_CONTROL_READ`. A present trusted local actor ID is
therefore resolved to one active local profile whose display identity comes
from that profile and whose exact permission is required. Invalid, inactive,
missing-profile, or unauthorized present local identities return no user and
cannot acquire authority or representation identity from `REMOTE_USER` or
legacy account rows.

Legacy lookup remains available only inside the shared resolver when
`FMONITOR_AUTH_USER_ID` is absent. The following existing capability check is
redundant for a successful local resolution but is a safe defense-in-depth
check using the same exact capability; it neither broadens authority nor adds a
second identity implementation.

The conditional is exact: only `/pilot/construction-control` takes the local
resolver. `/pilot/objects` and object-card routes retain their previous
`resolveActiveUser($principal)` call and their remaining behavior is byte
unchanged. No new state owner, write seam, runtime DDL, secret-bearing response,
or history mutation is introduced.

## Spec axis

**APPROVED.** The unchanged public HTTP planning verifier now reaches the
approved healthy sequence `303/200/200/200`, including construction-control,
with the deliberately absent legacy identity family. That proves the positive
identity is the configured local actor with its active local role and exact
`construction_control.read` grant, not the legacy decoy principal.

The same verifier proceeds through both missing and incompatible planning
families. Each remains fail-closed and its complete schema, rows, row bytes,
and counters remain identical before and after rejected schedule, Calendar,
object-list, construction-control, and repeated object-list requests. Thus the
identity correction does not weaken the planning precondition or manufacture
repair/mutation. The separately approved local-auth endpoint regression retains
the negative local-ID and legacy-fallback matrix required by Gate 3.

## Independent verification

```text
php tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php
PASS: INSPECTION-PLANNING-SCHEMA-001 real HTTP/Compose DML-only contract

php tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
PASS: INSPECTION-ITEM-COMPLETE-001 raw HTTP endpoint admission

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

make lint
exit 0

git diff --check
exit 0

openspec validate canonicalize-inspection-planning-schema --strict
Change 'canonicalize-inspection-planning-schema' is valid
```

`make verify` was also run with Docker 29.7.2 after explicitly adding the
installed Docker.app binary directory to this session's PATH. The reviewed
planning verifier and local-auth suites were GREEN in their canonical stages.
The command ended with the independently visible aggregate:

```text
FULL_VERIFICATION_FAILURE count=4 stages=unit-test,db-test,characterization-test,e2e-test
```

Those failures are outside this production delta: the two intentional
original-upload RED tests lack their planned verification factory; existing
completion/card/auth and blocked legacy E2E predecessors remain non-GREEN; and
the duplicate-schedule characterization reported a subprocess PATH/setup
failure. None changes or contradicts the passing construction-control planning
and local-identity observations. This review does not approve those residuals.

## Gate decision

Gate 5 for the construction-control local-identity integration at production
commit `07526e28ce6349bdd06e8334c09122fb8e846a02` is **APPROVED**. No blocking
Standards, security, scope, append-only-history, or Spec finding remains for
this exact slice.
