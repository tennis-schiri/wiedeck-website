<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if(!$id || $id<1){http_response_code(404);exit;}
$stmt=db()->prepare('SELECT p.filename,p.mime,p.restaurant_id,p.visit_id,v.status,v.visit_date,v.food_text,v.verdict
 FROM photos p LEFT JOIN visits v ON v.id=p.visit_id AND v.restaurant_id=p.restaurant_id WHERE p.id=?');
$stmt->execute([$id]);$photo=$stmt->fetch();
$public=$photo && $photo['visit_id']!==null && $photo['status']==='published'
    && $photo['visit_date']!=='' && $photo['food_text']!=='' && $photo['verdict']!=='';
if(!$photo && !authenticated()){http_response_code(404);exit;}
if(!$public && !authenticated()){http_response_code(404);exit;}
$filename=__DIR__.'/private/images/'.$photo['filename'];
if(!is_file($filename)){http_response_code(404);exit;}
if(!in_array($photo['mime'],['image/jpeg','image/png','image/webp'],true)){http_response_code(404);exit;}
header('Content-Type: '.$photo['mime']);
header('Content-Disposition: inline');
header('Content-Length: '.filesize($filename));
header('Cache-Control: '.($public?'public, max-age=120':'private, no-store'));
readfile($filename);
