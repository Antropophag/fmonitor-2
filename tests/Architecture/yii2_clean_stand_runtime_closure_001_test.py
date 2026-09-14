#!/usr/bin/env python3
import pathlib, sys
sys.path.insert(0, str(pathlib.Path(__file__).resolve().parents[2]))
from tests.Support.clean_stand_contract import CleanStandFixture, result

f = CleanStandFixture()
try:
    closure = result(f.run("run"))["facts"]["closure"]
    assert closure["imageHasRapidPilot"] is False and closure["loadedLegacy"] == []
    assert closure["commands"] == {"web": "nginx", "php": "php-fpm", "jobs-worker": "php bin/yii jobs/worker", "jobs-scheduler": "php bin/yii jobs/scheduler"}
    traces={x["call"]:x for x in f.trace()}; assert traces["runtime.image-inventory"]["image"]==f.manifest["image"]
    assert {x["invocation"] for x in traces["runtime.include-traces"]["traces"]}=={"http.health","http.login","http.golden","migration","jobs"}
    for t in traces["runtime.include-traces"]["traces"]:
        assert t["source"]==f.manifest["source"] and t["image"]==f.manifest["image"] and t["operationId"]==f.operation and len(t["artifactSha256"])==64
        expected="4"*64 if t["invocation"]=="jobs" else "2"*64; assert t["containerId"]==expected
        assert all("rapid-pilot" not in p and "RuntimeRecovery" not in p for p in t["files"])
finally: f.close()

for mutate in [
    lambda x:x.driver["observations"]["imageInventory"]["paths"].append("/workspace/fmonitor-2/rapid-pilot/router.php"),
    lambda x:x.driver["observations"]["processes"][2]["argv"].append("RuntimeRecovery"),
    lambda x:x.driver["observations"]["includeTraces"][0]["files"].append("/workspace/fmonitor-2/app/RuntimeRestore/RuntimeRecovery.php"),
    lambda x:x.driver["observations"]["includeTraces"][0].__setitem__("source","e"*40),
    lambda x:x.driver["observations"]["includeTraces"][1].__setitem__("image","fmonitor2-runtime@sha256:"+"e"*64),
    lambda x:x.driver["observations"]["includeTraces"][2].__setitem__("operationId","00000000-0000-4000-8000-000000000000"),
    lambda x:x.driver["observations"]["includeTraces"][3].__setitem__("artifactSha256","bad"),
    lambda x:x.driver["observations"]["includeTraces"][4].__setitem__("containerId","2"*64),
]:
    x=CleanStandFixture()
    try:
        mutate(x); x.sync(); failed=x.run("run"); assert failed.returncode!=0 and result(failed)["reason"]=="LEGACY_RUNTIME_REACHABLE"
    finally:x.close()
print("PASS: YII2-CLEAN-STAND-CUTOVER-001 runtime closure")
