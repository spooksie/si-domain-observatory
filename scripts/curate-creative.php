<?php
require dirname(__DIR__).'/lib.php';
$root=dirname(__DIR__);
$groups=json_decode(file_get_contents($root.'/candidates/creative-themes.json'),true,512,JSON_THROW_ON_ERROR);
$picks=json_decode(file_get_contents($root.'/candidates/creative-picks.json'),true,512,JSON_THROW_ON_ERROR);
$data=updateStore(function($rows)use($groups,$picks){foreach($rows as &$r){if(!isset($groups[$r['category']]))continue;$r['recommended']=$r['status']==='available'&&isset($picks[$r['word']]);$r['rank']=$r['recommended']?85:0;if($r['recommended'])$r['idea']=$picks[$r['word']];}unset($r);return $rows;});
$out="# Creative .si naming directions\n\nChecked: ".gmdate('Y-m-d H:i').' UTC · [Browse the viewer](https://spooksie.github.io/si-domain-observatory/)\n\n';
$out.="Names were chosen around product purpose, personality and spoken sound. These are coined or word-inspired candidates, not claimed English dictionary entries. Shorter names help recall, but a clear sound and a recognisable product promise matter more than shaving off one letter. Names ending in iq, io or va are exploratory alternatives; the recommendations favour distinctiveness and pronounceability over mechanical suffix patterns.\n\nAvailability is Domenca’s dated result, not a reservation. Product ideas and brand quality are editorial judgements. The name itself has not been cleared for brand or trademark use.\n\n";
$out.="| Theme | Checked candidates | Available | Unconfirmed / unchecked | Product direction |\n|---|---:|---:|---:|---|\n";
foreach($groups as $theme=>$group){$set=array_filter($data,fn($r)=>$r['category']===$theme);$counts=array_count_values(array_column($set,'status'));$pending=($counts['unknown']??0)+($counts['unchecked']??0)+($counts['candidate']??0);$out.="| $theme | ".count($set).' | '.($counts['available']??0)." | $pending | {$group['idea']} |\n";}
$out.="\n## My strongest available candidates\n\n";
foreach($groups as $theme=>$group){$best=array_filter($data,fn($r)=>$r['category']===$theme&&($r['recommended']??false));if(!$best)continue;$out.="### $theme\n\n";foreach($best as $r)$out.="- **{$r['domain']}** — {$r['idea']} Checked {$r['checked_at']}.\n";$out.="\n";}
$out.="## Available names by theme\n\n";
foreach($groups as $theme=>$group){$available=array_values(array_filter($data,fn($r)=>$r['category']===$theme&&$r['status']==='available'));usort($available,fn($a,$b)=>[strlen($a['word']),$a['word']]<=>[strlen($b['word']),$b['word']]);$out.="### $theme\n\n".implode(', ',array_column($available,'domain'))."\n\n";}
file_put_contents($root.'/CREATIVE-NAMING.md',$out);
echo "Creative recommendations saved\n";
