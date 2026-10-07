<?php
declare(strict_types=1);
define('AUDITORIA_UNIT_TEST', true);
require __DIR__ . '/preparar-auditoria.php';
function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
$root = sys_get_temp_dir() . '/mupanic-audit-test-' . bin2hex(random_bytes(6));
mkdir($root . '/source/cache', 0700, true);
mkdir($root . '/source/config', 0700, true);
file_put_contents($root . '/source/config/webengine.json', json_encode([
    'SQL_DB_NAME' => 'MuOnline43', 'SQL_DB_USER' => 'mupanic_web',
    'SQL_DB_PASS' => 'fake-production-password', 'SQL_USE_2_DB' => true,
    'SQL_DB_2_NAME' => 'OtherLiveDB', 'smtp_password' => 'fake-mail-password',
    'smtp_enabled' => true,
]));
file_put_contents($root . '/source/read.php', '<?php $db="[MuOnline43]"; $path="/home/mupanic/payments-private";');
file_put_contents($root . '/source/cache/private.json', '{"secret":"not-to-copy"}');
file_put_contents($root . '/source/database.bak', 'not-to-copy');
$before = hash_file('sha256', $root . '/source/config/webengine.json');
check(auditCopy($root . '/source', $root . '/destination') === 2, 'Wrong file count');
$config = json_decode(file_get_contents($root . '/destination/config/webengine.json'), true);
check($config['SQL_DB_NAME'] === 'MuOnline43_Auditoria', 'Wrong database');
check($config['SQL_DB_USER'] === 'mupanic_auditoria' && $config['SQL_DB_PASS'] === '', 'Live credentials copied');
check(!$config['SQL_USE_2_DB'] && $config['SQL_DB_2_NAME'] === null, 'Second database enabled');
check($config['smtp_password'] === '' && !$config['smtp_enabled'], 'SMTP credentials copied');
check(!file_exists($root . '/destination/cache') && !file_exists($root . '/destination/database.bak'), 'Private files copied');
check($before === hash_file('sha256', $root . '/source/config/webengine.json'), 'Source changed');
$php = file_get_contents($root . '/destination/read.php');
check(strpos($php, '[MuOnline43_Auditoria]') !== false && strpos($php, '/home/mupanic/payments-private') === false, 'Live references retained');
symlink($root . '/source', $root . '/linked-source');
$rejected = false;
try { auditCopy($root . '/linked-source', $root . '/rejected'); } catch (RuntimeException $e) { $rejected = true; }
check($rejected, 'Source symlink accepted');
symlink($root . '/source', $root . '/linked-destination');
$rejected = false;
try { auditCopy($root . '/source', $root . '/linked-destination'); } catch (RuntimeException $e) { $rejected = true; }
check($rejected, 'Destination symlink accepted');
echo "OK: isolated copying, credential removal, database isolation, source preservation and symlink rejection.\n";
