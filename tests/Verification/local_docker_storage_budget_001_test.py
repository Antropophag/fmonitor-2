#!/usr/bin/env python3
"""Executable contract for LOCAL-DOCKER-STORAGE-BUDGET-001 A-N."""

from __future__ import annotations

import json
import os
import pathlib
import re
import shutil
import signal
import subprocess
import tempfile
import time
import yaml

ROOT = pathlib.Path(__file__).resolve().parents[2]
GUARD = ROOT / "tools/delivery/docker-storage-guard"
DISPOSABLE = ROOT / "tools/delivery/run-disposable-compose"
RUNNER = ROOT / "tools/delivery/run-in-profile"
BUILDKIT = ROOT / "tools/delivery/buildkitd.focused.toml"
CI_RUNTIME = ROOT / ".github/actions/setup-runtime/action.yml"
GIB = 1024**3
GB = 1000**3


def require(value: bool, message: str) -> None:
    if not value:
        raise AssertionError(message)


def write_executable(path: pathlib.Path, body: str) -> None:
    path.write_text(body)
    path.chmod(0o755)


def fake_tools(root: pathlib.Path) -> tuple[pathlib.Path, pathlib.Path, pathlib.Path]:
    binary = root / "bin"
    binary.mkdir()
    trace = root / "docker-trace.jsonl"
    free = root / "free-bytes"
    write_executable(
        binary / "docker",
        """#!/usr/bin/env python3
import hashlib,importlib.util,json,os,pathlib,sys,time
argv=sys.argv[1:]
trace=pathlib.Path(os.environ['FAKE_DOCKER_TRACE'])
images=trace.with_suffix('.images')
with trace.open('a') as stream: stream.write(json.dumps(argv,separators=(',',':'))+'\\n')
for prefix in os.environ.get('FAKE_DOCKER_FAIL_PREFIXES','').split('|'):
    if prefix and ' '.join(argv).startswith(prefix): sys.exit(int(os.environ.get('FAKE_DOCKER_FAIL_STATUS','73')))
if argv[:2]==['buildx','version']:
    print('github.com/docker/buildx v0.32.2')
elif argv[:2]==['buildx','inspect']:
    inspect_mode=os.environ.get('FAKE_BUILDX_INSPECT_MODE','valid')
    if inspect_mode=='valid': print('Name:          fmonitor2-focused'); print('Driver:        docker-container'); print('Nodes:'); print('Name: fmonitor2-focused0')
    elif inspect_mode=='missing': print('Nodes:')
    elif inspect_mode=='missing_name': print('Driver: docker-container')
    elif inspect_mode=='missing_driver': print('Name: fmonitor2-focused')
    elif inspect_mode=='wrong_name': print('Name: foreign'); print('Driver: docker-container')
    elif inspect_mode=='wrong_driver': print('Name: fmonitor2-focused'); print('Driver: docker')
    elif inspect_mode=='empty_name': print('Name:   '); print('Driver: docker-container')
    elif inspect_mode=='empty_driver': print('Name: fmonitor2-focused'); print('Driver:   ')
    elif inspect_mode=='indented': print('  Name: fmonitor2-focused'); print('  Driver: docker-container')
    elif inspect_mode=='duplicate': print('Name: fmonitor2-focused'); print('Name: fmonitor2-focused'); print('Driver: docker-container')
    elif inspect_mode=='conflicting': print('Name: fmonitor2-focused'); print('Name: foreign'); print('Driver: docker-container')
    elif inspect_mode=='duplicate_driver': print('Name: fmonitor2-focused'); print('Driver: docker-container'); print('Driver: docker-container')
    elif inspect_mode=='conflicting_driver': print('Name: fmonitor2-focused'); print('Driver: docker-container'); print('Driver: docker')
elif argv[:2]==['buildx','create']:
    print('fmonitor2-focused')
elif argv[:2]==['buildx','build']:
    tag=argv[argv.index('--tag')+1]
    known=set(images.read_text().splitlines()) if images.exists() else set(); known.add(tag); images.write_text('\\n'.join(sorted(known))+'\\n')
elif argv[:2]==['buildx','prune']:
    update=os.environ.get('FAKE_DOCKER_PRUNE_FREE_BYTES')
    if update: pathlib.Path(os.environ['FMONITOR_DOCKER_STORAGE_FREE_BYTES_FILE']).write_text(update)
elif argv[:2]==['image','inspect']:
    tag=argv[-1]; known=set(images.read_text().splitlines()) if images.exists() else set()
    if tag not in known: sys.exit(1)
    value='sha256:'+hashlib.sha256(tag.encode()).hexdigest(); profile=tag.removeprefix('fmonitor2-focused-').split(':',1)[0]; digest=tag.rsplit(':',1)[-1]
    print(value if '{{.Id}}' in argv and 'org.fmonitor.owner' not in argv else f'{value}|focused-checks|{profile}|{digest}')
elif argv[:2]==['system','df']:
    for item in (
      {'Type':'Images','TotalCount':'4','Active':'3','Size':'3.4GB','Reclaimable':'10MB'},
      {'Type':'Build Cache','TotalCount':'10','Active':'2','Size':'12GB','Reclaimable':'8GB'},
      {'Type':'Local Volumes','TotalCount':'5','Active':'5','Size':'1GB','Reclaimable':'0B'}): print(json.dumps(item))
elif argv[:1]==['compose']:
    if 'down' in argv: sys.exit(int(os.environ.get('FAKE_COMPOSE_DOWN_STATUS','0')))
    delay=float(os.environ.get('FAKE_COMPOSE_UP_DELAY','0'))
    if delay: time.sleep(delay)
    sys.exit(int(os.environ.get('FAKE_COMPOSE_UP_STATUS','0')))
elif argv[:1]==['run']:
    mounts=[argv[index+1] for index,token in enumerate(argv[:-1]) if token=='--mount']
    workspaces=[item for item in mounts if ',dst=/workspace,' in item]
    if workspaces:
        workspace=workspaces[0]
        fields=dict(part.split('=',1) for part in workspace.split(',') if '=' in part)
        source=pathlib.Path(fields['src'])
        sys.path.insert(0,str(source/'tools/delivery'))
        spec=importlib.util.spec_from_file_location('mounted_harness',source/'tools/delivery/harness.py')
        module=importlib.util.module_from_spec(spec);spec.loader.exec_module(module)
        expected=next((item.split('=',1)[1] for index,item in enumerate(argv) if index and argv[index-1]=='--env' and item.startswith('FMONITOR_EXECUTED_SOURCE=')),None)
        mountpoints={name:{'is_dir':(source/name).is_dir(),'entries':sorted(item.name for item in (source/name).iterdir()) if (source/name).is_dir() else None} for name in ('.local','vendor','.test-artifacts')}
        witness={'src':str(source),'src_absolute':source.is_absolute(),'src_exists':source.is_dir(),'ambient_repo':str(pathlib.Path.cwd().resolve()),'workspace_mount_count':len(workspaces),'readonly':'readonly' in workspace.split(','),'marker':(source/'marker.txt').read_text().strip(),'actual_digest':module.source_details()['executable_digest'],'expected_digest':expected,'mountpoints':mountpoints}
        with pathlib.Path(os.environ['FAKE_RUN_WITNESS']).open('a') as stream: stream.write(json.dumps(witness,separators=(',',':'))+'\\n')
    sys.exit(int(os.environ.get('FAKE_RUN_STATUS','0')))
sys.exit(0)
""",
    )
    return binary, trace, free


