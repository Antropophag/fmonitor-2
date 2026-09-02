"use strict";
const fs=require("fs"),vm=require("vm");
const source=fs.readFileSync(process.argv[2],"utf8");
const context={document:{querySelector:()=>null},globalThis:null};context.globalThis=context;
vm.runInNewContext(source,context,{filename:"checklist.js"});
const cap=context.FMonitorChecklistProgressCap;
if(typeof cap!=="function")throw new Error("intended RED: external checklist asset does not expose executable progress-cap helper");
const cases=[[97,85,85],[85,85,85],[64,85,64],[0,100,100],[85,100,100],[100,100,100]];
for(const [value,limit,expected]of cases){const actual=cap(value,limit);if(actual!==expected)throw new Error(`cap(${value},${limit}) expected ${expected}, actual ${actual}`);}
if(!source.includes("FMonitorChecklistProgressCap("))throw new Error("external asset does not wire the tested helper into checklist rendering");
process.stdout.write("completion external cap behavior: PASS\n");
