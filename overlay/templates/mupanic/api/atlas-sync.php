<?php
// Receives only public gameplay JSON. Disabled until the private token is installed.
require_once(__DIR__.'/../inc/atlas-runtime.php');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function atlasReply($status, $message) {
    http_response_code($status);
    echo json_encode(array('message'=>$message));
    exit;
}
if($_SERVER['REQUEST_METHOD'] !== 'POST') atlasReply(405, 'POST required');
$runtime = panicAtlasRuntimeDirectory();
$tokenFile = $runtime.'/token';
if(!is_file($tokenFile) || is_link($tokenFile)) atlasReply(503, 'Synchronization is not configured');
$token = trim(file_get_contents($tokenFile));
if(strlen($token) < 32) atlasReply(503, 'Synchronization is not configured');
$stamp = isset($_SERVER['HTTP_X_PANIC_TIMESTAMP']) ? $_SERVER['HTTP_X_PANIC_TIMESTAMP'] : '';
$signature = isset($_SERVER['HTTP_X_PANIC_SIGNATURE']) ? $_SERVER['HTTP_X_PANIC_SIGNATURE'] : '';
if(!preg_match('/^[0-9]+$/D', $stamp) || abs(time() - (int)$stamp) > 300) atlasReply(401, 'Invalid signature');
$body = file_get_contents('php://input', false, null, 0, 8000001);
if(strlen($body) > 8000000) atlasReply(413, 'Snapshot too large');
if(!hash_equals(hash_hmac('sha256', $stamp."\n".$body, $token), $signature)) atlasReply(401, 'Invalid signature');
$snapshot = json_decode($body, true);
$required = array('schemaVersion','generatedAt','sourceHash','revision','accounts','masterMonsterMin','resets','experience','maps','drops','crafting');
if(!is_array($snapshot) || array_diff($required, array_keys($snapshot)) || array_diff(array_keys($snapshot), array_merge($required, array('eventBags')))) atlasReply(422, 'Invalid snapshot fields');
if($snapshot['schemaVersion'] !== 2 || !is_string($snapshot['generatedAt']) || strtotime($snapshot['generatedAt']) === false || !is_string($snapshot['sourceHash']) || !preg_match('/^[a-f0-9]{64}$/D', $snapshot['sourceHash'])) atlasReply(422, 'Invalid snapshot metadata');
// Keep account names controlled because existing template sections display them directly.
if(!is_array($snapshot['accounts']) || count($snapshot['accounts']) !== 4) atlasReply(422, 'Invalid accounts');
foreach(array('Free','VIP 1','VIP 2','VIP 3') as $i=>$name) {
    $a = $snapshot['accounts'][$i];
    if(!is_array($a) || !isset($a['name'],$a['experience'],$a['master'],$a['drop']) || $a['name'] !== $name) atlasReply(422, 'Invalid account');
    foreach(array('experience','master','drop') as $key) if(!is_numeric($a[$key]) || $a[$key] < 0) atlasReply(422, 'Invalid rate');
}
if(!is_array($snapshot['maps']) || count($snapshot['maps']) < 1 || count($snapshot['maps']) > 256 || !is_array($snapshot['drops']) || count($snapshot['drops']) > 10000) atlasReply(422, 'Invalid collections');
foreach($snapshot['maps'] as $map) {
    if(!is_array($map) || !isset($map['id'],$map['name'],$map['moves'],$map['monsters'],$map['spots'],$map['equipmentDrop']) || !is_int($map['id']) || $map['id'] < 0 || !is_string($map['name']) || !is_array($map['monsters']) || !is_array($map['spots']) || !is_array($map['moves'])) atlasReply(422, 'Invalid map');
    foreach($map['monsters'] as $monster) {
        if(!isset($monster['id'],$monster['name'],$monster['level']) || !is_int($monster['id']) || !is_string($monster['name']) || !is_int($monster['level'])) atlasReply(422, 'Invalid monster');
    }
    foreach($map['spots'] as $spot) if(!isset($spot['x'],$spot['y'],$spot['monsters']) || !is_int($spot['x']) || !is_int($spot['y']) || $spot['x'] < 0 || $spot['x'] > 255 || $spot['y'] < 0 || $spot['y'] > 255 || !is_array($spot['monsters'])) atlasReply(422, 'Invalid spot');
}
foreach($snapshot['drops'] as $drop) {
    if(!isset($drop['name'],$drop['map'],$drop['monster'],$drop['min'],$drop['max'],$drop['rates']) || !is_string($drop['name']) || !is_array($drop['rates']) || count($drop['rates']) !== 4) atlasReply(422, 'Invalid drop');
    foreach($drop['rates'] as $rate) if(!is_numeric($rate) || $rate < 0 || $rate > 100) atlasReply(422, 'Invalid drop rate');
}
if(isset($snapshot['eventBags'])) {
    if(!is_array($snapshot['eventBags']) || count($snapshot['eventBags']) > 2000) atlasReply(422, 'Invalid reward lists');
    foreach($snapshot['eventBags'] as $bag) {
        if(!is_array($bag) || !isset($bag['id'],$bag['name'],$bag['monster'],$bag['monsterName'],$bag['item'],$bag['variant'],$bag['topHit'],$bag['special'],$bag['coins'],$bag['format'],$bag['settings'],$bag['items'],$bag['unsupportedSections']) || !is_int($bag['id']) || !is_int($bag['monster']) || !is_string($bag['name']) || !is_string($bag['monsterName']) || !in_array($bag['format'],array('standard','unsupported','missing'),true) || !is_array($bag['items']) || count($bag['items']) > 5000 || !is_array($bag['settings']) || !is_array($bag['coins']) || count($bag['coins']) !== 3 || !is_array($bag['unsupportedSections'])) atlasReply(422, 'Invalid reward list');
        if($bag['format'] === 'standard') {
            foreach(array('dropZen','itemDropRate','itemDropCount','setItemDropRate','itemDropType','fireworks','dropInventory') as $field) if(!isset($bag['settings'][$field]) || !is_int($bag['settings'][$field])) atlasReply(422, 'Invalid reward settings');
        }
        foreach($bag['items'] as $item) {
            if(!is_array($item) || !isset($item['name']) || !is_string($item['name'])) atlasReply(422, 'Invalid reward item');
            foreach(array('id','min','max','skill','luck','option','excellent','setOption','socketOption','pool') as $field) if(!isset($item[$field]) || !is_int($item[$field])) atlasReply(422, 'Invalid reward item fields');
        }
    }
}
if(!isset($snapshot['crafting']['mixRates'],$snapshot['crafting']['jewels'],$snapshot['crafting']['failLevelRemoval']) || !is_array($snapshot['resets']) || !is_array($snapshot['experience'])) atlasReply(422, 'Invalid crafting or progression');
// Reject malformed numeric fields before they reach PHP rendering or JS formulas.
function atlasCheckValues($value, $key='') {
    $numeric = array('id','level','life','damageMin','damageMax','defense','attackRate','defenseRate','respawnSeconds','x','y','quantity','zen','min','max','points','percent','experience','master','drop','excellent','ancient','map','monster','variant','masterMonsterMin','failLevelRemoval');
    if(in_array($key,$numeric,true) && !($key === 'experience' && is_array($value)) && (!is_int($value) && !is_float($value))) return false;
    if(is_string($value) && (strlen($value) > 512 || strpos($value,'<') !== false || strpos($value,'>') !== false)) return false;
    if(is_array($value)) foreach($value as $childKey=>$child) if(!atlasCheckValues($child, is_string($childKey) ? $childKey : '')) return false;
    return true;
}
if(!atlasCheckValues($snapshot)) atlasReply(422, 'Invalid public values');
$path = $runtime.'/public-balance.json';
$lock = fopen($runtime.'/sync.lock', 'c');
if(!$lock || !flock($lock, LOCK_EX)) atlasReply(503, 'Cannot lock snapshot');
if(is_file($path)) {
    $old = json_decode(file_get_contents($path), true);
    if(isset($old['generatedAt']) && strtotime($old['generatedAt']) > strtotime($snapshot['generatedAt'])) atlasReply(409, 'A newer snapshot is already available');
}
$temporary = tempnam($runtime, 'atlas-');
if(!$temporary || file_put_contents($temporary, json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) === false) atlasReply(503, 'Cannot store snapshot');
chmod($temporary, 0600);
if(!rename($temporary, $path)) { unlink($temporary); atlasReply(503, 'Cannot commit snapshot'); }
flock($lock, LOCK_UN); fclose($lock);
atlasReply(200, 'Snapshot updated');
