<?php
require dirname(__DIR__).'/lib.php';
$groups=json_decode(file_get_contents(dirname(__DIR__).'/data/name-candidates.json'),true,512,JSON_THROW_ON_ERROR);
$added=0;
updateStore(function($rows)use($groups,&$added){
 $known=array_fill_keys(array_column($rows,'domain'),true);
 foreach($groups as $theme=>$words)foreach(preg_split('/\s+/',trim($words)) as $word){
  $domain=$word.'.si';if(isset($known[$domain]))continue;$known[$domain]=true;
  $idea=match($theme){'Vincent & friends'=>'A Vincent-family name or nickname for a personal SI companion.','Friendly assistant nicknames'=>'A friendly nickname for a conversational assistant.','Short first names'=>'A short personal name for a helpful SI service.',default=>'A familiar personal-name spelling for an SI assistant.'};
  $rows[]=['word'=>$word,'domain'=>$domain,'category'=>'Names & nicknames','ai'=>true,'dictionary'=>'Personal name / nickname','status'=>'unchecked','checked_at'=>null,'bought'=>false,'favorite'=>false,'recommended'=>in_array($word,['vinnie','vinny','vince','vincenzo','enzo'],true),'rank'=>match($word){'vinnie'=>120,'vinny'=>119,'vince'=>118,'enzo'=>117,default=>0},'idea'=>$idea,'service_theme'=>$theme];
  $added++;
 }return $rows;
});
echo "Added $added name and nickname candidates\n";
