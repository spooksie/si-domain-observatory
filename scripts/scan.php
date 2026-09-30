<?php
require dirname(__DIR__).'/lib.php';
$statuses=in_array('--candidates-only',$argv,true)?['candidate']:['unchecked','unknown','candidate'];
$pending=array_values(array_filter(rows(),fn($r)=>in_array($r['status'],$statuses,true)));
$total=count($pending);$done=0;
foreach(array_chunk($pending,24) as $batch){
    $checkLock=fopen(dirname(__DIR__).'/data/check.lock','c');
    flock($checkLock,LOCK_EX);
    try{$results=checkDomains(array_column($batch,'domain'));}finally{flock($checkLock,LOCK_UN);fclose($checkLock);}
    foreach($results as $r){$done++;echo "$done/$total {$r['domain']} {$r['status']}\n";if($r['http_status']===429){echo "Rate limited. Scan stopped; unchecked rows retained for resume.\n";exit(2);}}
    sleep(3);
}
echo "Scan complete\n";
