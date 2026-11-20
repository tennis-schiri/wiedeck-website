<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$id=(string)($_GET['id'] ?? '');
$stmt=db()->prepare('SELECT * FROM restaurants WHERE id=? AND status="published" AND visit_date!="" AND food_text!="" AND verdict!=""');
$stmt->execute([$id]);$r=$stmt->fetch();
if(!$r){http_response_code(404);exit('Dieser Bericht ist noch nicht veröffentlicht.');}
$stmt=db()->prepare('SELECT id,caption FROM photos WHERE restaurant_id=? ORDER BY id');$stmt->execute([$id]);$photos=$stmt->fetchAll();
$ratingLabels=['score_food'=>'Essen & Trinken','score_ambience'=>'Atmosphäre','score_service'=>'Service','score_value'=>'Preis-Leistung'];
$validRatings=[];foreach($ratingLabels as $key=>$label)if($r[$key]!==null)$validRatings[]=(int)$r[$key];
$average=count($validRatings)>0?round(array_sum($validRatings)/count($validRatings),1):null;
?><!doctype html><html lang="de" data-theme="dark"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="<?=esc($r['name'].' – persönlicher Restaurantbericht von Michael Wiedeck')?>"><title><?=esc($r['name'])?> – Gut gegessen · wiedeck.de</title><link rel="stylesheet" href="../assets/restaurant-journal.css?v=20261120-public"></head><body>
<header class="top"><div class="shell top-inner"><a class="wordmark" href="../restaurants.html"><strong>wiedeck.de</strong></a><nav class="header-actions"><a class="back" href="../restaurants.html">← Alle Restaurants</a></nav></div></header>
<main class="shell public-report"><div class="page-heading"><span class="eyebrow">GUT GEGESSEN / <?=esc(mb_strtoupper($r['city']))?></span><h1><?=esc($r['name'])?></h1><p><?=esc($r['cuisine'])?></p><p><?=esc($r['visit_date'])?> · <?=esc($r['city'])?></p></div>
<?php if($photos): ?><div class="public-report-photos"><?php foreach($photos as $photo): ?><figure><img src="media.php?id=<?=$photo['id']?>" alt="<?=esc($photo['caption'])?>" loading="lazy"><figcaption><?=esc($photo['caption'])?></figcaption></figure><?php endforeach; ?></div><?php endif; ?>
<div class="public-report-body">
<?php foreach(['impression'=>'Mein erster Eindruck','food_text'=>'Essen & Trinken','ambience_text'=>'Atmosphäre','service_text'=>'Service','value_text'=>'Preis-Leistung','highlights'=>'Was besonders gut war','shortcomings'=>'Was mich gestört hat','verdict'=>'Mein Fazit'] as $key=>$title): if(trim((string)$r[$key])==='')continue; ?><section><h2><?=$title?></h2><p><?=nl2br(esc($r[$key]))?></p></section><?php endforeach; ?>
<?php if($average!==null): ?><section class="public-report-rating"><h2>Meine Bewertung</h2><p class="average"><?=esc(number_format($average,1,',','.'))?> / 10 ★</p><div class="ratings"><?php foreach($ratingLabels as $key=>$label):if($r[$key]===null)continue;?><div><span><?=$label?></span><strong><?=esc((string)$r[$key])?> / 10 ★</strong></div><?php endforeach; ?></div></section><?php endif; ?>
<?php if($r['revisit']!==''):?><p class="revisit">Würde ich wiederkommen? <strong><?=esc($r['revisit'])?></strong></p><?php endif; ?>
<?php if($r['address']!==''||$r['website']!==''):?><section><h2>Restaurantinfos</h2><p><?=esc($r['address'])?></p><?php if($r['website']!==''):?><a href="<?=esc($r['website'])?>" target="_blank" rel="noopener noreferrer">Restaurantwebsite ↗</a><?php endif;?></section><?php endif;?>
</div><div class="back-under"><a href="../restaurants.html">← Alle Restaurants</a></div></main>
<footer class="page-footer"><div class="shell"><span>© 2026 Michael Wiedeck</span><nav><a href="../impressum.html">Impressum</a><a href="../datenschutz.html">Datenschutz</a></nav></div></footer>
</body></html>