def base_environment(binary: pathlib.Path, trace: pathlib.Path, free: pathlib.Path) -> dict[str, str]:
    env = os.environ.copy()
    env.update({
        "PATH": f"{binary}:{env['PATH']}",
            "FAKE_DOCKER_TRACE": str(trace),
            "FAKE_RUN_WITNESS": str(trace.parent / "run-witness.jsonl"),
        "FMONITOR_DOCKER_STORAGE_TEST_MODE": "1",
        "FMONITOR_DOCKER_STORAGE_FREE_BYTES_FILE": str(free),
        "FMONITOR_DOCKER_STORAGE_HARD_FLOOR_BYTES": str(50 * GIB),
        "FMONITOR_DOCKER_STORAGE_TARGET_FREE_BYTES": str(80 * GIB),
        "FMONITOR_DOCKER_STORAGE_MAX_CACHE_BYTES": str(30 * GB),
        "FMONITOR_DOCKER_STORAGE_RETENTION_HOURS": "48",
    })
    return env


def read_trace(path: pathlib.Path) -> list[list[str]]:
    return [json.loads(line) for line in path.read_text().splitlines()] if path.exists() else []


def guard_command(lock: pathlib.Path) -> list[str]:
    return [
        str(GUARD), "build", "--profile", "governance",
        "--image", "fmonitor2-focused-governance:dependency-digest",
        "--dependency-digest", "dependency-digest", "--lock-path", str(lock), "--",
        "docker", "buildx", "build", "--builder", "fmonitor2-focused", "--load",
        "--tag", "fmonitor2-focused-governance:dependency-digest", ".",
    ]


