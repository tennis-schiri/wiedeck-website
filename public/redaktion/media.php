<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if (!$id || $id<1){http_response_code(404);exit;}
$stmt=db()->prepare('SELECT p.filename,p.mime,r.status FROM photos p JOIN restaurants r ON r.id=p.restaurant_id WHERE p.id=?');
$stmt->execute([$id]);$photo=$stmt->fetch();
if (!$photo || ($photo['status']!=='published' && !authenticated())) {http_response_code(404);exit;}
$filename=__DIR__.'/private/images/'.$photo['filename'];
if(!is_file($filename)){http_response_code(404);exit;}
header('Content-Type: '.$photo['mime']);
header('Content-Disposition: inline');
header('Content-Length: '.filesize($filename));
header('Cache-Control: private, max-age=120');
readfile($filename);
