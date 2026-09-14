#!/usr/bin/env python3
import os,pathlib,subprocess,sys,tempfile
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
print("PASS: YII2-CLEAN-STAND-CUTOVER-001 real acceptance is authorization-gated")
