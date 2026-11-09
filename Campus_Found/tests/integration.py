"""Isolated API regression suite. Python standard library only."""
import http.cookiejar
import json
import os
from pathlib import Path
import shutil
import sqlite3
import subprocess
import tempfile
import time
import urllib.error
import urllib.request
import uuid

ROOT = Path(__file__).resolve().parents[1]
PHP = os.environ.get('PHP_BINARY', 'php')
EXT = os.environ.get('PHP_EXTENSION_DIR', 'C:/Program Files/php-8.5.11/ext')
checks = []
CLI = os.environ.get('CF_CLI') == '1'
test_root = None


class Client:
    def __init__(self, port):
        self.base = f'http://127.0.0.1:{port}/api.php?action='
        self.http = urllib.request.OpenerDirector()
        for handler in [urllib.request.HTTPHandler(), urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()), urllib.request.HTTPDefaultErrorHandler(), urllib.request.HTTPErrorProcessor()]:
            self.http.add_handler(handler)
        self.token = ''
        self.session = uuid.uuid4().hex

    def call(self, action, data=None, expected=200, csrf=True):
        if CLI:
            request = dict(action=action, data=data, csrf=self.token if csrf else '', session=self.session)
            run = subprocess.run([PHP, '-d', f'extension_dir={EXT}', '-d', 'extension=pdo_sqlite', str(test_root / 'runner.php')], input=json.dumps(request), text=True, capture_output=True)
            result = json.loads(run.stdout)
            session_path = test_root / 'session-id.txt'
            current_session = session_path.read_text().strip()
            if current_session:
                self.session = current_session
            status = int(run.stderr.split('STATUS=')[-1])
            assert status == expected, (action, status, expected, result)
            if action == 'state':
                self.token = result['csrf']
            return result
        headers = {'Content-Type': 'application/json'}
        if csrf:
            headers['X-CSRF-Token'] = self.token
        req = urllib.request.Request(self.base + action, data=None if data is None else json.dumps(data).encode(), headers=headers)
        try:
            response = self.http.open(req)
        except urllib.error.HTTPError as error:
            response = error
        result = json.load(response)
        assert response.status == expected, (action, response.status, expected, result)
        if action == 'state':
            self.token = result['csrf']
        return result


