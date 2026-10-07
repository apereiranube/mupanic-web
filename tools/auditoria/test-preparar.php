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
    'password_min_len' => 4, 'password_max_len' => 10, 'SQL_PASSWORD_ENCRYPTION' => 'phpmd5',
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
check($config['password_min_len'] === 4 && $config['password_max_len'] === 10 && $config['SQL_PASSWORD_ENCRYPTION'] === 'phpmd5', 'Password policy changed');
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
$flags = ['Base' => 'MuOnline43_Auditoria', 'Usuario' => 'mupanic_auditoria',
    'AccesoProduccion' => 0, 'EsSysadmin' => 0, 'EsDbOwner' => 0,
    'ControlBase' => 0, 'AlterarBase' => 0, 'CrearTablas' => 0,
    'EjecutarSetCoin' => 0, 'ModificarWCoin' => 0];
auditCheckFlags($flags);
foreach ($flags as $key => $value) {
    foreach ([null, 1, 'unexpected'] as $unsafe) {
        $bad = $flags;
        $bad[$key] = $unsafe;
        $rejected = false;
        try { auditCheckFlags($bad); } catch (RuntimeException $e) { $rejected = true; }
        check($rejected, 'Unsafe permissions accepted: ' . $key);
    }
}
$rejected = false;
try { auditVerify($root . '/destination'); } catch (RuntimeException $e) { $rejected = true; }
check($rejected, 'Incomplete configuration accepted');
echo "OK: isolated copying, credential removal, database isolation, source preservation and symlink rejection.\n";
echo "OK: verification rejects missing results, excessive permissions and incomplete configuration.\n";
if (function_exists('simplexml_load_string')) {
    $xml = auditXml('<config><smtp_pass>fake-secret</smtp_pass><verify_email>1</verify_email><register_enable_recaptcha>1</register_enable_recaptcha><experience>100</experience></config>');
    $data = simplexml_load_string($xml);
    check((string)$data->smtp_pass === '' && (string)$data->verify_email === '0' && (string)$data->register_enable_recaptcha === '0', 'Unsafe XML copied');
    check((string)$data->experience === '100', 'Unrelated XML setting changed');
    file_put_contents($root . '/source/config/register.xml', '<config><verify_email>1</verify_email></config>');
    file_put_contents($root . '/source/class.cache.php', '<?php // required core class');
    file_put_contents($root . '/destination/config/webengine.json', '{"SQL_DB_PASS":"saved-test-password"}');
    auditCopy($root . '/source', $root . '/destination', false);
    check(is_file($root . '/destination/class.cache.php'), 'Core cache class skipped');
    check((string)simplexml_load_file($root . '/destination/config/register.xml')->verify_email === '0', 'Missing module XML not restored');
    check(json_decode(file_get_contents($root . '/destination/config/webengine.json'), true)['SQL_DB_PASS'] === 'saved-test-password', 'Saved password overwritten');
    echo "OK: XML sanitized, missing core files restored, saved password preserved.\n";
    mkdir($root . '/report-source/includes/config/modules', 0755, true);
    file_put_contents($root . '/report-source/includes/config/modules/register.xml', '<config><active>1</active><verify_email>1</verify_email><smtp_pass>fake-secret</smtp_pass><register_recaptcha_secret_key>fake-token</register_recaptcha_secret_key></config>');
    $report = auditModuleReport($root . '/report-source');
    check($report['modules'][0] === ['module' => 'register', 'active' => true, 'verify_email' => true], 'Unexpected report fields');
    check(strpos(json_encode($report), 'fake-secret') === false && strpos(json_encode($report), 'fake-token') === false, 'Secret exported');
    echo "OK: module report exports only allowlisted activation flags.\n";
}
file_put_contents($root . '/generated-guard.php', auditGuardCode());
