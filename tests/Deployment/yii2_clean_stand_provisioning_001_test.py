#!/usr/bin/env python3
import pathlib, sys
sys.path.insert(0, str(pathlib.Path(__file__).resolve().parents[2]))
from tests.Support.clean_stand_contract import CleanStandFixture,result,CALLS,IDS

f = CleanStandFixture()
try:
    run = f.run("run")
    payload = result(run)
    assert run.returncode == 0 and payload["reason"] == "CLEAN_STAND_CONTRACT_VERIFIED"
    facts = payload["facts"]; trace=f.trace()
    assert facts["database"] == {"fresh": True, "schemaVersion": 30, "migrationCount": 30}
    assert facts["provisioning"]["replayCreated"] == [] and facts["provisioning"]["sameFacts"] is True
    assert facts["legacyInputs"] == []
    assert [row["call"] for row in trace]==CALLS
    assert trace[1]["call"]=="compose.create" and trace[2]["call"]=="postcreate.inspect" and trace[2]["observedIds"]==IDS
    assert trace[2]["sequence"] < trace[3]["sequence"],"post-create exact identity is checked before database creation"
    events = [row["event"] for row in f.records()]
    assert events.index("TARGET_ATTESTED") < events.index("DATABASE_CREATED") < events.index("MIGRATIONS_COMPLETED") < events.index("USERS_PROVISIONED")
finally: f.close()

for reason,mutate in [
    ("POSTCREATE_LABEL_MISMATCH",lambda x:x.driver["postcreate"]["labels"].__setitem__("project","other")),
    ("POSTCREATE_IMAGE_MISMATCH",lambda x:x.driver["postcreate"].__setitem__("image","fmonitor2-runtime@sha256:"+"e"*64)),
    ("POSTCREATE_CONTAINER_MISMATCH",lambda x:x.driver["postcreate"]["ids"]["containers"].__setitem__("php","e"*64)),
    ("POSTCREATE_NETWORK_MISMATCH",lambda x:x.driver["postcreate"].__setitem__("networkAttachments","e"*64)),
    ("POSTCREATE_VOLUME_MISMATCH",lambda x:x.driver["postcreate"]["volumeMounts"].__setitem__("state","e"*64)),
]:
    x=CleanStandFixture()
    try:
        mutate(x); x.sync(); failed=x.run("run"); assert failed.returncode!=0 and result(failed)["reason"]==reason
        assert [r["call"] for r in x.trace()]==["precreate.inspect","compose.create","postcreate.inspect"]
    finally:x.close()
print("PASS: YII2-CLEAN-STAND-CUTOVER-001 fresh provisioning")
