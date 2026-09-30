<?php
require dirname(__DIR__).'/lib.php';
$pending=array_values(array_filter(rows(),fn($r)=>in_array($r['status'],['unchecked','unknown'],true)));$done=0;$total=count($pending);
foreach(array_chunk($pending,4) as $batch){
 $multi=curl_multi_init();$handles=[];
 foreach($batch as $r){$ch=curl_init('https://rdap.register.si/domain/'.$r['domain']);curl_setopt_array($ch,[CURLOPT_NOBODY=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15,CURLOPT_CONNECTTIMEOUT=>5]);$handles[$r['domain']]=$ch;curl_multi_add_handle($multi,$ch);}
 do{curl_multi_exec($multi,$running);if($running)curl_multi_select($multi,1);}while($running);
 $results=[];$limited=false;
 foreach($handles as $domain=>$ch){$http=curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$status=match($http){200,401=>'taken',404=>'candidate',default=>'unknown'};$result=['domain'=>$domain,'status'=>$status,'provider_status'=>'RDAP HTTP '.$http,'checked_at'=>gmdate('Y-m-d\TH:i:s\Z'),'http_status'=>$http,'source'=>'Register.si RDAP','source_url'=>'https://rdap.register.si/domain/'.$domain,'error'=>$status==='unknown'?'Registry could not confirm status':null];$results[$domain]=$result;file_put_contents(dirname(__DIR__).'/data/evidence.jsonl',json_encode($result)."\n",FILE_APPEND|LOCK_EX);$limited=$limited||$http===429;$done++;echo "$done/$total $domain $status\n";curl_multi_remove_handle($multi,$ch);curl_close($ch);}
 curl_multi_close($multi);
 updateStore(function($data)use($results){foreach($data as &$r)if(isset($results[$r['domain']])&&in_array($r['status'],['unchecked','unknown'],true))$r=array_merge($r,$results[$r['domain']]);unset($r);return $data;});
 if($limited){echo "Registry rate limit: screening stopped.\n";exit(2);}usleep(600000);
}
echo "Registry screening complete\n";
