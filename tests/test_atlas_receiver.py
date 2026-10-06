import hashlib,hmac,json,os,subprocess,tempfile,time,unittest,urllib.request,urllib.error,socket
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
PHP=os.environ.get('PHP_BIN','php')
class Receiver(unittest.TestCase):
    def test_runtime_upgrade_requires_matching_source_hash(self):
        snapshot=json.loads((ROOT/'overlay/templates/mupanic/inc/public-balance.json').read_text())
        medusa=next(b for b in snapshot['eventBags'] if b['id']==106)
        medusa['format']='unsupported';medusa['items']=[];medusa.pop('selection')
        with tempfile.TemporaryDirectory() as tmp:
            env={**os.environ,'PANIC_ATLAS_RUNTIME_DIR':tmp}
            path=Path(tmp)/'public-balance.json'
            code='require '+json.dumps(str(ROOT/'overlay/templates/mupanic/inc/atlas-runtime.php'))+'; echo json_encode(panicAtlasBalance());'
            path.write_text(json.dumps(snapshot));read=json.loads(subprocess.check_output([PHP,'-r',code],env=env))
            self.assertEqual(next(b for b in read['eventBags'] if b['id']==106)['format'],'advanced')
            snapshot['sourceHash']='f'*64;path.write_text(json.dumps(snapshot));read=json.loads(subprocess.check_output([PHP,'-r',code],env=env))
            self.assertEqual(next(b for b in read['eventBags'] if b['id']==106)['format'],'unsupported')
    def test_receiver_accepts_advanced_and_rejects_malformed_updates(self):
        snapshot=json.loads((ROOT/'overlay/templates/mupanic/inc/public-balance.json').read_text())
        snapshot['generatedAt']=time.strftime('%Y-%m-%dT%H:%M:%SZ',time.gmtime())
        with tempfile.TemporaryDirectory() as tmp:
            token='fixture-only-token-not-a-real-credential-12345'
            (Path(tmp)/'token').write_text(token)
            with socket.socket() as sock:sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
            proc=subprocess.Popen([PHP,'-S',f'127.0.0.1:{port}','-t',str(ROOT/'overlay/templates/mupanic')],env={**os.environ,'PANIC_ATLAS_RUNTIME_DIR':tmp},stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
            try:
                url=f'http://127.0.0.1:{port}/api/atlas-sync.php'
                for _ in range(50):
                    try:urllib.request.urlopen(url);break
                    except urllib.error.HTTPError:break
                    except urllib.error.URLError:time.sleep(.1)
                def send(data,bad_signature=False):
                    body=json.dumps(data).encode();stamp=str(int(time.time()));sig=hmac.new(token.encode(),stamp.encode()+b'\n'+body,hashlib.sha256).hexdigest()
                    req=urllib.request.Request(url,data=body,headers={'X-Panic-Timestamp':stamp,'X-Panic-Signature':'0'*64 if bad_signature else sig})
                    try:
                        with urllib.request.urlopen(req) as r:return r.status
                    except urllib.error.HTTPError as e:return e.code
                self.assertEqual(send(snapshot),200)
                original=(Path(tmp)/'public-balance.json').read_bytes()
                self.assertEqual(send(snapshot,True),401)
                advanced=next(b for b in snapshot['eventBags'] if b['id']==106)
                advanced['selection']['attempts'][0]['dropRate']=10001
                self.assertEqual(send(snapshot),422)
                self.assertEqual((Path(tmp)/'public-balance.json').read_bytes(),original)
            finally:proc.terminate();proc.wait(timeout=5)
if __name__=='__main__':unittest.main()
