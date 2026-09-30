<?php
declare(strict_types=1);
require __DIR__.'/lib.php';
session_start();
header('Content-Type: application/json');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
function respond(array $body,int $code=200): never {http_response_code($code);echo json_encode($body,JSON_THROW_ON_ERROR);exit;}
try{
    if($_SERVER['REQUEST_METHOD']==='GET'){respond(['domains'=>rows(),'server_time'=>gmdate('c')]);}
    if($_SERVER['REQUEST_METHOD']!=='POST')respond(['error'=>'Method not allowed'],405);
    if(!isset($_SESSION['csrf'])||!hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN']??''))respond(['error'=>'Reload the page before saving.'],403);
    session_write_close();
    $input=json_decode(file_get_contents('php://input'),true,32,JSON_THROW_ON_ERROR);$action=$input['action']??'';
    if($action==='check'){
        $domains=$input['domains']??[];
        if(!is_array($domains)||count($domains)<1||count($domains)>3)respond(['error'=>'Check one to three domains at a time.'],422);
        $known=array_column(rows(),'domain');foreach($domains as $domain)if(!is_string($domain)||!in_array($domain,$known,true))respond(['error'=>'Unknown domain.'],422);
        // Share the scanner lock so browser rechecks cannot flood the provider during a scan.
        $checkLock=fopen(__DIR__.'/data/check.lock','c');
        if(!flock($checkLock,LOCK_EX|LOCK_NB))respond(['error'=>'A check is already running. Try again shortly.'],409);
        try{$result=checkDomains(array_unique($domains));}finally{flock($checkLock,LOCK_UN);fclose($checkLock);}
        respond(['results'=>$result]);
    }
    if($action==='save'){
        $domain=$input['domain']??'';if(!is_string($domain)||!in_array($domain,array_column(rows(),'domain'),true))respond(['error'=>'Unknown domain.'],422);
        $patch=[];
        foreach(['bought','favorite'] as $key)if(array_key_exists($key,$input)){if(!is_bool($input[$key]))respond(['error'=>'Invalid toggle.'],422);$patch[$key]=$input[$key];}
        updateStore(function($data)use($domain,$patch){foreach($data as &$r)if($r['domain']===$domain)$r=array_merge($r,$patch);unset($r);return $data;});respond(['saved'=>true]);
    }
    if($action==='add'){
        $word=strtolower(trim($input['word']??''));$word=preg_replace('/\.si$/','',$word);
        if(!preg_match('/^[a-z]{2,63}$/D',$word))respond(['error'=>'Enter one word with 2–63 English letters.'],422);
        $data=updateStore(function($data)use($word){if(in_array($word.'.si',array_column($data,'domain'),true))return $data;$data[]=['word'=>$word,'domain'=>$word.'.si','category'=>'Your additions','ai'=>false,'status'=>'unchecked','bought'=>false,'checked_at'=>null,'dictionary'=>'Not verified','recommended'=>false,'idea'=>''];return $data;});respond(['domain'=>$word.'.si']);
    }
    respond(['error'=>'Unknown action'],422);
}catch(Throwable $e){error_log($e->getMessage());respond(['error'=>'The request could not be completed. Your saved data is preserved.'],500);}