def run_guard(root: pathlib.Path, free_bytes: int | str, **extra: str) -> tuple[subprocess.CompletedProcess[str], list[list[str]]]:
    binary, trace, free = fake_tools(root)
    free.write_text(str(free_bytes))
    env = base_environment(binary, trace, free)
    env.update(extra)
    result = subprocess.run(guard_command(root / "guard.lock"), cwd=ROOT, env=env, text=True, capture_output=True)
    return result, read_trace(trace)


def tagged(prefix: str, output: str) -> list[dict[str, object]]:
    return [json.loads(line.removeprefix(prefix)) for line in output.splitlines() if line.startswith(prefix)]


def expected_build() -> list[str]:
    return ["buildx", "build", "--builder", "fmonitor2-focused", "--load", "--tag", "fmonitor2-focused-governance:dependency-digest", "."]


VERSION = ["buildx", "version"]
INSPECT = ["buildx", "inspect", "fmonitor2-focused"]
IMAGE_PRUNE = ["image", "prune", "--force", "--filter", "label=org.fmonitor.owner=focused-checks", "--filter", "until=48h"]
CACHE_PRUNE = ["buildx", "prune", "--builder", "fmonitor2-focused", "--force", "--filter", "until=48h", "--max-used-space", str(30 * GB), "--min-free-space", str(80 * GIB)]


require(GUARD.is_file(), "INTENDED_RED: owning Docker storage guard is absent")
require(DISPOSABLE.is_file(), "INTENDED_RED: disposable Compose lifecycle owner is absent")
require(os.access(GUARD, os.X_OK) and os.access(DISPOSABLE, os.X_OK), "delivery owners must be executable")
require(BUILDKIT.is_file(), "dedicated focused BuildKit configuration is absent")
pin="docker/setup-buildx-action@8d2750c68a42422c14e847fe6c8ac0403b4cbd6f"
runtime=yaml.safe_load(CI_RUNTIME.read_text())
require(runtime.get("inputs",{}).get("buildx",{}).get("default")=="false","CI runtime Buildx input must default false")
buildx_steps=[step for step in runtime["runs"]["steps"] if step.get("uses")==pin]
require(len(buildx_steps)==1,"INTENDED_RED: CI runtime does not install pinned Buildx as one active step")
require(buildx_steps[0].get("if")=="inputs.buildx == 'true'","pinned Buildx setup must be opt-in")
workflow=yaml.safe_load((ROOT/".github/workflows/quality-graph.yml").read_text())
jobs=workflow["jobs"]
def runtime_steps(job): return [step for step in job["steps"] if step.get("uses")=="./.github/actions/setup-runtime"]
for job_name in ("integration","governance"):
    steps=runtime_steps(jobs[job_name]);require(len(steps)==1 and steps[0].get("with",{}).get("buildx")=="true",f"{job_name} must opt into Buildx exactly once")
e2e=jobs["e2e"];steps=runtime_steps(e2e)
require(len(steps)==1 and "buildx" not in steps[0].get("with",{}),"E2E must not pay unrelated Buildx setup cost")
require(e2e.get("timeout-minutes")==30 and e2e.get("if")=="needs.plan.outputs.full == 'true'","E2E timeout/full-mode admission changed")
require("continue-on-error" not in e2e,"E2E job must remain blocking")
run_steps=[step for step in e2e["steps"] if step.get("name")=="Run e2e category once"]
require(len(run_steps)==1 and run_steps[0].get("run")=="make test CATEGORY=e2e" and "continue-on-error" not in run_steps[0],"E2E command or failure semantics changed")
for expected in ('gc = true', 'reservedSpace = "10GB"', 'maxUsedSpace = "30GB"', 'minFreeSpace = "80GB"'):
    require(expected in BUILDKIT.read_text(), f"BuildKit GC config misses {expected}")

# D: sufficient-space exact order and schema.
with tempfile.TemporaryDirectory() as temporary:
    result, calls = run_guard(pathlib.Path(temporary), 60 * GIB)
    require(result.returncode == 0, result.stderr)
    require(calls == [VERSION, INSPECT, expected_build()], f"unexpected sufficient-space argv/order: {calls}")
    value = tagged("DOCKER_STORAGE_GUARD ", result.stderr)[-1]
    require(value == {"action":"build","cleanup":"skipped","free_bytes":60*GIB,"hard_floor_bytes":50*GIB,"outcome":"allowed","reason":"sufficient_free_space"}, f"unexpected guard result: {value}")

