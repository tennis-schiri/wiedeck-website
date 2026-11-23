<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
guard();
$restaurantId=(string)($_GET['restaurant'] ?? $_POST['restaurant_id'] ?? '');
$stmt=db()->prepare('SELECT id,name,city FROM restaurants WHERE id=?');$stmt->execute([$restaurantId]);$restaurant=$stmt->fetch();
if(!$restaurant){http_response_code(404);exit('Restaurant nicht gefunden.');}
$error='';
$submittedVisit=null; // Preserve all entered values after validation or photo-upload errors.
$fields=['visit_date','occasion','companions','price','impression','food_text','ambience_text','service_text',
         'value_text','highlights','shortcomings','verdict','revisit'];
$ratingFields=['score_food','score_ambience','score_service','score_value'];
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $action=(string)($_POST['action'] ?? '');
    $visitId=(int)($_POST['visit_id'] ?? 0);
    $find=db()->prepare('SELECT * FROM visits WHERE id=? AND restaurant_id=?');$find->execute([$visitId,$restaurantId]);
    $existing=$visitId>0?$find->fetch():null;
    if($action==='new'){
        db()->prepare('INSERT INTO visits(restaurant_id) VALUES(?)')->execute([$restaurantId]);
        redirect('visits.php?restaurant='.rawurlencode($restaurantId).'&visit='.db()->lastInsertId());
    }
    if($action==='delete'){
        if(!$existing){http_response_code(404);exit('Besuch nicht gefunden.');}
        $photos=db()->prepare('SELECT filename FROM photos WHERE visit_id=? AND restaurant_id=?');$photos->execute([$visitId,$restaurantId]);
        foreach($photos->fetchAll(PDO::FETCH_COLUMN) as $name) @unlink(__DIR__.'/private/images/'.$name);
        // Delete photos first for databases with an old table without visit-level cascade.
        db()->prepare('DELETE FROM photos WHERE visit_id=? AND restaurant_id=?')->execute([$visitId,$restaurantId]);
        db()->prepare('DELETE FROM visits WHERE id=? AND restaurant_id=?')->execute([$visitId,$restaurantId]);
        redirect('visits.php?restaurant='.rawurlencode($restaurantId));
    }
    if($action==='delete_photo'){
        if(!$existing){http_response_code(404);exit('Besuch nicht gefunden.');}
        $pid=(int)($_POST['photo_id']??0);
        $stmt=db()->prepare('SELECT filename FROM photos WHERE id=? AND visit_id=? AND restaurant_id=?');
        $stmt->execute([$pid,$visitId,$restaurantId]);$filename=$stmt->fetchColumn();
        if($filename){
            db()->prepare('DELETE FROM photos WHERE id=?')->execute([$pid]);
            @unlink(__DIR__.'/private/images/'.$filename);
        }
        redirect('visits.php?restaurant='.rawurlencode($restaurantId).'&visit='.$visitId);
    }
    if($action==='save'){
        if(!$existing){http_response_code(404);exit('Besuch nicht gefunden.');}
        $data=[];
        foreach($fields as $field){
            $v=trim((string)($_POST[$field]??''));
            $data[$field]=mb_substr($v,0,in_array($field,['impression','food_text','ambience_text','service_text','value_text','highlights','shortcomings','verdict'],true)?12000:320);
        }
        foreach($ratingFields as $field)$data[$field]=rating($_POST[$field]??'');
        $status=(string)($_POST['status']??'draft')==='published'?'published':'draft';
        // Keep the submitted text, ratings, selected visibility and photo caption,
        // even if validation or the separate file upload fails.
        $submittedVisit=$data;
        $submittedVisit['status']=$status;
        $submittedVisit['photo_caption']=mb_substr(trim((string)($_POST['photo_caption']??'')),0,200);
        if($data['visit_date']==='')$error='Bitte nur das Besuchsdatum eintragen. Alle übrigen Felder sind freiwillig; deine bisherigen Eingaben bleiben erhalten.';
        elseif(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$data['visit_date']) || !checkdate((int)substr($data['visit_date'],5,2),(int)substr($data['visit_date'],8,2),(int)substr($data['visit_date'],0,4)))$error='Besuchsdatum ungültig. Alle bisherigen Eingaben bleiben erhalten.';
        if($error===''){
            $cols=array_keys($data);$assign=implode(',',array_map(fn($c)=>$c.'=?',$cols));
            $publishedAt=$status==='published'?($existing['published_at']?:gmdate('Y-m-d H:i:s')):null;
            $args=array_values($data);array_push($args,$status,$publishedAt,$visitId,$restaurantId);
            db()->prepare('UPDATE visits SET '.$assign.',status=?,published_at=?,updated_at=CURRENT_TIMESTAMP WHERE id=? AND restaurant_id=?')->execute($args);
            if(!empty($_FILES['photo']['name'])){
                $file=$_FILES['photo'];
                if((int)($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || (int)($file['size']??0)>8*1024*1024 || (int)($file['size']??0)<100){
                    $error='Foto konnte nicht hochgeladen werden (maximal 8 MB). Der Text wurde gespeichert.';
                }else{
                    $mime=(new finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']);
                    $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??'';
                    $info=@getimagesize((string)$file['tmp_name']);
                    if($ext===''||!$info||$info[0]>9000||$info[1]>9000||$info[0]<1||$info[1]<1){
                        $error='Nur gültige JPG-, PNG- und WebP-Fotos zulässig. Der Text wurde gespeichert.';
                    }else{
                        $dir=__DIR__.'/private/images';
                        if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))$error='Upload-Verzeichnis nicht verfügbar.';
                        else{
                            $filename=bin2hex(random_bytes(20)).'.'.$ext;
                            if(!move_uploaded_file((string)$file['tmp_name'],$dir.'/'.$filename))$error='Bildspeicherung fehlgeschlagen.';
                            else{
                                @chmod($dir.'/'.$filename,0600);
                                db()->prepare('INSERT INTO photos(restaurant_id,visit_id,filename,mime,caption) VALUES(?,?,?,?,?)')
                                  ->execute([$restaurantId,$visitId,$filename,$mime,mb_substr(trim((string)($_POST['photo_caption']??'')),0,200)]);
                            }
                        }
                    }
                }
            }
            if($error==='')redirect('visits.php?restaurant='.rawurlencode($restaurantId).'&visit='.$visitId.'&saved=1');
        }
    }
}
$all=db()->prepare('SELECT id,visit_date,status,updated_at FROM visits WHERE restaurant_id=? ORDER BY visit_date DESC,id DESC');
$all->execute([$restaurantId]);$visits=$all->fetchAll();
$visitId=(int)($_GET['visit']??$_POST['visit_id']??($visits[0]['id']??0));
$find=db()->prepare('SELECT * FROM visits WHERE id=? AND restaurant_id=?');$find->execute([$visitId,$restaurantId]);
$v=$find->fetch()?:null;
if($v && $submittedVisit!==null && $error!=='' && (int)($_POST['visit_id']??0)===$visitId){
    $v=array_replace($v,$submittedVisit); // Re-render POST values, not the stale DB record.
}
$photos=[];
if($v){$stmt=db()->prepare('SELECT id,caption,filename FROM photos WHERE visit_id=? AND restaurant_id=? ORDER BY id DESC');$stmt->execute([$visitId,$restaurantId]);$photos=$stmt->fetchAll();}
function input_visit(string $name,string $label,?array $v,string $type='text'):void{
    echo '<label class="field"><span>'.esc($label).'</span>';
    $value=esc((string)($v[$name]??''));
    if($type==='textarea')echo '<textarea rows="4" name="'.esc($name).'">'.$value.'</textarea>';
    else echo '<input type="'.esc($type).'" name="'.esc($name).'" value="'.$value.'"'.($name==='visit_date'?' required aria-required="true"':'').'>';
    echo '</label>';
}
?><!doctype html><html lang="de" data-theme="dark"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Besuche · <?=esc($restaurant['name'])?></title><link rel="stylesheet" href="admin.css?v=6"><script defer src="visits-draft.js?v=1"></script></head><body>
<header class="bar"><a href="index.php?id=<?=rawurlencode($restaurantId)?>">← Restaurant-Stammdaten</a><strong>MW / Besuche</strong><a href="../restaurants.html">Zur Website ↗</a></header>
<main class="shell"><div class="editor-title"><div><span class="eyebrow">MEHRFACHE BESUCHE</span><h2><?=esc($restaurant['name'])?></h2><p><?=esc($restaurant['city'])?></p></div></div>
<?php if($error):?><p class="alert" role="alert"><?=esc($error)?></p><?php endif;?>
<?php if(isset($_GET['saved'])):?><p class="success">Besuch gespeichert.</p><?php endif;?>
<div class="dashboard"><aside class="sidebar"><h1>Meine Besuche</h1><p><?=count($visits)?> Einträge</p>
<form method="post"><input type="hidden" name="csrf" value="<?=esc(csrf())?>"><input type="hidden" name="restaurant_id" value="<?=esc($restaurantId)?>"><button class="primary" name="action" value="new">+ Weiteren Besuch anlegen</button></form>
<nav class="restaurant-list"><?php foreach($visits as $visit): ?><a class="<?=$visitId===(int)$visit['id']?'active':''?>" href="?restaurant=<?=rawurlencode($restaurantId)?>&visit=<?=$visit['id']?>"><strong><?=esc($visit['visit_date']?:'Datum noch offen')?> · #<?=$visit['id']?></strong><small><?=$visit['status']==='published'?'Bericht online':'Entwurf'?></small></a><?php endforeach;?></nav></aside>
<section class="editor"><?php if(!$v):?><p>Für dieses Restaurant gibt es noch keine Besuchsberichte. Lege deinen ersten Besuch an.</p><?php else:?>
<div class="editor-title"><div><span class="eyebrow">BESUCH #<?=$visitId?></span><h2><?=esc($v['visit_date']?:'Neuer Besuch')?></h2></div><span class="status"><?=$v['status']==='published'?'Veröffentlicht':'Privater Entwurf'?></span></div>
<form class="editor-form" id="restaurant-visit-form" method="post" enctype="multipart/form-data" data-validation-error="<?=$error!==''?'1':'0'?>">
<input type="hidden" name="csrf" value="<?=esc(csrf())?>"><input type="hidden" name="restaurant_id" value="<?=esc($restaurantId)?>"><input type="hidden" name="visit_id" value="<?=$visitId?>"><input type="hidden" name="action" value="save">
<fieldset><legend>Dieser Besuch</legend><div class="two"><?php input_visit('visit_date','Besuchsdatum',$v,'date');input_visit('occasion','Anlass',$v);input_visit('companions','Begleitung',$v);input_visit('price','Ausgaben pro Person',$v);?></div></fieldset>
<fieldset><legend>Meine Eindrücke</legend><?php input_visit('impression','Erster Eindruck',$v,'textarea');input_visit('food_text','Essen & Trinken',$v,'textarea');input_visit('ambience_text','Atmosphäre',$v,'textarea');input_visit('service_text','Service',$v,'textarea');input_visit('value_text','Preis-Leistung',$v,'textarea');input_visit('highlights','Was besonders gut war',$v,'textarea');input_visit('shortcomings','Was mich gestört hat',$v,'textarea');input_visit('verdict','Mein Fazit',$v,'textarea');?><label class="field"><span>Würde ich wiederkommen?</span><select name="revisit"><option value="">Noch offen</option><?php foreach(['Ja','Vielleicht','Nein'] as $answer):?><option value="<?=$answer?>" <?=$v['revisit']===$answer?'selected':''?>><?=$answer?></option><?php endforeach;?></select></label></fieldset>
<fieldset><legend>0–10 Sterne je Kategorie</legend><p class="help">Nicht bewertete Kategorien bleiben leer. Jede Bewertung gilt nur für diesen Besuch.</p><div class="two"><?php foreach(['score_food'=>'Essen & Trinken','score_ambience'=>'Atmosphäre','score_service'=>'Service','score_value'=>'Preis-Leistung'] as $key=>$label):?><label class="field"><span><?=$label?></span><select name="<?=$key?>"><option value="">Nicht bewertet</option><?php for($score=0;$score<=10;$score++):?><option value="<?=$score?>" <?=$v[$key]!==null&&(int)$v[$key]===$score?'selected':''?>><?=$score?> / 10 ★</option><?php endfor;?></select></label><?php endforeach;?></div></fieldset>
<fieldset><legend>Fotos dieses Besuchs</legend><?php input_visit('photo_caption','Bildunterschrift',$v);?><label class="field"><span>Bild auswählen (JPG, PNG, WebP bis 8 MB)</span><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label><?php if($photos):?><div class="photo-grid"><?php foreach($photos as $p):?><figure><img loading="lazy" src="media.php?id=<?=$p['id']?>" alt="<?=esc($p['caption'])?>"><figcaption><?=esc($p['caption'])?></figcaption></figure><?php endforeach;?></div><?php endif;?></fieldset>
<div class="publish"><label class="field"><span>Sichtbarkeit</span><select name="status"><option value="draft" <?=$v['status']==='draft'?'selected':''?>>Entwurf – privat</option><option value="published" <?=$v['status']==='published'?'selected':''?>>Bericht veröffentlichen</option></select></label><button class="primary" type="submit">Besuch speichern</button></div>
<p class="help">Nur das Besuchsdatum ist Pflicht. Alles andere – Texte, Bewertungen, Fotos und Fazit – ist freiwillig. Ohne deine ausdrückliche Freigabe bleibt der Besuch privat.</p></form>
<?php if($photos):?><div class="danger-zone"><h3>Bilder einzeln löschen</h3><?php foreach($photos as $p):?><form method="post"><input type="hidden" name="csrf" value="<?=esc(csrf())?>"><input type="hidden" name="restaurant_id" value="<?=esc($restaurantId)?>"><input type="hidden" name="visit_id" value="<?=$visitId?>"><input type="hidden" name="photo_id" value="<?=$p['id']?>"><button name="action" value="delete_photo">Foto löschen: <?=esc($p['caption']?:$p['filename'])?></button></form><?php endforeach;?></div><?php endif;?>
<details class="danger-zone"><summary>Besuch löschen</summary><p>Der Besuch und die zugehörigen Fotos werden dauerhaft gelöscht.</p><form method="post" onsubmit="return confirm('Diesen Besuch endgültig löschen?')"><input type="hidden" name="csrf" value="<?=esc(csrf())?>"><input type="hidden" name="restaurant_id" value="<?=esc($restaurantId)?>"><input type="hidden" name="visit_id" value="<?=$visitId?>"><button class="danger" name="action" value="delete">Besuch endgültig löschen</button></form></details>
<?php endif;?></section></div></main></body></html>
