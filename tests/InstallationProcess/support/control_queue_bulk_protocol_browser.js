"use strict";
const fs=require("node:fs"),vm=require("node:vm");
const source=fs.readFileSync(process.argv[2],"utf8"),sent=[];
class E{constructor(){this.dataset={};this.value="";this.checked=false;this.hidden=false;this.textContent=""}querySelector(){return new E}querySelectorAll(){return[]}addEventListener(){}setAttribute(){}closest(){return null}click(){}focus(){}}
const root=new E;root.dataset.userId="18";root.querySelector=s=>({"[data-control-search]":new E,"[data-show-completed]":new E,"[data-result-count]":new E,"[data-list-summary]":new E,"[data-control-empty]":new E,"[data-clear-filters]":null}[s]??new E);root.querySelectorAll=s=>s==="input[name=ownership]"?[Object.assign(new E,{checked:true,value:"mine"})]:[];
const operations=new Map,device="22222222-2222-4222-8222-222222222222",batch="33333333-3333-4333-8333-333333333333";
for(let i=0;i<3;i++){const client=`11111111-1111-4111-8111-${String(i+1).padStart(12,"0")}`,previous=i?`11111111-1111-4111-8111-${String(i).padStart(12,"0")}`:null;operations.set(`op${i}`,{id:`op${i}`,clientOperationId:client,deviceInstallationId:device,scope:"18:device:4512",type:"item_completed",status:"queued",deviceTime:"2026-09-07T12:00:00+03:00",baseRevision:i?null:0,localPredecessorId:previous,localBatchId:batch,localBatchSequence:i,sectionId:1,itemId:28+i,installerTabIds:["1042"]})}
const request=value=>{const r={};queueMicrotask(()=>{r.result=value;r.onsuccess?.()});return r};
const db={objectStoreNames:{contains:n=>n==="operations"},close(){},transaction(name){return{objectStore(){return{getAll:()=>request([...operations.values()]),get:key=>request(operations.get(key)),put:value=>{operations.set(value.id,{...value});return request(value.id)}}}}}};
const indexedDB={open(){const r={result:db};queueMicrotask(()=>r.onsuccess?.());return r}};
let revision=0;const fetch=async(url,options={})=>{if(url.includes("sync-context"))return{ok:true,json:async()=>({csrf:"c".repeat(64),revision:99})};const payload=JSON.parse(options.body);sent.push(payload);revision++;return{json:async()=>({status:"accepted",revision})}};
const events={};const context={document:{querySelector:s=>s==="[data-control-queue]"?root:null},window:{scrollY:0,isSecureContext:false,addEventListener:(n,f)=>events[n]=f},navigator:{onLine:true},indexedDB,fetch,sessionStorage:{getItem:()=>null,setItem(){}},requestAnimationFrame:f=>f(),scrollTo(){},setTimeout,clearTimeout,TextEncoder,JSON,Map,Promise,console};
vm.runInNewContext(source,context,{filename:"control-queue.js"});
setTimeout(()=>process.stdout.write(JSON.stringify({sent,stored:[...operations.values()]})),80);