# E/G/H: low-space exact bounded maintenance and recovered build.
with tempfile.TemporaryDirectory() as temporary:
    result, calls = run_guard(pathlib.Path(temporary), 40 * GIB, FAKE_DOCKER_PRUNE_FREE_BYTES=str(90 * GIB))
    require(result.returncode == 0, result.stderr)
    require(calls == [VERSION, INSPECT, IMAGE_PRUNE, CACHE_PRUNE, expected_build()], f"unexpected recovery argv/order: {calls}")
    value = tagged("DOCKER_STORAGE_GUARD ", result.stderr)[-1]
    require(value["cleanup"] == "performed" and value["free_bytes"] == 90 * GIB and value["outcome"] == "allowed", f"bad recovery result: {value}")

# F: all failures stop before build; invalid config/measurement has zero Docker effects.
with tempfile.TemporaryDirectory() as temporary:
    result, calls = run_guard(pathlib.Path(temporary), 40 * GIB)
    require(result.returncode != 0 and calls == [VERSION, INSPECT, IMAGE_PRUNE, CACHE_PRUNE], "unrecovered path must stop after exact cleanup")
    require(tagged("DOCKER_STORAGE_GUARD ", result.stderr)[-1]["reason"] == "insufficient_free_space", "wrong low-space reason")
for measurement in ("", "-1", "not-a-number"):
    with tempfile.TemporaryDirectory() as temporary:
        result, calls = run_guard(pathlib.Path(temporary), measurement)
        require(result.returncode != 0 and calls == [], f"invalid measurement {measurement!r} reached Docker")
with tempfile.TemporaryDirectory() as temporary:
    result, calls = run_guard(pathlib.Path(temporary), 60 * GIB, FAKE_DOCKER_FAIL_PREFIXES="buildx version")
    require(result.returncode != 0 and calls == [VERSION], "unsupported Buildx must fail before inspect/build")
for inspect_mode in ("missing","missing_name","missing_driver","wrong_name","wrong_driver","empty_name","empty_driver","indented","duplicate","conflicting","duplicate_driver","conflicting_driver"):
    with tempfile.TemporaryDirectory() as temporary:
        result,calls=run_guard(pathlib.Path(temporary),60*GIB,FAKE_BUILDX_INSPECT_MODE=inspect_mode)
        require(result.returncode!=0 and calls==[VERSION,INSPECT],f"ambiguous/non-top-level builder identity admitted: {inspect_mode} {calls}")
        rejection=tagged("DOCKER_STORAGE_GUARD ",result.stderr)[-1]
        require(rejection["reason"]=="unsupported_buildx" and rejection["outcome"]=="rejected",f"unstable builder rejection: {inspect_mode} {rejection}")
with tempfile.TemporaryDirectory() as temporary:
    root = pathlib.Path(temporary); blocker = root / "not-directory"; blocker.write_text("x")
    binary, trace, free = fake_tools(root); free.write_text(str(40 * GIB)); env = base_environment(binary, trace, free)
    result = subprocess.run(guard_command(blocker / "guard.lock"), cwd=ROOT, env=env, text=True, capture_output=True)
    require(result.returncode != 0 and read_trace(trace) == [VERSION, INSPECT], "lock failure reached maintenance/build")
with tempfile.TemporaryDirectory() as temporary:
    result, calls = run_guard(pathlib.Path(temporary), 40 * GIB, FAKE_DOCKER_FAIL_PREFIXES="image prune", FAKE_DOCKER_PRUNE_FREE_BYTES=str(90 * GIB))
    require(result.returncode != 0 and calls == [VERSION, INSPECT, IMAGE_PRUNE], "cleanup failure did not fail immediately")

# M: exact accepted override bounds and rejection matrix.
valid_bounds = [
    {"FMONITOR_DOCKER_STORAGE_HARD_FLOOR_BYTES":str(10*GIB),"FMONITOR_DOCKER_STORAGE_TARGET_FREE_BYTES":str(10*GIB),"FMONITOR_DOCKER_STORAGE_MAX_CACHE_BYTES":str(1*GB),"FMONITOR_DOCKER_STORAGE_RETENTION_HOURS":"1"},
    {"FMONITOR_DOCKER_STORAGE_HARD_FLOOR_BYTES":str(200*GIB),"FMONITOR_DOCKER_STORAGE_TARGET_FREE_BYTES":str(300*GIB),"FMONITOR_DOCKER_STORAGE_MAX_CACHE_BYTES":str(100*GB),"FMONITOR_DOCKER_STORAGE_RETENTION_HOURS":"720"},
]
for overrides in valid_bounds:
    with tempfile.TemporaryDirectory() as temporary:
        result, calls = run_guard(pathlib.Path(temporary), 300 * GIB, **overrides)
        require(result.returncode == 0 and calls[-1] == expected_build(), f"valid boundary rejected: {overrides} {result.stderr}")
