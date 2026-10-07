<?php
define('access', true);
require __DIR__.'/../overlay/templates/mupanic/inc/launch-runtime.php';
$config = require __DIR__.'/../overlay/templates/mupanic/inc/launch-config.php';
$cases = [
 ['https://beta.mupanic.com.ar/', '', '', false],
 ['https://beta.mupanic.com.ar/', '', 'launch', true],
 ['https://beta.mupanic.com.ar/', 'info', 'launch', false],
 ['https://mupanic.com.ar/', '', '', true],
 ['https://www.mupanic.com.ar/', '', '', true],
 ['https://mupanic.com.ar/', 'login', '', false],
 ['https://mupanic.com.ar/', 'api', '', false],
 ['https://mupanic.com.ar.attacker.test/', '', '', false],
];
foreach($cases as $case) {
 if(panicLaunchVisible($case[0],$case[1],$case[2],$config) !== $case[3]) throw new Exception('Unexpected gate: '.json_encode($case));
}
$config['productionMode'] = 'website';
if(panicLaunchVisible('https://mupanic.com.ar/','','launch',$config)) throw new Exception('Production toggle must restore home');
if(!panicLaunchVisible('https://beta.mupanic.com.ar/','','launch',$config)) throw new Exception('Preview must remain available');
echo "Launch gate: 10 checks passed\n";
