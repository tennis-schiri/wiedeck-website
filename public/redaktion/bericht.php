<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$id=(string)($_GET['id']??'');
$stmt=db()->prepare('SELECT id,name,city,address,website,cuisine FROM restaurants WHERE id=?');
$stmt->execute([$id]);$restaurant=$stmt->fetch();
if(!$restaurant){http_response_code(404);exit('Restaurant nicht gefunden.');}
$stmt=db()->prepare('SELECT * FROM visits
 WHERE restaurant_id=? AND status="published" AND visit_date!=""
 ORDER BY visit_date DESC,id DESC');
$stmt->execute([$id]);$visits=$stmt->fetchAll();
if(!$visits){http_response_code(404);exit('Noch kein Besuch veröffentlicht.');}
$photoQuery=db()->prepare('SELECT id,caption FROM photos WHERE restaurant_id=? AND visit_id=? ORDER BY id');
$ratingLabels=['score_food'=>'Essen & Trinken','score_ambience'=>'Atmosphäre','score_service'=>'Service','score_value'=>'Preis-Leistung'];
$sections=['impression'=>'Erster Eindruck','food_text'=>'Essen & Trinken','ambience_text'=>'Atmosphäre',
'service_text'=>'Service','value_text'=>'Preis-Leistung','highlights'=>'Was besonders gut war',
'shortcomings'=>'Was mich gestört hat','verdict'=>'Mein Fazit'];
?><!doctype html><html lang="de" data-theme="dark"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="index,follow">
<meta name="description" content="<?=esc($restaurant['name'].' – '.count($visits).' persönliche Restaurantbesuche von Michael Wiedeck')?>">
<title><?=esc($restaurant['name'])?> – Meine Besuche · wiedeck.de</title>
<link rel="stylesheet" href="../assets/restaurant-journal.css?v=20261120-multi-visit">
<script src="../theme.js?v=20261118-footer-scroll"></script></head><body>
<header class="top"><div class="shell top-inner"><a class="wordmark" href="../restaurants.html"><strong>wiedeck.de</strong></a><nav class="header-actions"><a class="back" href="../restaurants.html">← Alle Restaurants</a></nav></div></header>
<main class="shell public-report"><div class="page-heading">
<span class="eyebrow">GUT GEGESSEN / <?=esc(mb_strtoupper($restaurant['city']))?></span>
<h1><?=esc($restaurant['name'])?></h1><p><?=esc($restaurant['cuisine'])?></p>
<p><?=count($visits)?> <?=count($visits)===1?'veröffentlichter Besuch':'veröffentlichte Besuche'?></p></div>
<nav class="visit-navigation" aria-label="Besuche"><strong>Meine Besuche:</strong>
<?php foreach($visits as $index=>$v):?><a href="#besuch-<?=$v['id']?>"><?=esc(date('d.m.Y',strtotime($v['visit_date'])))?></a><?php endforeach;?></nav>
<?php foreach($visits as $index=>$v):
    $photoQuery->execute([$id,$v['id']]);$photos=$photoQuery->fetchAll();
    $ratings=[];foreach($ratingLabels as $key=>$label)if($v[$key]!==null)$ratings[]=(int)$v[$key];
    $average=$ratings?round(array_sum($ratings)/count($ratings),1):null;
?>
<article class="public-visit" id="besuch-<?=$v['id']?>">
<div class="public-visit-heading"><span class="eyebrow">BESUCH <?=str_pad((string)(count($visits)-$index),2,'0',STR_PAD_LEFT)?></span>
<h2><?=esc(date('d.m.Y',strtotime($v['visit_date'])))?></h2>
<?php if($v['occasion']!==''):?><p><?=esc($v['occasion'])?></p><?php endif;?>
<?php if($v['companions']!==''):?><p>Begleitung: <?=esc($v['companions'])?></p><?php endif;?>
<?php if($v['price']!==''):?><p>Ausgaben: <?=esc($v['price'])?></p><?php endif;?></div>
<?php if($photos):?><div class="public-report-photos"><?php foreach($photos as $photo):?>
<figure><img src="media.php?id=<?=$photo['id']?>" alt="<?=esc($photo['caption'])?>" loading="lazy"><figcaption><?=esc($photo['caption'])?></figcaption></figure>
<?php endforeach;?></div><?php endif;?>
<div class="public-report-body"><?php foreach($sections as $key=>$heading):if(trim((string)$v[$key])==='')continue;?>
<section><h3><?=esc($heading)?></h3><p><?=nl2br(esc($v[$key]))?></p></section><?php endforeach;?>
<?php if($average!==null):?><section class="public-report-rating"><h3>Meine Bewertung</h3>
<p class="average"><?=esc(number_format($average,1,',','.'))?> / 10 ★</p><div class="ratings">
<?php foreach($ratingLabels as $key=>$label):if($v[$key]===null)continue;?><div><span><?=esc($label)?></span><strong><?=esc((string)$v[$key])?> / 10 ★</strong></div><?php endforeach;?>
</div></section><?php endif;?>
<?php if($v['revisit']!==''):?><p class="revisit">Würde ich wiederkommen? <strong><?=esc($v['revisit'])?></strong></p><?php endif;?>
</div></article>
<?php endforeach;?>
<?php if($restaurant['address']!==''||$restaurant['website']!==''):?><section class="restaurant-master-contact"><h2>Restaurantinfos</h2><p><?=esc($restaurant['address'])?></p><?php if($restaurant['website']!==''):?><a href="<?=esc($restaurant['website'])?>" target="_blank" rel="noopener noreferrer">Restaurantwebsite ↗</a><?php endif;?></section><?php endif;?>
<div class="back-under"><a href="../restaurants.html">← Alle Restaurants</a></div></main>
<footer class="page-footer"><div class="shell"><span>© 2026 Michael Wiedeck</span><nav><a href="../impressum.html">Impressum</a><a href="../datenschutz.html">Datenschutz</a></nav></div></footer></body></html>