invalid_bounds = [
    {"FMONITOR_DOCKER_STORAGE_HARD_FLOOR_BYTES":str(10*GIB-1)}, {"FMONITOR_DOCKER_STORAGE_HARD_FLOOR_BYTES":str(200*GIB+1)},
    {"FMONITOR_DOCKER_STORAGE_TARGET_FREE_BYTES":str(10*GIB-1)}, {"FMONITOR_DOCKER_STORAGE_TARGET_FREE_BYTES":str(300*GIB+1)},
    {"FMONITOR_DOCKER_STORAGE_HARD_FLOOR_BYTES":str(100*GIB),"FMONITOR_DOCKER_STORAGE_TARGET_FREE_BYTES":str(99*GIB)},
    {"FMONITOR_DOCKER_STORAGE_MAX_CACHE_BYTES":str(1*GB-1)}, {"FMONITOR_DOCKER_STORAGE_MAX_CACHE_BYTES":str(100*GB+1)},
    {"FMONITOR_DOCKER_STORAGE_RETENTION_HOURS":"0"}, {"FMONITOR_DOCKER_STORAGE_RETENTION_HOURS":"721"},
    {"FMONITOR_DOCKER_STORAGE_RETENTION_HOURS":"1.5"},
]
for overrides in invalid_bounds:
    with tempfile.TemporaryDirectory() as temporary:
        result, calls = run_guard(pathlib.Path(temporary), 300 * GIB, **overrides)
        require(result.returncode != 0 and calls == [], f"invalid boundary reached Docker: {overrides}")

# I/J: sequential no-op and concurrent lock/recheck.
with tempfile.TemporaryDirectory() as temporary:
    root = pathlib.Path(temporary); binary, trace, free = fake_tools(root); free.write_text(str(40*GIB))
    env = base_environment(binary, trace, free); env["FAKE_DOCKER_PRUNE_FREE_BYTES"] = str(90*GIB); argv = guard_command(root/"shared.lock")
    first = subprocess.run(argv, cwd=ROOT, env=env, text=True, capture_output=True)
    second = subprocess.run(argv, cwd=ROOT, env=env, text=True, capture_output=True)
    calls = read_trace(trace)
    require(first.returncode == second.returncode == 0, first.stderr + second.stderr)
    require(calls.count(IMAGE_PRUNE) == calls.count(CACHE_PRUNE) == 1, "repeat must be an idempotent cleanup no-op")
    require(calls.count(expected_build()) == 2, "each sequential invocation must build once")
