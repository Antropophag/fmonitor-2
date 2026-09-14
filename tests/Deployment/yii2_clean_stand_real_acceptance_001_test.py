#!/usr/bin/env python3
import hashlib,json,os,pathlib,subprocess,sys,tempfile
ROOT=pathlib.Path(__file__).resolve().parents[2]; seam=ROOT/"tests/Support/yii2_clean_stand_acceptance.php"
assert seam.is_file(),"missing-clean-stand-acceptance-public-seam"
package=os.getenv("FMONITOR_CLEAN_STAND_AUTHORIZATION_PACKAGE")
if package:
    evidence=os.environ["FMONITOR_CLEAN_STAND_EVIDENCE_ROOT"]
    run=subprocess.run(["php",str(seam),"run","--authorization-package="+package,"--evidence-root="+evidence],cwd=ROOT,text=True,stdout=subprocess.PIPE,stderr=subprocess.PIPE)
    assert run.returncode==0 and '"reason":"CLEAN_STAND_ACCEPTED"' in run.stdout,(run.stdout,run.stderr)
else:
    run=subprocess.run(["php",str(seam),"run"],cwd=ROOT,text=True,stdout=subprocess.PIPE,stderr=subprocess.PIPE)
    assert run.returncode!=0 and '"reason":"AUTHORIZATION_REQUIRED"' in run.stdout and run.stderr=="",(run.stdout,run.stderr)
    with tempfile.TemporaryDirectory(prefix="fm2-clean-real-gate-") as tmp:
        package=pathlib.Path(tmp)/"authorization.json"; package.write_text("{}\n")
        driver=pathlib.Path(tmp)/"driver.json"; driver.write_text("{}\n")
        env=os.environ.copy(); env["FMONITOR_CLEAN_STAND_TEST_MODE"]="1"; env["FMONITOR_CLEAN_STAND_RECORDING_DRIVER"]=str(driver)
        mixed=subprocess.run(["php",str(seam),"run","--authorization-package="+str(package),"--evidence-root="+tmp],cwd=ROOT,env=env,text=True,stdout=subprocess.PIPE,stderr=subprocess.PIPE)
        assert mixed.returncode!=0 and '"reason":"RECORDING_DRIVER_FORBIDDEN"' in mixed.stdout and mixed.stderr=="",(mixed.stdout,mixed.stderr)
    with tempfile.TemporaryDirectory(prefix="fm2-clean-http-context-") as tmp:
        context=pathlib.Path(tmp)/"context.json"; valid={"httpRequests":[{"method":"GET","path":"/pilot/login","headers":[],"expectedStatus":200,"captures":{"csrf":"~name=\"_csrf\" value=\"([^\"]+)\"~","json_value":"~data-json-value=\"([^\"]+)\"~"}},{"method":"POST","path":"/pilot/objects/4512/checklist/operations","headers":["Content-Type: application/json","X-FM2-CSRF: @capture:csrf"],"rawBody":"{\"operation_id\":\"11111111-1111-4111-8111-111111111111\",\"value\":\"@capture:json_value\"}","expectedStatus":200}]}; context.write_text(json.dumps(valid,sort_keys=True,separators=(",",":"))+"\n")
        digest=hashlib.sha256(context.read_bytes()).hexdigest(); checked=subprocess.run(["php",str(seam),"validate-http-context","--context="+str(context),"--context-digest="+digest],cwd=ROOT,text=True,stdout=subprocess.PIPE,stderr=subprocess.PIPE)
        body=json.loads(checked.stdout); assert checked.returncode==0 and body=={"reason":"HTTP_CONTEXT_VALID","requests":[{"method":"GET","path":"/pilot/login","body":"none","defines":["csrf","json_value"],"references":[]},{"method":"POST","path":"/pilot/objects/4512/checklist/operations","body":"raw-json","defines":[],"references":["csrf","json_value"]}]} and checked.stderr=="",(checked.stdout,checked.stderr)
        assert "11111111" not in checked.stdout and "_csrf" not in checked.stdout,"raw body/capture values are not disclosed"
        invalid=[]; both=json.loads(json.dumps(valid)); both["httpRequests"][1]["form"]={"x":"y"}; invalid.append(both)
        forward=json.loads(json.dumps(valid)); forward["httpRequests"].reverse(); invalid.append(forward)
        malformed=json.loads(json.dumps(valid)); malformed["httpRequests"][1]["rawBody"]="{not-json"; invalid.append(malformed)
        nonstring=json.loads(json.dumps(valid)); nonstring["httpRequests"][1]["rawBody"]={"x":1}; invalid.append(nonstring)
        missing=json.loads(json.dumps(valid)); missing["httpRequests"][1]["headers"][1]="X-FM2-CSRF: @capture:missing"; invalid.append(missing)
        for conflict in invalid:
            context.write_text(json.dumps(conflict,sort_keys=True,separators=(",",":"))+"\n"); digest=hashlib.sha256(context.read_bytes()).hexdigest(); rejected=subprocess.run(["php",str(seam),"validate-http-context","--context="+str(context),"--context-digest="+digest],cwd=ROOT,text=True,stdout=subprocess.PIPE,stderr=subprocess.PIPE); assert rejected.returncode!=0 and '"reason":"HTTP_CONTEXT_INVALID"' in rejected.stdout and rejected.stderr==""
        context.write_text(json.dumps(valid,sort_keys=True,separators=(",",":"))+"\n"); digest=hashlib.sha256(context.read_bytes()).hexdigest(); secret='quote" slash\\ Юникод'; captures=pathlib.Path(tmp)/"captures.json"; captures.write_text(json.dumps({"csrf":"csrf-private","json_value":secret})+"\n"); captures.chmod(0o600); trace=pathlib.Path(tmp)/"curl-trace.json"; fake=pathlib.Path(tmp)/"fake-curl.php"; fake.write_text('<?php $a=array_slice($argv,1);$i=array_search("--data-binary",$a,true);$p=$i===false?null:substr($a[$i+1],1);file_put_contents(getenv("TRACE"),json_encode(["argv"=>$a,"path"=>$p,"mode"=>decoct(fileperms($p)&0777),"body"=>file_get_contents($p)]));echo "200";'); fake.chmod(0o700)
        env=os.environ.copy(); env.update({"FMONITOR_CLEAN_STAND_TEST_MODE":"1","FMONITOR_CLEAN_STAND_CURL_BIN":str(fake),"TRACE":str(trace)}); executed=subprocess.run(["php",str(seam),"execute-http-context","--context="+str(context),"--context-digest="+digest,"--captures="+str(captures),"--request-index=1"],cwd=ROOT,env=env,text=True,stdout=subprocess.PIPE,stderr=subprocess.PIPE); observed=json.loads(trace.read_text()); decoded=json.loads(observed["body"])
        assert executed.returncode==0 and json.loads(executed.stdout)=={"reason":"HTTP_REQUEST_EXECUTED","status":200,"bodyTransport":"private-file"} and executed.stderr==""
        assert decoded["value"]==secret and observed["mode"]=="600" and not pathlib.Path(observed["path"]).exists()
        rendered=" ".join(observed["argv"])+executed.stdout+executed.stderr; assert secret not in rendered and "csrf-private" not in rendered and "@capture:" not in observed["body"]
print("PASS: YII2-CLEAN-STAND-CUTOVER-001 real acceptance is authorization-gated")
