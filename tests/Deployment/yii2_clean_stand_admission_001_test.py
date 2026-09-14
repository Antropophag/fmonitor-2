#!/usr/bin/env python3
import pathlib, sys, os
sys.path.insert(0,str(pathlib.Path(__file__).resolve().parents[2]))
from tests.Support.clean_stand_contract import CleanStandFixture,result,sha,IMAGE

f=CleanStandFixture()
try:
 p=f.run("preflight"); assert (p.returncode,result(p)["reason"])==(0,"CLEAN_STAND_PREFLIGHT_OK"); assert f.trace()==[]
 cases=[
  ("MANIFEST_MALFORMED",lambda x:x.manifest_path.write_text("{"),False),
  ("MANIFEST_VERSION_INVALID",lambda x:x.manifest.__setitem__("version","v0"),True),
  ("AUTHORIZATION_MALFORMED",lambda x:x.authorization_path.write_text("{"),False),
  ("AUTHORIZATION_VERSION_INVALID",lambda x:x.authorization.__setitem__("version","v0"),True),
  ("AUTHORIZATION_ID_INVALID",lambda x:x.authorization.__setitem__("authorizationId","not-a-uuid"),True),
  ("OPERATION_ID_INVALID",lambda x:x.authorization.__setitem__("operationId","not-a-uuid"),True),
  ("AUTHORIZATION_INTENT_INVALID",lambda x:x.authorization.__setitem__("intent","RESTORE"),True),
  ("AUTHORIZATION_EXPIRED",lambda x:x.authorization.__setitem__("expiresAtUtc","2000-01-01T00:00:00Z"),True),
  ("AUTHORIZATION_EFFECTS_INVALID",lambda x:x.authorization["effects"].pop(),True),
  ("AUTHORIZATION_DIGEST_MISMATCH",lambda x:None,True),
  ("SOURCE_MISMATCH",lambda x:x.authorization.__setitem__("source","e"*40),True),
  ("IMAGE_INVALID",lambda x:x.authorization.__setitem__("image","fmonitor2-runtime:latest"),True),
  ("IMAGE_MISMATCH",lambda x:x.authorization.__setitem__("image","fmonitor2-runtime@sha256:"+"e"*64),True),
  ("COMPOSE_PATH_MISMATCH",lambda x:x.authorization["compose"].__setitem__("path",str(x.root/"other.yaml")),True),
  ("COMPOSE_DIGEST_MISMATCH",lambda x:x.authorization["compose"].__setitem__("sha256","e"*64),True),
  ("COMPOSE_SYMLINK_INVALID",lambda x:(os.symlink(x.compose,x.root/"compose-link.yaml"),x.manifest["compose"].__setitem__("path",str(x.root/"compose-link.yaml")),x.authorization["compose"].__setitem__("path",str(x.root/"compose-link.yaml"))),True),
  ("TARGET_NOT_DISPOSABLE",lambda x:x.manifest.__setitem__("cleanDisposable",False),True),
  ("EXPECTED_ABSENCE_REQUIRED",lambda x:x.manifest.__setitem__("expectedAbsent",False),True),
  ("TARGET_NAMES_MISMATCH",lambda x:x.authorization["names"].__setitem__("database","fm2_clean_conflict"),True),
  ("TARGET_ALIAS_INVALID",lambda x:(x.manifest["names"].__setitem__("database",x.manifest["names"]["project"]),x.authorization["names"].__setitem__("database",x.authorization["names"]["project"])),True),
  ("CREDENTIAL_REFERENCE_INVALID",lambda x:x.authorization["credentialFiles"].__setitem__("databasePassword","relative"),True),
  ("CREDENTIAL_KEYS_INVALID",lambda x:x.authorization["credentialFiles"].__setitem__("extra",str(x.credential)),True),
  ("CREDENTIAL_MISSING",lambda x:x.credential.unlink(),True),
  ("CREDENTIAL_MODE_INVALID",lambda x:x.credential.chmod(0o644),True),
  ("CREDENTIAL_SYMLINK_INVALID",lambda x:(os.symlink(x.credential,x.root/"credential-link"),x.authorization["credentialFiles"].__setitem__("databasePassword",str(x.root/"credential-link"))),True),
  ("TARGET_ALREADY_EXISTS",lambda x:x.driver["precreate"].__setitem__("projectExists",True),True),
  ("PRODUCTION_OVERLAP",lambda x:x.driver["precreate"]["conflicts"].append({"scope":"production","class":"network","name":"production-network"}),True),
  ("NEIGHBOR_OVERLAP",lambda x:x.driver["precreate"]["conflicts"].append({"scope":"neighbor","class":"network","name":"neighbor-network"}),True),
  ("UNEXPECTED_CONTAINER",lambda x:x.driver["precreate"]["conflicts"].append({"scope":"target","class":"container","name":x.manifest["names"]["containers"][0]}),True),
  ("UNEXPECTED_NETWORK",lambda x:x.driver["precreate"]["conflicts"].append({"scope":"target","class":"network","name":x.manifest["names"]["network"]}),True),
  ("UNEXPECTED_VOLUME",lambda x:x.driver["precreate"]["conflicts"].append({"scope":"target","class":"volume","name":x.manifest["names"]["volumes"][0]}),True),
 ]
 for reason,mutate,sync in cases:
  x=CleanStandFixture()
  try:
   before=x.tree(x.target); mutate(x)
   supplied="0"*64 if reason=="AUTHORIZATION_DIGEST_MISMATCH" else None
   if sync:x.sync()
   p=x.run("preflight",authorization_digest=supplied); assert p.returncode!=0 and result(p)["reason"]==reason,(reason,p.stdout,p.stderr)
   assert x.trace()==[] and x.tree(x.target)==before and "synthetic-secret" not in p.stdout+p.stderr
  finally:x.close()
finally:f.close()

x=CleanStandFixture()
try:
 assert x.run("preflight").returncode==0; before=x.tree(x.target); x.authorization["authorizationId"]="00000000-0000-4000-8000-000000000001"; x.sync()
 conflict=x.run("preflight"); assert conflict.returncode!=0 and result(conflict)["reason"]=="AUTHORIZATION_REPLAY_CONFLICT"
 assert x.trace()==[] and x.tree(x.target)==before
finally:x.close()
print("PASS: YII2-CLEAN-STAND-CUTOVER-001 exact admission")
