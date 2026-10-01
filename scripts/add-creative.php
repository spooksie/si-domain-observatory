<?php
require dirname(__DIR__).'/lib.php';
$groups=json_decode(file_get_contents(dirname(__DIR__).'/candidates/creative-themes.json'),true,512,JSON_THROW_ON_ERROR);
$added=0;
updateStore(function($rows)use($groups,&$added){
    $known=array_fill_keys(array_column($rows,'domain'),true);
    foreach($groups as $theme=>$group)foreach(preg_split('/\s+/',trim($group['words'])) as $word){
        if(!preg_match('/^[a-z]{3,12}$/',$word))throw new RuntimeException('Invalid candidate');
        $domain=$word.'.si';if(isset($known[$domain]))continue;$known[$domain]=true;
        $rows[]=['word'=>$word,'domain'=>$domain,'category'=>$theme,'ai'=>true,'dictionary'=>$theme==='Playful sound'?'Coined sound-based name':'Coined / word-inspired brand name','status'=>'unchecked','checked_at'=>null,'bought'=>false,'favorite'=>false,'recommended'=>false,'idea'=>$group['idea']];$added++;
    }return $rows;
});
echo "Added $added creative candidates\n";
