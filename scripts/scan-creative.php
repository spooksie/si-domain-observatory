<?php
require dirname(__DIR__).'/lib.php';
$themes=array_keys(json_decode(file_get_contents(dirname(__DIR__).'/candidates/creative-themes.json'),true,512,JSON_THROW_ON_ERROR));
$individual=in_array('--individual',$argv,true);
$lock=fopen(dirname(__DIR__).'/data/check.lock','c');flock($lock,LOCK_EX);
try{
    $pending=array_values(array_filter(rows(),fn($r)=>in_array($r['category'],$themes,true)&&in_array($r['status'],['unchecked','unknown','candidate'],true)));
    $total=count($pending);$done=0;$bad=0;
    foreach(array_chunk($pending,$individual?1:8) as $batch){
        $results=checkDomains(array_column($batch,'domain'),true,true);
        foreach($results as $r){$done++;echo "$done/$total {$r['domain']} {$r['status']}\n";if($r['http_status']===429){echo "Rate limit; stopped.\n";exit(2);}}
        $bad=count(array_filter($results,fn($r)=>$r['status']==='unknown'))===count($results)?$bad+1:0;
        if($bad>=3){echo "Repeated unconfirmed responses; stopped.\n";exit(3);}sleep(2);
    }
}finally{flock($lock,LOCK_UN);fclose($lock);}
echo "Creative scan complete\n";
