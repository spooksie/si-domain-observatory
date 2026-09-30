<?php
require dirname(__DIR__).'/lib.php';
$pending=array_values(array_filter(rows(),fn($r)=>preg_match('/^[a-z]{3}$/',$r['word'])&&in_array($r['status'],['unchecked','unknown','candidate'],true)));
usort($pending,fn($a,$b)=>($a['status']==='unchecked'?0:1)<=>($b['status']==='unchecked'?0:1));
$lock=fopen(dirname(__DIR__).'/data/check.lock','c');flock($lock,LOCK_EX);
$done=0;$unknown=0;$total=count($pending);
try{foreach(array_chunk($pending,8) as $batch){$results=checkDomains(array_column($batch,'domain'));foreach($results as $r){$done++;echo "$done/$total {$r['domain']} {$r['status']}\n";if($r['http_status']===429){echo "Rate limit; stopped.\n";exit(2);}}$unknown=count(array_filter($results,fn($r)=>$r['status']==='unknown'))===count($results)?$unknown+1:0;if($unknown>=3){echo "Repeated unconfirmed responses; stopped.\n";exit(3);}sleep(2);}}finally{flock($lock,LOCK_UN);fclose($lock);}echo "Three-letter scan complete\n";
