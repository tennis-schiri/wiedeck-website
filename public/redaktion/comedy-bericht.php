<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$id=(string)($_GET['id']??'');
$stmt=db()->prepare('SELECT id,name,city,category,website FROM comedy_clubs WHERE id=?');
$stmt->execute([$id]);$club=$stmt->fetch();
if(!$club){http_response_code(404);exit('Club nicht gefunden.');}
$stmt=db()->prepare('SELECT show_date,title,comedians,report_text FROM comedy_shows
    WHERE club_id=? AND status="published" AND show_date!=""
    ORDER BY show_date DESC,id DESC');
$stmt->execute([$id]);$shows=$stmt->fetchAll();
if(!$shows){http_response_code(404);exit('Noch keine öffentlich freigegebenen Shows.');}
$unique=[];
foreach($shows as $show)foreach(preg_split('/\\r\\n|\\r|\\n/u',$show['comedians']) as $name){
    $name=trim($name);if($name!=='')$unique[mb_strtolower($name,'UTF-8')]=$name;
}
?><!doctype html><html lang="de" data-theme="dark"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="index,follow"><meta name="description" content="Meine Comedy-Abende bei <?=esc($club['name'])?>">
<title><?=esc($club['name'])?> – Comedy-Tagebuch · wiedeck.de</title>
<link rel="stylesheet" href="../assets/restaurant-journal.css?v=20261120-comedy">
<link rel="stylesheet" href="../assets/comedy-journal.css?v=20261120-comedy">
<script src="../theme.js?v=20261120-comedy"></script></head><body>
<header class="top" id="oben"><div class="shell top-inner"><a class="wordmark" href="../">
<span class="brand-symbol"><img class="logo-light" src="../logos/mw-symbol-light.png" alt=""><img class="logo-dark" src="../logos/mw-symbol-dark.png" alt=""></span><strong>wiedeck.de</strong></a>
<nav class="header-actions"><a class="back" href="../comedy.html">← Comedy-Locations</a><button type="button" id="theme-toggle">Darstellung wechseln</button></nav></div></header>
<main class="shell public-report"><div class="page-heading"><span class="eyebrow">MEINE COMEDY-ABENDE</span>
<h1><?=esc($club['name'])?></h1><p><?=esc($club['city'])?> · <?=count($shows)?> <?=count($shows)===1?'Besuch':'Besuche'?> · <?=count($unique)?> <?=count($unique)===1?'Comedian':'verschiedene Comedians'?>. Keine Künstlerbewertungen.</p></div>
<?php foreach($shows as $show):?>
<article class="comedy-public-show"><div class="comedy-public-head">
<span class="eyebrow"><?=esc(date('d.m.Y',strtotime($show['show_date'])))?></span>
<?php if($show['title']!==''):?><h2><?=esc($show['title'])?></h2><?php endif;?></div>
<?php if(trim($show['comedians'])!==''):?><h3>Aufgetretene Comedians</h3>
<ul class="comedy-performer-list"><?php foreach(preg_split('/\\r\\n|\\r|\\n/u',$show['comedians']) as $name):if(trim($name)==='')continue;?>
<li><?=esc(trim($name))?></li><?php endforeach;?></ul><?php endif;?>
<?php if(trim($show['report_text'])!==''):?><h3>Mein persönlicher Eindruck</h3>
<p class="comedy-report-text"><?=nl2br(esc($show['report_text']))?></p><?php endif;?>
</article>
<?php endforeach;?>
<?php if($club['website']!==''):?><p class="comedy-external"><a href="<?=esc($club['website'])?>" target="_blank" rel="noopener noreferrer">Club-Website ↗</a></p><?php endif;?>
<div class="back-under"><a href="../comedy.html">← Alle Comedy-Locations</a></div></main>
<footer class="page-footer"><div class="shell"><span>© 2026 Michael Wiedeck</span><nav><a href="../impressum.html">Impressum</a><a href="../datenschutz.html">Datenschutz</a></nav></div></footer>
</body></html>
