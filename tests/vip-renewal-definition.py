"""Regression fixtures for the migration's metadata comparison; no SQL DML."""
import re
from pathlib import Path
script = (Path(__file__).resolve().parents[1] / 'tools/fix_vip_renewal.ps1').read_text()
procedure = re.search(r"\$proposed=@'\n(.*?)\n'@", script, re.S)[1]
header = re.search(r"\$header=\[regex\]::Replace\(\$Value,'([^']+)','CREATE PROCEDURE'\)", script)[1]
# PowerShell .NET regex \A and Python \A have the same meaning here.
def normalize(value):
    return re.sub(r'\s+', '', re.sub(header, 'CREATE PROCEDURE', value)).lower()
expected = normalize(procedure)
for verb in ('CREATE PROCEDURE','ALTER PROCEDURE','CREATE OR ALTER PROCEDURE','create proc','alter proc'):
    stored = re.sub(r'^ALTER PROCEDURE',verb,procedure)
    assert normalize(stored)==expected
    assert normalize(stored.replace('\n','\r\n'))==expected
# A different body must still fail, even if it retains the marker.
for before,after in (('@Expiry<=@Now','@Expiry>@Now'),('DATEADD(second,','DATEADD(day,'),('SET AccountLevel=@AccountLevel','SET AccountLevel=3'),('UPDLOCK,HOLDLOCK','NOLOCK')):
    assert before in procedure
    assert normalize(procedure.replace(before,after))!=expected
assert script.index('$proposed=') < script.index("$original.Contains('MU_PANIC_RENEWAL_V1')")
assert script.index('$transaction.Commit()') > script.index('Normalize-VipSql $definition')
assert 'WZ_SetAccountLevel.received.sql' in script
assert '$transaction.Rollback()' in script
print('Renewal comparison fixtures passed: DDL verb variants, CRLF, modified body rejection, idempotence and rollback guards.')
