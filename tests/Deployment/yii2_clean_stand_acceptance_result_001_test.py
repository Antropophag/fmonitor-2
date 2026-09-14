#!/usr/bin/env python3
import json
import pathlib, sys
sys.path.insert(0, str(pathlib.Path(__file__).resolve().parents[2]))
from tests.Support.clean_stand_contract import CleanStandFixture,result,sha,CALLS

f = CleanStandFixture()
try:
    first = f.run("run"); payload = result(first)
    assert first.returncode == 0 and payload["reason"] == "CLEAN_STAND_CONTRACT_VERIFIED"
    accepted = f.evidence / "contract-verified.json"; assert accepted.is_file() and not (f.evidence/"accepted.json").exists()
    record = json.loads(accepted.read_text())
    assert record["operationId"] == f.operation and record["source"] == f.manifest["source"] and record["image"] == f.manifest["image"]
    assert record["authorizationDigest"] == sha(f.authorization_path)
    before = f.tree(); replay = f.run("run"); assert result(replay) == payload and f.tree() == before
    forbidden = json.dumps([row.get("event") for row in f.records()])
    for marker in ("RESTORE", "ROLLBACK", "RECONCILE", "PRODUCTION_CUTOVER", "DELETE_OLD_STAND"): assert marker not in forbidden
finally: f.close()

for fail_at in CALLS[1:-1]:
    x=CleanStandFixture()
    try:
        x.driver["failAt"]=fail_at; x.sync(); failed=x.run("run")
        assert failed.returncode!=0 and result(failed)["reason"] in {"STEP_FAILED","OUTCOME_UNKNOWN"},fail_at
        assert not (x.evidence/"accepted.json").exists() and not (x.evidence/"contract-verified.json").exists() and all(row.get("call")!="evidence.accept" for row in x.trace())
    finally:x.close()

for interrupt_at in ("schema.migrate","runtime.start","runtime.include-traces","evidence.accept.before-rename","evidence.accept.after-rename"):
    x=CleanStandFixture()
    try:
        x.driver["interruptAt"]=interrupt_at; x.sync(); interrupted=x.run("run")
        assert interrupted.returncode!=0 and result(interrupted)["reason"]=="OUTCOME_UNKNOWN"
        if interrupt_at!="evidence.accept.after-rename": assert not (x.evidence/"accepted.json").exists()
        x.driver["interruptAt"]=None; x.sync(); repaired=x.run("run"); assert repaired.returncode==0 and result(repaired)["reason"]=="CLEAN_STAND_CONTRACT_VERIFIED"
        effects=[r["call"] for r in x.trace() if r.get("stateChanging")]
        assert len(effects)==len(set(effects)),(interrupt_at,effects)
    finally:x.close()

x=CleanStandFixture()
try:
    assert x.run("run").returncode==0; before=x.tree(); x.authorization["authorizationId"]="00000000-0000-4000-8000-000000000001"; x.sync()
    conflict=x.run("run"); assert conflict.returncode!=0 and result(conflict)["reason"]=="REPLAY_CONFLICT"
    after=x.tree(); assert {k:v for k,v in after.items() if k!="clean-stand-operations.jsonl"}=={k:v for k,v in before.items() if k!="clean-stand-operations.jsonl"}
finally:x.close()
print("PASS: YII2-CLEAN-STAND-CUTOVER-001 result/replay/non-cutover")