with tempfile.TemporaryDirectory(prefix='campus-found-test-', dir=ROOT.parent) as folder:
    root = Path(folder)
    test_root = root
    shutil.copytree(ROOT / 'public', root / 'public')
    dbpath = root / 'test.sqlite'
    db = sqlite3.connect(dbpath)
    db.executescript((ROOT / 'database/schema.sql').read_text().replace('INTEGER PRIMARY KEY AUTO_INCREMENT', 'INTEGER PRIMARY KEY AUTOINCREMENT'))
    db.close()
    (root / 'config.php').write_text("<?php return ['dsn'=>'sqlite:" + dbpath.as_posix() + "','user'=>'','password'=>''];")
    (root / 'sessions').mkdir()
    (root / 'runner.php').write_text('''<?php
$request=json_decode(stream_get_contents(STDIN),true);
session_save_path(__DIR__.'/sessions');
session_id($request['session']);
// Keep the generated session identifier visible to the test transport after regeneration.
register_shutdown_function(function() { file_put_contents(__DIR__.'/session-id.txt',session_id()); fwrite(STDERR,'STATUS='.(http_response_code()?:200)); });
$_GET['action']=$request['action'];
$_SERVER['REQUEST_METHOD']=$request['data']===null?'GET':'POST';
$_SERVER['HTTP_X_CSRF_TOKEN']=$request['csrf'];
$_SERVER['REMOTE_ADDR']='127.0.0.1';
$GLOBALS['test_input']=json_encode($request['data']??[]);
class TestInputStream {
 public $context; private int $offset=0;
 public function stream_open($path,$mode,$options,&$opened_path){return $path==='php://input';}
 public function stream_read($count){$s=substr($GLOBALS['test_input'],$this->offset,$count);$this->offset+=strlen($s);return $s;}
 public function stream_eof(){return $this->offset>=strlen($GLOBALS['test_input']);}
 public function stream_stat(){return [];}
}
stream_wrapper_unregister('php'); stream_wrapper_register('php',TestInputStream::class);
require __DIR__.'/public/api.php';
''')
    args = [PHP, '-d', f'extension_dir={EXT}', '-d', 'extension=pdo_sqlite', '-S', '127.0.0.1:4181', '-t', str(root / 'public')]
    with (root / 'server.log').open('w') as log:
        server = None if CLI else subprocess.Popen(args, stdout=log, stderr=log)
        try:
            for _ in range(30):
                try:
                    guest = Client(4181)
                    guest.call('state')
                    break
                except OSError:
                    time.sleep(.1)
            clients = []
            for name in ['Finder', 'Owner', 'Other', 'Officer']:
                client = Client(4181)
                client.call('state')
                client.call('register', {'name': name, 'email': name.lower() + '@example.test', 'password': 'Sample-Test-2026!','role':'security'})
                assert client.call('state')['user']['role'] == 'student'
                clients.append(client)
            finder, owner, other, officer = clients
            db = sqlite3.connect(dbpath)
            db.execute("UPDATE users SET role='security' WHERE email='officer@example.test'")
            db.commit()
            officer.call('state')
            checks.append('Registration cannot elevate privileges')
            guest.call('report', {}, 401)
            owner.call('watch', {}, 403, csrf=False)
            owner.call('custody', {'id':1}, 403)
            checks.append('Unauthenticated, CSRF and role restrictions')
            report = dict(type='found', title='Black calculator', category='Electronics', color='black', location_id='1', event_date=time.strftime('%Y-%m-%d'), description='Found near desk', private_detail='Private star engraving')
            item = finder.call('report', report)['id']
            public = guest.call('state')
            assert 'private_detail' not in public['reports'][0]
            assert 'document_hash' not in public['reports'][0]
            assert not public['details']
            checks.append('Public responses exclude private fields')
            owner.call('report', {**report,'type':'lost'})
            owner.call('claim', {'report_id':item,'evidence':'Private star engraving'})
            owner.call('claim', {'report_id':item,'evidence':'Again'}, 400)
            other.call('claim', {'report_id':item,'evidence':'Other evidence'})
            finder.call('claim', {'report_id':item,'evidence':'Self claim'}, 400)
            checks.append('Claim ownership and duplicate prevention')
            owner_claim = owner.call('state')['claims'][0]['id']
            officer.call('review', {'id':owner_claim,'decision':'approved','review_note':'Evidence matches'},400)
            officer.call('custody', {'id':item})
            officer.call('review', {'id':owner_claim,'decision':'approved','review_note':'Evidence matches'})
            assert other.call('state')['claims'][0]['status']=='rejected'
            officer.call('review', {'id':owner_claim,'decision':'approved','review_note':'Replay'},400)
            checks.append('Custody required; one approved claimant; review replay blocked')
            code = owner.call('state')['claims'][0]['collection_code']
            officer.call('collect', {'id':owner_claim,'code':'WRONG'},400)
            officer.call('collect', {'id':owner_claim,'code':code})
            officer.call('collect', {'id':owner_claim,'code':code},400)
            result=owner.call('state')
            assert result['claims'][0]['collection_code'] is None
            assert result['claims'][0]['status']=='collected'
            checks.append('Collection code verification and single-use handover')
            owner.call('watch',{'category':'Keys','color':'green'})
            watch=owner.call('state')['watches'][0]['id']
            other.call('unwatch',{'id':watch})
            assert len(owner.call('state')['watches'])==1
            owner.call('unwatch',{'id':watch})
            assert not owner.call('state')['watches']
            checks.append('Watchlist creation and ownership isolation')
            doc=finder.call('report',{**report,'category':'ID & documents','title':'Sensitive name','description':'Sensitive number','document_number':'ABC123'})['id']
            visible=next(r for r in guest.call('state')['reports'] if r['id']==doc)
            assert visible['title']=='ID or document' and 'Sensitive' not in visible['description']
            assert owner.call('id-search',{'document_number':'abc 123'})['ids']==[doc]
            assert owner.call('id-search',{'document_number':'WRONG'})['ids']==[]
            checks.append('ID redaction and exact private search')
            owner.call('logout',{})
            owner.call('state')
            owner.call('login',{'email':'owner@example.test','password':'Wrong'},401)
            owner.call('login',{'email':'owner@example.test','password':'Sample-Test-2026!'})
            assert owner.call('state')['user']['name']=='Owner'
            checks.append('Logout and password-verified login')
            db.close()
        finally:
            if server:
                server.terminate()
                server.wait(timeout=10)
print(json.dumps({'passed':len(checks),'checks':checks,'database':'isolated SQLite using PDO','transport':'CLI harness' if CLI else 'HTTP','mysql_verified':False},indent=2))
