<?php
function checkSalon($ok,$message){if(!$ok)throw new RuntimeException($message);}
$hall=file_get_contents(__DIR__.'/../overlay/templates/mupanic/inc/fractura-hall.php');
checkSalon(strpos($hall,'salon-vencedores-mobile-v34.webp')!==false && strpos($hall,'salon-vencedores-v34.webp')!==false,'Art for desktop and mobile');
checkSalon(strpos($hall,'<table')===false && strpos($hall,'fractura-honors.php')===false,'No tables or technical rank section in hall');
checkSalon(strpos($hall,'F8')===false && strpos($hall,'misiones')===false && strpos($hall,'fh-premios')===false,'Claim instructions belong in Atlas');
$guide=file_get_contents(__DIR__.'/../overlay/templates/mupanic/inc/atlas-fractura.php');
foreach(['fractura-rangos','fractura-premios','fractura-cobro','F8','dos misiones','3 minutos','20 días','último golpe'] as $term)checkSalon(strpos($guide,$term)!==false,'Missing guide reference: '.$term);
$nav=file_get_contents(__DIR__.'/../overlay/templates/mupanic/inc/guide.php');
checkSalon(strpos($nav,"include(__DIR__.'/atlas-fractura.php')")!==false && strpos($nav,'href="#fractura"')!==false && strpos($nav,"/^fractura-/")!==false,'Atlas menu and deep-link boot');
$home=file_get_contents(__DIR__.'/../overlay/templates/mupanic/inc/atlas-home.php');checkSalon(strpos($home,'href="#fractura"')!==false,'Atlas home entry');
foreach(['fractura-week-data.php','fractura-ranks.php'] as $file){$reader=file_get_contents(__DIR__.'/../overlay/templates/mupanic/inc/'.$file);checkSalon(strpos($reader,'MUPanicWeekClaimsV31')===false && strpos($reader,'DECLARE @')===false,'Only public views and one result set');}
echo "PASS: hall art, no technical tables in Hall, complete Atlas guide, menu/home/deep links and public-view reader boundary.\n";
