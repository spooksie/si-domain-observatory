<?php
require dirname(__DIR__).'/lib.php';
$groups=json_decode(file_get_contents(dirname(__DIR__).'/data/modern-candidates.json'),true,512,JSON_THROW_ON_ERROR);
$added=0;
updateStore(function($rows)use($groups,&$added){
    $known=array_fill_keys(array_column($rows,'domain'),true);
    foreach($groups as $theme=>$words)foreach(preg_split('/\s+/',trim($words)) as $word){
        $domain=$word.'.si';if(isset($known[$domain]))continue;$known[$domain]=true;
        $rows[]=['word'=>$word,'domain'=>$domain,'category'=>'Modern service names','ai'=>true,'dictionary'=>'Coined / modern brand name','status'=>'unchecked','checked_at'=>null,'bought'=>false,'favorite'=>false,'recommended'=>false,'idea'=>'Coined name for '.strtolower($theme).'.','service_theme'=>$theme];
        $added++;
    }
    return $rows;
});
echo "Added $added unique modern service names\n";