with tempfile.TemporaryDirectory() as temporary:
    root = pathlib.Path(temporary); binary, trace, free = fake_tools(root); free.write_text(str(40*GIB))
    env = base_environment(binary, trace, free); env["FAKE_DOCKER_PRUNE_FREE_BYTES"] = str(90*GIB); argv = guard_command(root/"shared.lock")
    first = subprocess.Popen(argv, cwd=ROOT, env=env, text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    second = subprocess.Popen(argv, cwd=ROOT, env=env, text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    first.communicate(timeout=10); second.communicate(timeout=10); calls = read_trace(trace)
    require(first.returncode == second.returncode == 0, "concurrent guards failed")
    require(calls.count(IMAGE_PRUNE) == calls.count(CACHE_PRUNE) == 1 and calls.count(expected_build()) == 2, "concurrent guards did not serialize/recheck")

# M: complete diagnostic schema, no environment leak, manual host guidance only.
with tempfile.TemporaryDirectory() as temporary:
    root = pathlib.Path(temporary); binary, trace, free = fake_tools(root); free.write_text(str(90*GIB)); env = base_environment(binary, trace, free); env["SECRET_CANARY"]="must-not-leak"
    result = subprocess.run([str(GUARD),"doctor"], cwd=ROOT, env=env, text=True, capture_output=True)
    require(result.returncode == 0 and read_trace(trace) == [["system","df","--format","{{json .}}"]], result.stderr)
    value = tagged("DOCKER_STORAGE_DIAGNOSTIC ", result.stdout)[-1]
    require(value["free_bytes"] == 90*GIB and value["limits"] == {"hard_floor_bytes":50*GIB,"target_free_bytes":80*GIB,"max_cache_bytes":30*GB,"retention_hours":48}, f"bad diagnostic limits: {value}")
    require({item["Type"] for item in value["docker"]} == {"Images","Build Cache","Local Volumes"}, "diagnostic lacks Docker totals")
    require(value["automatic_scope"] == "project_owned_images_and_builder_cache" and value["host_configuration"] == "manual", "diagnostic blurs automatic/manual ownership")
    require("Docker Desktop" in value["guidance"] and "must-not-leak" not in result.stdout+result.stderr, "unsafe/incomplete guidance")

# A/B/C: real public runner in isolated Git fixtures across source/dependency revisions.
def runner_fixture(root: pathlib.Path) -> tuple[pathlib.Path, dict[str,str], pathlib.Path]:
    repo=root/"repo"; (repo/"tools").mkdir(parents=True)
    shutil.copytree(ROOT/"tools/delivery",repo/"tools/delivery")
    for name in ("composer.json","composer.lock","pyproject.toml","uv.lock"):
        shutil.copy2(ROOT/name, repo/name)
    shutil.copy2(ROOT/".gitignore",repo/".gitignore")
    (repo/"marker.txt").write_text("source-a\n")
    subprocess.run(["git","init","-q"],cwd=repo,check=True); subprocess.run(["git","config","user.email","fixture@example.test"],cwd=repo,check=True); subprocess.run(["git","config","user.name","Fixture"],cwd=repo,check=True)
    subprocess.run(["git","add","."],cwd=repo,check=True); subprocess.run(["git","commit","-qm","A"],cwd=repo,check=True)
    binary,trace,free=fake_tools(root); free.write_text(str(90*GIB)); env=base_environment(binary,trace,free); return repo,env,trace
def public_run(repo:pathlib.Path, env:dict[str,str], expected_status:int=0, profile:str="governance") -> tuple[dict[str,object],list[list[str]]]:
    trace=pathlib.Path(env["FAKE_DOCKER_TRACE"]); before=len(read_trace(trace)); result=subprocess.run([str(repo/"tools/delivery/run-in-profile"),profile,"true"],cwd=repo,env=env,text=True,capture_output=True); require(result.returncode==expected_status,result.stderr)
    return tagged("RUN_IN_PROFILE_RESULT ",result.stderr)[-1],read_trace(trace)[before:]
with tempfile.TemporaryDirectory() as temporary:
    repo,env,trace=runner_fixture(pathlib.Path(temporary)); a,calls_a=public_run(repo,env); sha_a=subprocess.check_output(["git","rev-parse","HEAD"],cwd=repo,text=True).strip()
    (repo/"marker.txt").write_text("source-b\n"); subprocess.run(["git","add","marker.txt"],cwd=repo,check=True); subprocess.run(["git","commit","-qm","B"],cwd=repo,check=True)
    b,calls_b=public_run(repo,env); sha_b=subprocess.check_output(["git","rev-parse","HEAD"],cwd=repo,text=True).strip()
    builds=[[token for token in calls if token[:2]==["buildx","build"]] for calls in (calls_a,calls_b)]
    require(len(builds[0])==1 and builds[1]==[],f"source-only revision rebuilt stable dependency image: {builds}")
    require(a["image_digest"]==b["image_digest"],f"source-only revision changed immutable dependency image: {a} {b}")
    require(a["source_digest"]!=b["source_digest"],f"source-only revision did not change executed source provenance: {a} {b}")
    for result,sha,calls in ((a,sha_a,calls_a),(b,sha_b,calls_b)):
        require(result["git_sha"]==sha and result["profile"]=="governance" and result["exit_code"]==0 and isinstance(result["duration_seconds"],(int,float)), f"dishonest provenance: {result}")
        require(str(result["image_digest"]).startswith("sha256:"), "runner omitted immutable image id")
        build=[call for call in calls if call[:2]==["buildx","build"]]; run=[call for call in calls if call[:1]==["run"]]
        require(len(run)==1 and len(build)==(1 if result is a else 0), "runner must build once then reuse, while running every source")
        if build:
            require(build[0].count("--target")==1 and build[0][build[0].index("--target")+1]==result["profile"],f"runner did not build exactly the requested profile target: {build[0]} {result}")
            joined="\n".join(build[0]); require("org.fmonitor.owner=focused-checks" in joined and "org.fmonitor.profile=governance" in joined and "org.fmonitor.dependency-digest=" in joined, f"missing labels: {build[0]}")
            require(result["git_sha"] not in joined and result["source_digest"] not in joined,f"source-derived values change dependency image: {build[0]}")
        mounts=[run[0][index+1] for index,token in enumerate(run[0][:-1]) if token=="--mount"]
        workspace_mounts=[value for value in mounts if ",dst=/workspace," in value]
        require(len(workspace_mounts)==1 and workspace_mounts[0].endswith(",dst=/workspace,readonly") and "type=bind,src=" in workspace_mounts[0],f"runner must bind exactly one frozen source read-only: {run[0]}")
        vendor_mounts=[value for value in mounts if ",dst=/workspace/vendor," in value]
        require(vendor_mounts==[f"type=image,src={result['image_digest']},dst=/workspace/vendor,readonly,image-subpath=opt/fmonitor/composer/vendor"],f"runner must mount image-owned dependencies read-only with a relative image subpath: {run[0]}")
        witnesses=[json.loads(line) for line in pathlib.Path(env["FAKE_RUN_WITNESS"]).read_text().splitlines()]
        expected_marker="source-a" if result is a else "source-b"
        witness=next(item for item in witnesses if item["marker"]==expected_marker)
        expected_mountpoints={name:{"is_dir":True,"entries":[]} for name in (".local","vendor",".test-artifacts")}
        require(witness["workspace_mount_count"]==1 and witness["readonly"] is True and witness["src_absolute"] is True and witness["src_exists"] is True and pathlib.Path(witness["src"]).resolve()!=pathlib.Path(witness["ambient_repo"]).resolve() and witness["actual_digest"]==witness["expected_digest"]==result["source_digest"] and witness["mountpoints"]==expected_mountpoints,f"mounted source is not exact isolated frozen candidate with empty nested mountpoints: {witness} {result}")
    failed_env=env.copy(); failed_env["FAKE_RUN_STATUS"]="37"; failed,calls_failed=public_run(repo,failed_env,37)
    failed_sha=subprocess.check_output(["git","rev-parse","HEAD"],cwd=repo,text=True).strip()
    require(failed["git_sha"]==failed_sha and failed["exit_code"]==37 and failed["profile"]=="governance" and isinstance(failed["duration_seconds"],(int,float)) and str(failed["image_digest"]).startswith("sha256:"),f"failed child provenance is dishonest: {failed}")
    require(sum(call[:2]==["buildx","build"] for call in calls_failed)==0 and sum(call[:1]==["run"] for call in calls_failed)==1,"failed public run must reuse dependencies and still run once")

with tempfile.TemporaryDirectory() as temporary:
    repo,env,trace=runner_fixture(pathlib.Path(temporary))
    for profile in ("governance","browser"):
        result,calls=public_run(repo,env,profile=profile)
        build=next(call for call in calls if call[:2]==["buildx","build"])
        require(build.count("--target")==1 and build[build.index("--target")+1]==profile,f"profile target mismatch: {profile} {build}")
        require(build[build.index("--tag")+1].startswith(f"fmonitor2-focused-{profile}:"),f"profile tag mismatch: {profile} {build}")
        require(f"org.fmonitor.profile={profile}" in build and result["profile"]==profile,f"profile label/result mismatch: {profile} {build} {result}")

dockerfile_text=(ROOT/"tools/delivery/Dockerfile.focused-checks").read_text()
logical=[]
pending=""
for raw in dockerfile_text.splitlines():
    stripped=raw.strip()
    if not stripped or stripped.startswith("#"): continue
    pending+=(" " if pending else "")+stripped.removesuffix("\\").strip()
    if not stripped.endswith("\\"): logical.append(pending);pending=""
allowed_context_sources={"composer.json","composer.lock","pyproject.toml","uv.lock"}
for instruction in logical:
    verb,_,arguments=instruction.partition(" ")
    if verb.upper() not in {"COPY","ADD"} or "--from=" in arguments: continue
    if arguments.lstrip().startswith("["):
        values=json.loads(arguments);sources=set(values[:-1])
    else:
        values=arguments.split();sources=set(values[:-1])
        while sources and next(iter(sources)).startswith("--"): sources.remove(next(iter(sources)))
    require(verb.upper()=="COPY" and sources<=allowed_context_sources,f"dependency image embeds non-dependency source via {instruction}")
require("org.fmonitor.executable-source" not in dockerfile_text,"source-specific label changes immutable dependency image")

dependency_inputs=(
    pathlib.Path("tools/delivery/Dockerfile.focused-checks"),
    pathlib.Path("tools/delivery/Dockerfile.focused-checks.dockerignore"),
    pathlib.Path("tools/delivery/dependencies.env"),
    pathlib.Path("composer.json"), pathlib.Path("composer.lock"),
    pathlib.Path("pyproject.toml"), pathlib.Path("uv.lock"),
)
for dependency in dependency_inputs:
    with tempfile.TemporaryDirectory() as temporary:
        repo,env,trace=runner_fixture(pathlib.Path(temporary))
        baseline,calls_baseline=public_run(repo,env)
        baseline_build=next(call for call in calls_baseline if call[:2]==["buildx","build"])
        baseline_tag=baseline_build[baseline_build.index("--tag")+1]
        target=repo/dependency
        require(target.is_file(),f"identity fixture omitted {dependency}")
        target.write_text(target.read_text()+f"\n# mutation:{dependency}\n")
        changed,calls_changed=public_run(repo,env)
        changed_build=next(call for call in calls_changed if call[:2]==["buildx","build"])
        changed_tag=changed_build[changed_build.index("--tag")+1]
        require(changed_tag!=baseline_tag,f"dependency identity omitted {dependency}")

# K/L: exact disposable lifecycle success, failure, cleanup failure, INT/TERM and persistent rejection.
def disposable_case(root:pathlib.Path, **extra:str) -> tuple[subprocess.CompletedProcess[str],list[list[str]]]:
    binary,trace,free=fake_tools(root); env=base_environment(binary,trace,free); env.update({"COMPOSE_PROJECT_NAME":"hostile-production",**extra}); compose=root/"compose.yaml"; compose.write_text("services: {}\n")
    result=subprocess.run([str(DISPOSABLE),"--project","fm2-disposable-contract","--file",str(compose),"--","up","--detach","--wait"],cwd=ROOT,env=env,text=True,capture_output=True)
    return result,read_trace(trace)
def compose_up(file:pathlib.Path)->list[str]: return ["compose","--project-name","fm2-disposable-contract","--file",str(file),"up","--detach","--wait"]
def compose_down(file:pathlib.Path)->list[str]: return ["compose","--project-name","fm2-disposable-contract","--file",str(file),"down","--volumes","--remove-orphans"]
for up_status,down_status,expected in (("0","0",0),("42","0",42),("42","71",42),("0","71",71)):
    with tempfile.TemporaryDirectory() as temporary:
        root=pathlib.Path(temporary); result,calls=disposable_case(root,FAKE_COMPOSE_UP_STATUS=up_status,FAKE_COMPOSE_DOWN_STATUS=down_status); file=root/"compose.yaml"
        require(result.returncode==expected and calls==[compose_up(file),compose_down(file)], f"disposable status/order wrong: {up_status}/{down_status}: {result.returncode} {calls}")
        value=tagged("DISPOSABLE_COMPOSE_RESULT ",result.stderr)[-1]; require(value["operation_status"]==int(up_status) and value["cleanup_status"]==int(down_status), f"disposable outcome incomplete: {value}")
for sent in (signal.SIGINT,signal.SIGTERM):
    with tempfile.TemporaryDirectory() as temporary:
        root=pathlib.Path(temporary); binary,trace,free=fake_tools(root); env=base_environment(binary,trace,free); env["FAKE_COMPOSE_UP_DELAY"]="30"; file=root/"compose.yaml"; file.write_text("services: {}\n")
        argv=[str(DISPOSABLE),"--project","fm2-disposable-contract","--file",str(file),"--","up"]
        signal_up=["compose","--project-name","fm2-disposable-contract","--file",str(file),"up"]
        process=subprocess.Popen(argv,cwd=ROOT,env=env,text=True,stdout=subprocess.PIPE,stderr=subprocess.PIPE)
        deadline=time.time()+5
        while time.time()<deadline and signal_up not in read_trace(trace): time.sleep(.02)
        process.send_signal(sent); out,err=process.communicate(timeout=5); calls=read_trace(trace)
        require(process.returncode==128+sent and calls[-1]==compose_down(file) and calls.count(compose_down(file))==1, f"signal teardown failed: {sent} {process.returncode} {calls} {err}")
with tempfile.TemporaryDirectory() as temporary:
    root=pathlib.Path(temporary); binary,trace,free=fake_tools(root); env=base_environment(binary,trace,free); file=root/"compose.yaml"; file.write_text("services: {}\n")
    result=subprocess.run([str(DISPOSABLE),"--project","fm2-local-stand","--file",str(file),"--","up"],cwd=ROOT,env=env,text=True,capture_output=True)
    require(result.returncode!=0 and read_trace(trace)==[], "persistent stand identity reached disposable Docker effects")

for path in (GUARD,RUNNER):
    content=path.read_text()
    for forbidden in ("docker system prune","docker volume prune","docker container prune","docker network prune"):
        require(forbidden not in content,f"{path.name} owns forbidden effect: {forbidden}")

print("LOCAL_DOCKER_STORAGE_BUDGET_001_OK")
