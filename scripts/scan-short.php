<?php
require dirname(__DIR__).'/lib.php';
$pending=array_values(array_filter(rows(),fn($r)=>preg_match('/^[a-z]{2,3}$/',$r['word'])&&in_array($r['status'],['unchecked','unknown','candidate'],true)));
usort($pending,fn($a,$b)=>strlen($b['word'])<=>strlen($a['word']));
$done=0;$total=count($pending);$unknown=0;
$checkLock=fopen(dirname(__DIR__).'/data/check.lock','c');
if(!flock($checkLock,LOCK_EX|LOCK_NB))throw new RuntimeException('Another check is running.');
try{foreach(array_chunk($pending,48) as $batch){$results=checkDomains(array_column($batch,'domain'));foreach($results as $r){$done++;echo "$done/$total {$r['domain']} {$r['status']}\n";if($r['http_status']===429){echo "Rate limited; stopped for later resume.\n";exit(2);}}$unknown=count(array_filter($results,fn($r)=>$r['status']==='unknown'))===count($results)?$unknown+1:0;if($unknown>=3){echo "Three unconfirmed batches; stopped to avoid unproductive requests.\n";exit(3);}sleep(3);}}finally{flock($checkLock,LOCK_UN);fclose($checkLock);}echo "Scan complete\n";
