<?php
declare(strict_types=1);
const STORE = __DIR__.'/data/domains.json';
const LOCK = __DIR__.'/data/store.lock';
function rows(): array { return json_decode(file_get_contents(STORE), true, 512, JSON_THROW_ON_ERROR); }
function updateStore(callable $fn): array {
    $lock=fopen(LOCK,'c'); if(!$lock || !flock($lock,LOCK_EX)) throw new RuntimeException('Cannot lock storage');
    try {
        $data=rows(); $data=$fn($data);
        $tmp=tempnam(__DIR__.'/data','save-');
        if(file_put_contents($tmp,json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n")===false || !rename($tmp,STORE)) throw new RuntimeException('Cannot save data');
        writeMarkdown($data); return $data;
    } finally {flock($lock,LOCK_UN);fclose($lock);}
}
function writeMarkdown(array $data): void {
    $counts=array_count_values(array_column($data,'status'));
    $out="# QQuantum.ai .si domain shortlist: words, service names, personal names, and domain hacks\n\nUpdated: ".(new DateTimeImmutable('now',new DateTimeZone('Europe/Madrid')))->format('Y-m-d H:i:s T')."\n\n";
    $out.="Candidates: ".count($data)." · Available: ".($counts['available']??0)." · Taken: ".($counts['taken']??0)." · Reserved: ".($counts['reserved']??0)." · Unchecked: ".($counts['unchecked']??0)." · Awaiting registrar confirmation: ".($counts['candidate']??0)." · Unknown: ".($counts['unknown']??0)."\n\n";
    $out.="Availability is a dated registrar response (Neoserv or Domenca), not a reservation. Recheck before checkout. .si is Slovenia's country-code domain; ‘super intelligence’ is a branding interpretation. Prices are the checking registrar's returned EUR amounts including Slovenian VAT for the returned duration; final checkout and renewal terms may differ. The checking registrar is recorded for each row; confirmation at one registrar is not independent confirmation at both. Bought is your manual tracking flag and does not place an order.\n\nSources: [Neoserv](https://www.neoserv.si/domene) · [Domenca](https://www.domenca.com/domene/) · [Registry RDAP explanation](https://www.register.si/en/rdap/)\n\nDictionary words were curated from English vocabulary, including established scientific/computing terms. Modern service names are separately labelled coined brand candidates; their suggested uses are branding ideas, not claimed dictionary meanings. Bundled dictionary matches are recorded per row; specialist terms absent from that dictionary are labelled separately. Names & nicknames includes personal names and spelling variants. Domain hacks join the label and si into a word or name; their language or name type is recorded. Brand and trademark suitability have not been assessed.\n\n";
    $sorted=$data; usort($sorted,fn($a,$b)=>[match($a['status']){'available'=>0,'unchecked','candidate'=>1,'unknown'=>2,default=>3},-($a['rank']??0),!($a['recommended']??false),$a['domain']]<=>[match($b['status']){'available'=>0,'unchecked','candidate'=>1,'unknown'=>2,default=>3},-($b['rank']??0),!($b['recommended']??false),$b['domain']]);
    foreach(['available'=>'Available at last check','unchecked'=>'Awaiting check','candidate'=>'Unregistered — registrar confirmation pending','unknown'=>'Could not confirm','taken'=>'Already registered or currently unavailable','reserved'=>'Reserved / unavailable'] as $status=>$title){
        $group=array_filter($sorted,fn($r)=>$r['status']===$status);if(!$group)continue;
        $out.="## $title\n\n| Domain | Theme | AI fit | Name type | Idea | EUR incl. VAT | Checked (UTC) | Source | Bought | Registrar links |\n|---|---|---|---|---|---|---|---|---|---|\n";
        foreach($group as $r){$domain=$r['domain'];$price=isset($r['price_eur'])?number_format($r['price_eur'],2).' / '.($r['duration']??1).' year(s)':'—';$out.="| $domain | {$r['category']} | ".($r['ai']?'Yes':'General brand')." | ".($r['dictionary']??'Curated term')." | ".($r['idea']??'—')." | $price | ".($r['checked_at']??'—')." | ".($r['source']??'—')." | ".($r['bought']?'Yes':'No')." | [Neoserv](https://www.neoserv.si/domene?domena=$domain) · [Domenca](https://www.domenca.com/portal/en_US/shoppingcart-domainsearchengine/goal/domain/?initialDomain=$domain) |\n";}
        $out.="\n";
    }
    $temp=tempnam(__DIR__,'md-');file_put_contents($temp,$out);rename($temp,__DIR__.'/DOMAINS.md');
}
function checkNeoserv(array $domains): array {
    $multi=curl_multi_init();$handles=[];
    foreach($domains as $domain){
        $ch=curl_init('https://api.avant.si/api/domains/available?domain='.rawurlencode($domain));
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>20,CURLOPT_HTTPHEADER=>['Accept: application/json'],CURLOPT_USERAGENT=>'siDomains-personal-shortlist/1.0']);
        $handles[$domain]=$ch;curl_multi_add_handle($multi,$ch);
    }
    do{$code=curl_multi_exec($multi,$running);if($running)curl_multi_select($multi,1);}while($running&&$code===CURLM_OK);
    $results=[];
    foreach($handles as $domain=>$ch){
        $http=curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$raw=curl_multi_getcontent($ch);$decoded=json_decode($raw,true);$d=$decoded['data']??[];
        $provider=$d['status']??null;
        $valid=$http===200&&($decoded['success']??false)===true&&strtolower($d['domain']??'')===$domain;
        $status=$valid?match($provider){'domain-available','domain-available-with-terms'=>'available','domain-registered','domain-registered-with-us','domain-premium-registered','domain-premium-registered-with-us','domain-registered-with-us-expired','domain-registered-with-us-deleted'=>'taken','domain-reserved'=>'reserved',default=>'unknown'}:'unknown';
        $result=['domain'=>$domain,'status'=>$status,'provider_status'=>$provider,'http_status'=>$http,'checked_at'=>gmdate('Y-m-d\TH:i:s\Z'),'source'=>'Neoserv live checker','source_url'=>'https://api.avant.si/api/domains/available?domain='.$domain,'price_eur'=>$status==='available'?($d['lowestPrice']['priceVat']??null):null,'duration'=>$status==='available'?($d['lowestPrice']['duration']??$d['duration']??null):null,'standard_price_eur'=>$status==='available'?($d['defaultPrice']['priceVat']??null):null,'error'=>$valid?null:($http===429?'Rate limit; wait before retrying':(curl_error($ch)?:'Provider response could not be validated'))];
        $results[]=$result;
        file_put_contents(__DIR__.'/data/evidence.jsonl',json_encode(['checked_at'=>$result['checked_at'],'domain'=>$domain,'http_status'=>$http,'provider_status'=>$provider,'success'=>$decoded['success']??null,'price_eur'=>$result['price_eur'],'duration'=>$result['duration'],'source_url'=>$result['source_url']])."\n",FILE_APPEND|LOCK_EX);
        curl_multi_remove_handle($multi,$ch);curl_close($ch);
    }
    curl_multi_close($multi);
    updateStore(function($data)use($results){$index=array_column($results,null,'domain');foreach($data as &$row)if(isset($index[$row['domain']]))$row=array_merge($row,$index[$row['domain']]);unset($row);return $data;});
    return $results;
}
function checkDomains(array $domains): array {
    $url='https://dac.domenca.com/portal/en_US/new-shopping-cart/get-domain-availability-info';
    $multi=curl_multi_init();$handles=[];
    foreach(array_chunk($domains,8) as $chunk){
        $fields=['defaultDomain'=>$chunk[0]];foreach($chunk as $i=>$domain)$fields['domains['.$i.']']=$domain;
        $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($fields),CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>30,CURLOPT_HTTPHEADER=>['Accept: application/json'],CURLOPT_USERAGENT=>'siDomains-personal-shortlist/1.0']);
        $handles[]=['handle'=>$ch,'domains'=>$chunk];curl_multi_add_handle($multi,$ch);
    }
    do{curl_multi_exec($multi,$running);if($running)curl_multi_select($multi,1);}while($running);
    $replies=[];
    foreach($handles as $entry){$ch=$entry['handle'];$decoded=json_decode(curl_multi_getcontent($ch)?:'',true);foreach($entry['domains'] as $domain)$replies[$domain]=['data'=>$decoded,'http'=>curl_getinfo($ch,CURLINFO_RESPONSE_CODE),'error'=>curl_error($ch)];curl_multi_remove_handle($multi,$ch);curl_close($ch);}
    curl_multi_close($multi);$results=[];
    foreach($domains as $domain){
        $decoded=$replies[$domain]['data'];$http=$replies[$domain]['http'];$error=$replies[$domain]['error'];
        $d=$decoded['domains'][$domain]??[];
        $valid=$http===200&&empty($decoded['errors'])&&isset($d['available'])&&is_bool($d['available'])&&strtolower($d['ascii']??'')===$domain;
        $status=$valid?(!empty($d['reserved'])?'reserved':($d['available']?'available':'taken')):'unknown';
        $priceText=html_entity_decode(!empty($d['discounted'])?($d['discountedPrice']??''):($d['price']??''),ENT_QUOTES|ENT_HTML5,'UTF-8');
        $price=null;$duration=null;if(preg_match('/([0-9]+(?:[.,][0-9]{2})?)\s*€/u',$priceText,$m))$price=(float)str_replace(',','.',$m[1]);
        if(preg_match('/(?:for\s+|\/\s*)(\d+)\.?\s*year/i',$priceText,$m))$duration=(int)$m[1];
        $standard=null;if(preg_match('/([0-9]+(?:[.,][0-9]{2})?)\s*€/u',html_entity_decode($d['price']??'',ENT_QUOTES|ENT_HTML5,'UTF-8'),$m))$standard=(float)str_replace(',','.',$m[1]);
        $result=['domain'=>$domain,'status'=>$status,'provider_status'=>$valid?($d['available']?'available':(!empty($d['reserved'])?'reserved':'unavailable')):null,'http_status'=>$http,'checked_at'=>gmdate('Y-m-d\TH:i:s\Z'),'source'=>'Domenca live checker','source_url'=>$url,'price_eur'=>$status==='available'?$price:null,'duration'=>$status==='available'?$duration:null,'standard_price_eur'=>$status==='available'?$standard:null,'error'=>$valid?null:($http===429?'Rate limit; wait before retrying':($error?:'Provider response could not be validated'))];
        $results[]=$result;
        file_put_contents(__DIR__.'/data/evidence.jsonl',json_encode(['checked_at'=>$result['checked_at'],'domain'=>$domain,'source'=>'Domenca','http_status'=>$http,'provider_response'=>$d,'provider_errors'=>$decoded['errors']??null,'source_url'=>$url,'request'=>['domains'=>[$domain],'defaultDomain'=>$domains[0]]])."\n",FILE_APPEND|LOCK_EX);
    }
    updateStore(function($data)use($results){$index=array_column($results,null,'domain');foreach($data as &$row)if(isset($index[$row['domain']]))$row=array_merge($row,$index[$row['domain']]);unset($row);return $data;});return $results;
}
