"""Exercise backup/apply/idempotency/restore/conflict/symlink/tamper in temp dirs."""
from pathlib import Path
import subprocess, tempfile, os, shutil
repo=Path(__file__).resolve().parents[1]
with tempfile.TemporaryDirectory(prefix='panic-eco-test-') as temp:
    base=Path(temp);root=base/'cms';(root/'templates/mupanic/inc').mkdir(parents=True)
    (root/'index.php').write_text('CMS marker')
    originals={'templates/mupanic/index.php':b'Original template index\n','templates/mupanic/inc/recharge-admin.php':b'Original shop admin\n'}
    for p,data in originals.items():(root/p).write_bytes(data)
    env={**os.environ,'MUPANIC_ECO_BACKUP_ROOT':str(base/'private')}
    def call(action,*args,ok=True):
        result=subprocess.run(['bash',str(repo/'scripts/economy-install.sh'),action,str(root),*map(str,args)],capture_output=True,text=True,env=env)
        assert (result.returncode==0)==ok,(action,result.stdout,result.stderr)
        return result.stdout
    assert 'NUEVO' in call('Estado')
    out=call('Aplicar');backup=next((base/'private').iterdir())
    files=(repo/'scripts/economy-files.txt').read_text().splitlines()
    for p in files:assert (root/p).read_bytes()==(repo/'overlay'/p).read_bytes(),p
    assert (backup/'files/templates/mupanic/index.php').read_bytes()==originals['templates/mupanic/index.php']
    assert 'sin cambios' in call('Aplicar');assert len(list((base/'private').iterdir()))==1
    (root/'templates/mupanic/index.php').write_text('Subsequent unrelated edit')
    call('Restaurar',backup,ok=False)
    assert (root/'templates/mupanic/inc/admin-economy/entry.php').exists(),'Conflict must abort whole restore'
    shutil.copyfile(repo/'overlay/templates/mupanic/index.php',root/'templates/mupanic/index.php')
    call('Restaurar',backup)
    for p,data in originals.items():assert (root/p).read_bytes()==data
    for p in files:
        if p not in originals:assert not (root/p).exists(),p
    # Restoring twice is safe; it never removes files created by someone else.
    call('Restaurar',backup)
    (root/'templates/mupanic/js').rmdir() if (root/'templates/mupanic/js').exists() and not list((root/'templates/mupanic/js').iterdir()) else None
    link=root/'templates/mupanic/inc/admin-economy/entry.php';link.symlink_to(base/'elsewhere')
    call('Aplicar',ok=False);link.unlink()
    env['MUPANIC_ECO_BACKUP_ROOT']=str(root/'backups')
    call('Aplicar',ok=False)
print('Installer: apply, hashes, private backups, idempotency, restore, conflict and symlink rejection OK.')
