import http.server, ssl, json, sys, pathlib, threading, time
root=pathlib.Path(sys.argv[1]); write_lock=threading.Lock()
def log(value):
    with write_lock:
        with (root/'requests.jsonl').open('a') as f: f.write(json.dumps(value)+'\n')
def person(i):
    return dict(ID=str(i) if i%2 else i, ACTIVE=bool(i%2), LAST_NAME='Работник', NAME='теста', SECOND_NAME='', WORK_POSITION='Монтажник', EMAIL=f'tab{i}@example.invalid', UF_XING=str(i), UF_DEPARTMENT=[71], UF_EMPLOYMENT_DATE=None)
class Server(http.server.ThreadingHTTPServer):
    daemon_threads=True
    def get_request(self):
        raw,address=self.socket.accept()
        with (root/'connections.jsonl').open('a') as f:f.write(json.dumps({'accepted':True})+'\n')
        if json.loads((root/'scenario.json').read_text())['mode']=='connect_timeout':
            with (root/'connect-timeouts.jsonl').open('a') as f:f.write(json.dumps({'accepted':True})+'\n')
            def hold():
                try:time.sleep(4)
                finally:raw.close()
            threading.Thread(target=hold,daemon=True).start()
            raise OSError('owned handshake timeout fixture')
        try:return self.tls.wrap_socket(raw,server_side=True),address
        except Exception:raw.close();raise
    def handle_error(self,*args): pass
class Handler(http.server.BaseHTTPRequestHandler):
    protocol_version='HTTP/1.1'
    def log_message(self,*args): pass
    def reply(self,code,body,extra=None):
        body=body.encode() if isinstance(body,str) else body
        self.send_response(code); self.send_header('Content-Type','application/json');self.send_header('Content-Length',str(len(body)));self.send_header('Connection','close')
        for k,v in (extra or {}).items():self.send_header(k,v)
        self.end_headers()
        try:self.wfile.write(body)
        except (BrokenPipeError,ConnectionResetError,ssl.SSLError):pass
        self.close_connection=True
    def do_GET(self):
        if self.path=='/fixture-health':self.reply(200,'{"ready":true}');return
        log({'method':'GET','path':self.path,'trap':True});self.reply(200,'{"trap":true}')
    def do_POST(self):
        n=int(self.headers.get('Content-Length','0'))
        if n>32768:self.reply(400,'{}');return
        raw=self.rfile.read(n)
        try:request=json.loads(raw)
        except Exception:self.reply(400,'{}');return
        config=json.loads((root/'scenario.json').read_text());mode=config['mode'];start=request.get('start',0)
        log({'method':'POST','path':self.path,'request':request,'headers':dict(self.headers),'version':self.request_version,'at':time.monotonic(),'mode':mode})
        count=sum(1 for line in (root/'requests.jsonl').read_text().splitlines() if json.loads(line).get('mode')==mode)
        if mode in ['paused_success','paused_error']:
            (root/'pause-ready').write_text('1');until=time.monotonic()+3
            while not (root/'pause-release').exists() and time.monotonic()<until:time.sleep(.001)
            if not (root/'pause-release').exists():self.reply(500,'{}');return
            self.reply(200 if mode=='paused_success' else 401,'{"result":[],"total":0}');return
        if mode=='redirect':self.reply(302,'{}',{'Location':f'https://127.0.0.1:{self.server.server_port}/trap'});return
        if mode in ['401','403','404','500','429','502','503','504']:
            self.reply(int(mode),'FAKE_TOKEN_123456789 Работник tab1@example.invalid');return
        if mode=='retry_then_ok' and count<3:self.reply(503,'{}');return
        if mode=='second_failure' and start:self.reply(503,'{}');return
        if mode=='request_timeout':time.sleep(4)
        if mode=='api_error':self.reply(200,json.dumps({'error':'FAIL','error_description':'FAKE_TOKEN_123456789 Работник tab1@example.invalid'}));return
        if mode=='invalid_json':self.reply(200,'{"FAKE_TOKEN_123456789":');return
        if mode=='duplicate_json':self.reply(200,'{"result":[],"total":0,"\\u0074otal":0}');return
        if mode=='oversize':self.reply(200,b'x'*1048577);return
        if mode=='exact_body':
            prefix='{"result":[],"total":0,"time":{"padding":"';suffix='"}}'
            self.reply(200,prefix+'x'*(1048576-len(prefix)-len(suffix))+suffix);return
        total=0 if mode=='zero' else (50 if mode=='fifty' else (1600 if mode=='exact_cumulative' else (5000 if mode=='cumulative' else 51)))
        rows=[person(i) for i in range(start+1,min(start+50,total)+1)]
        out={'result':rows,'total':total}
        if start+len(rows)<total:out['next']=start+50
        if mode=='reordered':out={k:out[k] for k in reversed(out)};out['result']=[dict(reversed(list(x.items()))) for x in rows]
        if mode=='unknown_envelope':out['unexpected']=True
        if mode=='missing_field':del rows[0]['UF_EMPLOYMENT_DATE']
        if mode=='unknown_field':rows[0]['unexpected']=1
        if mode=='wrong_active':rows[0]['ACTIVE']='Y'
        if mode=='wrong_id':rows[0]['ID']='01'
        if mode=='wrong_department':rows[0]['UF_DEPARTMENT']={'0':71}
        if mode=='long_string':rows[0]['LAST_NAME']='x'*4097
        if mode=='result_object':out['result']={}
        if mode=='negative_total':out['total']=-1
        if mode=='time_array':out['time']=[]
        if mode=='raw_nulls' and not start:rows[0].update(LAST_NAME='  Работник  ',NAME=None,SECOND_NAME=None,WORK_POSITION='  Монтажник  ',EMAIL=None,UF_XING=' 1 ',UF_DEPARTMENT=['71'],UF_EMPLOYMENT_DATE='unparsed source date')
        if mode=='wrong_total':out['total']='51'
        if mode=='scope':rows[0]['UF_DEPARTMENT']=[72]
        if mode=='next_jump':out['next']=start+100
        if mode=='next_string':out['next']='50'
        if mode=='missing_next':out.pop('next',None)
        if mode=='short':out['result']=rows[:-1]
        if mode=='overfull':out['result']=rows+[person(52)]
        if mode=='extra_next':out['total']=len(rows);out['next']=50
        if mode=='drift' and start:out['total']=52
        if mode=='overlap' and start:rows[0]['ID']='50'
        if mode=='decreasing':rows[0],rows[1]=rows[1],rows[0]
        if mode=='person_limit':out['total']=20001
        if mode=='cumulative':
            for row in rows:row['LAST_NAME']='x'*4096;row['WORK_POSITION']='y'*4096
        if mode=='deep':out['time']={};cur=out['time'];
        if mode=='deep':
            for _ in range(40):cur['x']={};cur=cur['x']
        body=json.dumps(out,ensure_ascii=False,separators=(',',':'))
        if mode=='exact_cumulative':body+=' '*(524288-len(body.encode()))
        self.reply(200,body)
server=Server(('127.0.0.1',0),Handler)
context=ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER);context.load_cert_chain(root/'server.crt',root/'server.key');server.tls=context
(root/'ready.json').write_text(json.dumps({'port':server.server_port}))
try:server.serve_forever(poll_interval=.1)
finally:server.server_close()
