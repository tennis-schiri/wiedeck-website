<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
guard();
$error='';
$clubId=(string)($_GET['club']??$_POST['club_id']??'');
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $action=(string)($_POST['action']??'');
    if($action==='new_club'){
        $name=mb_substr(trim((string)($_POST['name']??'')),0,140);
        $city=mb_substr(trim((string)($_POST['city']??'')),0,100);
        $category=in_array($_POST['category']??'', ['Clubs & kleine Bühnen','Andere Bühnen & besondere Orte'],true)?$_POST['category']:'Andere Bühnen & besondere Orte';
        if($name===''||$city==='')$error='Name und Stadt eingeben.';
        else{
            $clubId='comedy-own-'.bin2hex(random_bytes(7));
            db()->prepare('INSERT INTO comedy_clubs(id,name,city,category) VALUES(?,?,?,?)')->execute([$clubId,$name,$city,$category]);
            redirect('comedy.php?club='.rawurlencode($clubId));
        }
    }
    elseif($action==='save_club'){
        $name=mb_substr(trim((string)($_POST['name']??'')),0,140);
        $city=mb_substr(trim((string)($_POST['city']??'')),0,100);
        $website=mb_substr(trim((string)($_POST['website']??'')),0,320);
        $category=in_array($_POST['category']??'', ['Clubs & kleine Bühnen','Andere Bühnen & besondere Orte'],true)?$_POST['category']:'Andere Bühnen & besondere Orte';
        if($name===''||$city==='')$error='Name und Stadt eingeben.';
        elseif($website!==''&&(!filter_var($website,FILTER_VALIDATE_URL)||!str_starts_with($website,'https://')))$error='Nur HTTPS-Websites zulässig.';
        else{
            $stmt=db()->prepare('UPDATE comedy_clubs SET name=?,city=?,category=?,website=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
            $stmt->execute([$name,$city,$category,$website,$clubId]);
            redirect('comedy.php?club='.rawurlencode($clubId).'&saved=1');
        }
    }
    elseif($action==='new_show'){
        $exists=db()->prepare('SELECT 1 FROM comedy_clubs WHERE id=?');$exists->execute([$clubId]);
        if(!$exists->fetchColumn())$error='Club nicht gefunden.';
        else{
            db()->prepare('INSERT INTO comedy_shows(club_id) VALUES(?)')->execute([$clubId]);
            redirect('comedy.php?club='.rawurlencode($clubId).'&show='.db()->lastInsertId());
        }
    }
    elseif($action==='save_show'){
        $showId=(int)($_POST['show_id']??0);
        $date=trim((string)($_POST['show_date']??''));
        $title=mb_substr(trim((string)($_POST['title']??'')),0,200);
        $comedians=mb_substr(trim((string)($_POST['comedians']??'')),0,6000);
        $notes=mb_substr(trim((string)($_POST['notes']??'')),0,12000);
        $aiNotes=mb_substr(trim((string)($_POST['ai_notes']??'')),0,6000);
        $report=mb_substr(trim((string)($_POST['report_text']??'')),0,12000);
        $status=($_POST['status']??'draft')==='published'?'published':'draft';
        $find=db()->prepare('SELECT published_at FROM comedy_shows WHERE id=? AND club_id=?');
        $find->execute([$showId,$clubId]);$found=$find->fetch();
        if(!$found)$error='Auftritt nicht gefunden.';
        elseif($date!=='' && (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/',$date) || !checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4))))$error='Datum ungültig.';
        elseif($status==='published' && ($date===''||($title===''&&$comedians===''&&$report==='')))$error='Zum Freigeben Datum und mindestens einen Namen, Titel oder Bericht angeben.';
        else{
            $publishedAt=$status==='published'?($found['published_at']?:gmdate('Y-m-d H:i:s')):null;
            db()->prepare('UPDATE comedy_shows SET show_date=?,title=?,comedians=?,notes=?,ai_notes=?,report_text=?,status=?,published_at=?,updated_at=CURRENT_TIMESTAMP WHERE id=? AND club_id=?')
               ->execute([$date,$title,$comedians,$notes,$aiNotes,$report,$status,$publishedAt,$showId,$clubId]);
            redirect('comedy.php?club='.rawurlencode($clubId).'&show='.$showId.'&saved=1');
        }
    }
    elseif($action==='delete_show'){
        $showId=(int)($_POST['show_id']??0);
        db()->prepare('DELETE FROM comedy_shows WHERE id=? AND club_id=?')->execute([$showId,$clubId]);
        redirect('comedy.php?club='.rawurlencode($clubId));
    }
    elseif($action==='delete_club'){
        db()->prepare('DELETE FROM comedy_clubs WHERE id=?')->execute([$clubId]);
        redirect('comedy.php');
    }
}
$clubs=db()->query('SELECT c.*,COUNT(s.id) AS shows,
    SUM(CASE WHEN s.status="published" THEN 1 ELSE 0 END) AS published
    FROM comedy_clubs c LEFT JOIN comedy_shows s ON s.club_id=c.id
    GROUP BY c.id ORDER BY c.category,c.name COLLATE NOCASE')->fetchAll();
if($clubId==='')$clubId=(string)($clubs[0]['id']??'');
$club=null;foreach($clubs as $row){if($row['id']===$clubId){$club=$row;break;}}
$shows=[];$show=null;$showId=(int)($_GET['show']??$_POST['show_id']??0);
if($club){
    $stmt=db()->prepare('SELECT * FROM comedy_shows WHERE club_id=? ORDER BY show_date DESC,id DESC');$stmt->execute([$clubId]);
    $shows=$stmt->fetchAll();
    if($showId===0)$showId=(int)($shows[0]['id']??0);
    foreach($shows as $row){if((int)$row['id']===$showId){$show=$row;break;}}
}
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>Comedy-Redaktion · wiedeck.de</title>
<link rel="stylesheet" href="admin.css?v=5"><script defer src="comedy-ai.js?v=1"></script></head><body>
<header class="bar"><a href="index.php">← Restaurant-Redaktion</a><strong>MW / Comedy-Redaktion</strong><a href="../comedy.html">Comedy-Seite ↗</a></header>
<main class="shell"><div class="editor-title"><div><span class="eyebrow">GEMEINSAME REDAKTION</span><h2>Meine Comedy-Clubs</h2><p>Clubs und Auftritte zählen – Comedians erfassen, nicht bewerten. Kein Bericht ist Pflicht.</p></div></div>
<?php if($error):?><p class="alert" role="alert"><?=esc($error)?></p><?php endif;?>
<?php if(isset($_GET['saved'])):?><p class="success">Änderungen gespeichert.</p><?php endif;?>
<div class="dashboard"><aside class="sidebar"><h1>Comedy-Locations</h1><p><?=count($clubs)?> Clubs und Bühnen</p>
<form method="post" class="add"><input type="hidden" name="csrf" value="<?=esc(csrf())?>">
<input name="name" placeholder="Neuer Club" required><input name="city" placeholder="Stadt" required>
<select name="category"><option>Clubs &amp; kleine Bühnen</option><option>Andere Bühnen &amp; besondere Orte</option></select>
<button name="action" value="new_club">+ Club hinzufügen</button></form>
<nav class="restaurant-list"><?php foreach($clubs as $c):?><a class="<?=$clubId===$c['id']?'active':''?>" href="?club=<?=rawurlencode($c['id'])?>">
<strong><?=esc($c['name'])?></strong><small><?=esc($c['city'])?> · <?=$c['shows']?> Besuche · <?=$c['published']?> freigegeben</small></a><?php endforeach;?></nav>
<a class="backup" href="index.php?action=backup">Restaurant-Datensicherung ↗</a>
</aside><section class="editor"><?php if($club):?>
<div class="editor-title"><div><span class="eyebrow">CLUB-STAMMDATEN</span><h2><?=esc($club['name'])?></h2><p><?=esc($club['city'])?></p></div></div>
<form method="post" class="editor-form"><input type="hidden" name="csrf" value="<?=esc(csrf())?>">
<input type="hidden" name="club_id" value="<?=esc($clubId)?>">
<fieldset><legend>Location</legend><div class="two">
<label class="field"><span>Name</span><input name="name" value="<?=esc($club['name'])?>" required></label>
<label class="field"><span>Stadt</span><input name="city" value="<?=esc($club['city'])?>" required></label>
<label class="field"><span>Kategorie</span><select name="category"><?php foreach(['Clubs & kleine Bühnen','Andere Bühnen & besondere Orte'] as $cat):?><option value="<?=esc($cat)?>" <?=$club['category']===$cat?'selected':''?>><?=esc($cat)?></option><?php endforeach;?></select></label>
<label class="field"><span>Website (https://)</span><input name="website" value="<?=esc($club['website'])?>"></label>
</div><button class="primary" name="action" value="save_club">Club speichern</button></fieldset>
</form>
<div class="comedy-show-head"><div><h3>Besuche &amp; Shows</h3><p>Jeder Eintrag zählt als ein Besuch. Comedians bitte einzeln pro Zeile erfassen – auch wenn du keinen Bericht schreiben willst.</p></div>
<form method="post"><input type="hidden" name="csrf" value="<?=esc(csrf())?>"><input type="hidden" name="club_id" value="<?=esc($clubId)?>">
<button class="primary" name="action" value="new_show">+ Besuch zählen</button></form></div>
<?php if($shows):?><nav class="comedy-show-list"><?php foreach($shows as $s):?><a class="<?=$showId===(int)$s['id']?'active':''?>" href="?club=<?=rawurlencode($clubId)?>&show=<?=$s['id']?>">
<?=esc($s['show_date']?:'Datum offen')?> · <?=esc($s['title']?:'Auftritt #'.$s['id'])?>
<small><?=$s['status']==='published'?'Öffentlich':'Privat'?> <?=$s['report_text']!==''?'· mit Bericht':''?></small></a><?php endforeach;?></nav><?php endif;?>
<?php if($show):?>
<form method="post" class="editor-form" id="comedy-editor-form">
<input type="hidden" name="csrf" value="<?=esc(csrf())?>">
<input type="hidden" name="club_id" value="<?=esc($clubId)?>">
<input type="hidden" name="show_id" value="<?=$showId?>">
<input type="hidden" name="action" value="save_show">
<fieldset><legend>Show #<?=$showId?></legend>
<div class="two"><label class="field"><span>Datum</span><input name="show_date" type="date" value="<?=esc($show['show_date'])?>"></label>
<label class="field"><span>Show / Veranstaltungsreihe (optional)</span><input name="title" value="<?=esc($show['title'])?>"></label></div>
<label class="field"><span>Comedians (ein Name pro Zeile)</span><textarea name="comedians" rows="5" placeholder="Name 1&#10;Name 2&#10;Name 3"><?=esc($show['comedians'])?></textarea></label>
<label class="field"><span>Private Notizen (nicht öffentlich)</span><textarea name="notes" rows="3"><?=esc($show['notes'])?></textarea></label></fieldset>
<fieldset class="ai-fieldset"><legend>Optionaler Bericht mit KI</legend>
<p class="help">Du kannst nur die Show und die Namen eintragen. Ein Bericht ist freiwillig und bewertet keine Comedians.</p>
<label class="field"><span>Eigene Stichpunkte für den KI-Entwurf</span><textarea name="ai_notes" maxlength="6000" rows="4" placeholder="Wie war der Abend, die Stimmung, die Location?"><?=esc($show['ai_notes'])?></textarea></label>
<div class="ai-actions"><button class="primary" type="button" id="comedy-ai-generate">Bericht aus Stichpunkten erstellen</button><span id="comedy-ai-status" role="status" aria-live="polite"></span></div>
<div id="comedy-ai-preview" class="ai-preview" hidden><h3>Vorschlag vor der Übernahme prüfen</h3><p id="comedy-ai-text"></p><button class="primary" type="button" id="comedy-ai-apply">Entwurf übernehmen</button></div>
<label class="field"><span>Mein persönlicher Bericht (freiwillig)</span><textarea name="report_text" rows="7" maxlength="12000"><?=esc($show['report_text'])?></textarea></label>
<p class="help">Die KI erhält nur die bewusst eingegebenen Stichpunkte sowie Club und Stadt. Das Generieren veröffentlicht nichts.</p></fieldset>
<div class="publish"><label class="field"><span>Sichtbarkeit</span><select name="status">
<option value="draft" <?=$show['status']==='draft'?'selected':''?>>Privat – nur für mich zählen</option>
<option value="published" <?=$show['status']==='published'?'selected':''?>>Öffentlich – Show und Comedians zählen</option>
</select></label><button class="primary" type="submit">Besuch speichern</button></div>
<p class="help">Mit öffentlicher Freigabe erscheint der Besuch in den Zählern und die Besetzung in der Detailansicht. Eine große Berichtskarte oben gibt es nur, wenn zusätzlich ein persönlicher Bericht vorhanden ist.</p>
</form>
<details class="danger-zone"><summary>Diesen Besuch löschen</summary><form method="post">
<input type="hidden" name="csrf" value="<?=esc(csrf())?>"><input type="hidden" name="club_id" value="<?=esc($clubId)?>">
<input type="hidden" name="show_id" value="<?=$showId?>"><button class="danger" name="action" value="delete_show">Besuch endgültig löschen</button></form></details>
<?php endif;?>
<details class="danger-zone"><summary>Club samt allen Besuchen löschen</summary><p>Diese Aktion löscht den Club und alle zugehörigen Shows dauerhaft.</p>
<form method="post"><input type="hidden" name="csrf" value="<?=esc(csrf())?>"><input type="hidden" name="club_id" value="<?=esc($clubId)?>">
<button class="danger" name="action" value="delete_club">Club endgültig löschen</button></form></details>
<?php endif;?></section></div></main></body></html>
