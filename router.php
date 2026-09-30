<?php
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if(preg_match('#^/(data|scripts)/#',$path)||in_array($path,['/lib.php','/router.php'],true)){http_response_code(404);exit;}
if($path==='/'||$path==='/index.php'){require __DIR__.'/index.php';return true;}
return false;
