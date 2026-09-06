// ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-HTTP-001: browser DOM-event contract, isolated transport.
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
import { createHash } from 'node:crypto';
const settle=()=>new Promise(resolve=>setImmediate(resolve));
const response=(status,data)=>({status,ok:status>=200&&status<300,headers:{get:name=>name.toLowerCase()==='content-type'?'application/json; charset=UTF-8':null},json:async()=>data,text:async()=>JSON.stringify(data)+'\n'});
const callbacks = {}, sent = [], navigations = [], logs = [];
const bytes = Buffer.concat([Buffer.from('%PDF-1.4\n'), Buffer.alloc(318, 32)]);
const file = new File([bytes], 'подписанный.pdf', { type: 'application/pdf' });
const initial = '22222222-2222-4222-8222-000000000001';
const fields = Object.fromEntries(Object.entries({csrfToken:'c'.repeat(64), requestId:initial, mode:'initial',documentDate:'2026-09-01',rootOriginalId:'',targetRevisionId:'',expectedCurrentRevisionId:'',correctionReason:''}).map(([k,v])=>[k,{value:v}]));
fields.compositionConfirmed={checked:true,value:'true'}; fields.original={files:[file]};
const fieldset={disabled:true}, button={disabled:false}, status={textContent:''};
Object.defineProperty(status,'innerHTML',{set(){throw new Error('unsafe HTML assignment');}});
const form={action:'http://127.0.0.1/pilot/objects/4512/assignment-orders/81/originals',dataset:{returnUrl:'/pilot/objects/4512/assignment-orders/81/originals/submit'},
 elements:{namedItem:name=>fields[name]},reportValidity:()=>true,checkValidity:()=>true,
 addEventListener:(name,fn)=>{callbacks[name]=fn;},getAttribute(name){return name==='action'?this.action:null;},
 querySelector(selector){return {'[data-original-fields]':fieldset,'[data-original-submit]':button,'[data-original-status]':status}[selector]??null;}};
const location={assign:value=>navigations.push(value),replace:value=>navigations.push(value)};
Object.defineProperty(location,'href',{get:()=>form.action,set:value=>navigations.push(value)});
let counter=1, rejectPending, resultProvider=()=>new Promise((resolve,reject)=>{rejectPending=reject;});
const crypto={randomUUID:()=>`33333333-3333-4333-8333-${String(counter++).padStart(12,'0')}`};
const context={document:{querySelector:selector=>selector==='[data-original-upload-form]'?form:null},crypto,TextEncoder,File,Uint8Array,URL,
 btoa:value=>Buffer.from(value,'binary').toString('base64'),fetch:async(url,options)=>{sent.push({url,options});return resultProvider();},
 console:{log:(...args)=>logs.push(args),error:(...args)=>logs.push(args)},location};
context.window={location,crypto};
const source=new URL('../../app/PilotHttp/original-upload.js',import.meta.url);
assert.equal(fs.existsSync(source),true,'INTENDED_RED upload client missing after valid DOM/File fixture');
vm.runInNewContext(fs.readFileSync(source,'utf8'),context,{filename:'original-upload.js'});
assert.equal(fieldset.disabled,false,'JS enables the honest disabled fallback');
assert.equal(typeof callbacks.submit,'function','submit handler bound');
const event=()=>({preventDefault(){this.prevented=true;}});
callbacks.submit(event());
await settle();
assert.equal(sent.length,1,'first submission');
callbacks.submit(event());await settle();
assert.equal(sent.length,1,'in-flight duplicate is suppressed');
assert.equal(sent[0].options.body,file,'actual selected File is the body');
const header = request => Object.entries(request.options.headers).find(([key])=>key.toLowerCase()==='x-fmonitor-original')?.[1];
const firstHeader=header(sent[0]);
const expected={csrfToken:'c'.repeat(64),requestId:initial,mode:'initial',documentDate:'2026-09-01',compositionConfirmed:true,rootOriginalId:null,targetRevisionId:null,expectedCurrentRevisionId:null,correctionReason:null,originalFilename:'подписанный.pdf'};
assert.equal(Buffer.from(firstHeader,'base64').toString('utf8'),JSON.stringify(expected),'canonical UTF8 metadata exactly follows form');
rejectPending(new Error('synthetic network loss'));await settle();
assert.equal(navigations.length,0,'network loss not success');assert.equal(fieldset.disabled,false,'form remains retryable');
resultProvider=async()=>response(503,{error:'SERVICE_UNAVAILABLE'});
callbacks.submit(event());await settle();assert.equal(header(sent[1]),firstHeader,'network retry preserves identity and metadata');assert.equal(sent[1].options.body,file,'retry keeps File');
fields.documentDate.value='2026-09-02';callbacks.input(event());
callbacks.submit(event());await settle();assert.notEqual(header(sent[2]),firstHeader,'changed intent gets a new key');
const changed=JSON.parse(Buffer.from(header(sent[2]),'base64'));assert.notEqual(changed.requestId,initial);assert.equal(changed.documentDate,'2026-09-02');
const callsBeforeInvalid=navigations.length;resultProvider=async()=>response(200,{error:'not-an-accepted-result'});
callbacks.submit(event());await settle();assert.equal(navigations.length,callsBeforeInvalid,'invalid success envelope is not success');
const accepted={status:'accepted',reasonCode:null,retryable:false,requestId:changed.requestId,rootOriginalId:'root-'+'a'.repeat(32),currentRevisionId:'revision-'+'b'.repeat(32),revisionNumber:1,documentDate:'2026-09-02',sha256:createHash('sha256').update(bytes).digest('hex'),byteSize:327,uploadedAt:'2026-09-07T00:00:00Z'};
resultProvider=async()=>response(201,accepted);callbacks.submit(event());await settle();
assert.equal(navigations.at(-1),form.dataset.returnUrl,'only accepted result redirects to form');
assert.equal(JSON.stringify(logs).includes(expected.csrfToken),false,'no secret logging');
console.log('ORIGINAL_UPLOAD_CLIENT_OK canonical metadata/File/in-flight/retry/new intent/success');
