<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$error='';$setup=!admins_exist();
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    if(blocked()){$error='Zu viele Versuche. Bitte 15 Minuten warten.';http_response_code(429);}
    elseif($setup && ($_POST['action']??'')==='setup'){
        $keyFile=__DIR__.'/private/setup-key.php';
        $key=is_file($keyFile)?require $keyFile:'';
        $provided=(string)($_POST['setup_token']??'');
        $password=(string)($_POST['password']??'');
        if(!is_string($key)||strlen($key)<24||!hash_equals($key,$provided)||strlen($password)<12){
            register_failure();$error='Ungültiger Einrichtungsschlüssel oder Passwort zu kurz (mindestens 12 Zeichen).';
        }else{
            db()->prepare('INSERT INTO admin(id,password_hash) VALUES(1,?)')->execute([password_hash($password,PASSWORD_DEFAULT)]);
            @unlink($keyFile);session_regenerate_id(true);$_SESSION['admin']=true;$_SESSION['active_at']=time();clear_failures();redirect('index.php');
        }
    }elseif(!$setup && ($_POST['action']??'')==='login'){
        $hash=(string)db()->query('SELECT password_hash FROM admin WHERE id=1')->fetchColumn();
        if(!password_verify((string)($_POST['password']??''),$hash)){
            register_failure();usleep(300000);$error='Passwort nicht korrekt.';
        }else{session_regenerate_id(true);$_SESSION['admin']=true;$_SESSION['active_at']=time();clear_failures();redirect('index.php');}
    }elseif(authenticated()){
        $action=(string)($_POST['action']??'');
        if($action==='logout'){$_SESSION=[];session_regenerate_id(true);redirect('index.php');}
        if($action==='password'){
            $hash=(string)db()->query('SELECT password_hash FROM admin WHERE id=1')->fetchColumn();
            $new=(string)($_POST['new_password']??'');
            if(!password_verify((string)($_POST['current_password']??''),$hash)||strlen($new)<12)$error='Passwort nicht geändert: altes Passwort falsch oder neues zu kurz.';
            else{db()->prepare('UPDATE admin SET password_hash=? WHERE id=1')->execute([password_hash($new,PASSWORD_DEFAULT)]);session_regenerate_id(true);redirect('index.php?changed=1');}
        }
        if($action==='new'){
            $name=trim((string)($_POST['name']??''));$city=trim((string)($_POST['city']??''));
            if($name===''||$city==='')$error='Bitte Restaurantname und Stadt angeben.';
            else{$id='own-'.bin2hex(random_bytes(8));db()->prepare('INSERT INTO restaurants(id,name,city) VALUES(?,?,?)')->execute([$id,mb_substr($name,0,140),mb_substr($city,0,100)]);redirect('index.php?id='.rawurlencode($id));}
        }
        if($action==='save'){
            $id=(string)($_POST['id']??'');
            $exists=db()->prepare('SELECT id FROM restaurants WHERE id=?');$exists->execute([$id]);
            if(!$exists->fetch()){$error='Restaurant nicht gefunden.';http_response_code(404);}
            else{
                $name=mb_substr(trim((string)($_POST['name']??'')),0,140);
                $city=mb_substr(trim((string)($_POST['city']??'')),0,100);
                $address=mb_substr(trim((string)($_POST['address']??'')),0,320);
                $cuisine=mb_substr(trim((string)($_POST['cuisine']??'')),0,320);
                $website=mb_substr(trim((string)($_POST['website']??'')),0,320);
                if($name===''||$city==='')$error='Restaurantname und Stadt sind Pflicht.';
                elseif($website!=='' && (!filter_var($website,FILTER_VALIDATE_URL)||!str_starts_with($website,'https://')))$error='Bitte gültige HTTPS-Adresse eingeben.';
                else{
                    db()->prepare('UPDATE restaurants SET name=?,city=?,address=?,cuisine=?,website=?,updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$name,$city,$address,$cuisine,$website,$id]);
                    redirect('index.php?id='.rawurlencode($id).'&saved=1');
                }
            }
        }
        if($action==='delete'){
            $id=(string)($_POST['id']??'');
            $p=db()->prepare('SELECT filename FROM photos WHERE restaurant_id=?');$p->execute([$id]);
            foreach($p->fetchAll(PDO::FETCH_COLUMN) as $filename)@unlink(__DIR__.'/private/images/'.$filename);
            db()->prepare('DELETE FROM restaurants WHERE id=?')->execute([$id]);redirect('index.php');
        }
    }
}
$ready=authenticated();
if($ready && ($_GET['action']??'')==='backup'){
    $all=db()->query('SELECT * FROM restaurants ORDER BY city,name')->fetchAll();
    $visits=db()->query('SELECT * FROM visits ORDER BY restaurant_id,visit_date,id')->fetchAll();
    $photos=db()->query('SELECT restaurant_id,visit_id,filename,mime,caption FROM photos')->fetchAll();
    header('Content-Disposition: attachment; filename="restaurants-'.date('Y-m-d').'.json"');
    json_output(['exported_at'=>gmdate(DATE_ATOM),'restaurants'=>$all,'visits'=>$visits,'photos_metadata'=>$photos,
        'note'=>'Die Bilddateien selbst sind nicht in diesem Export enthalten; Server-Backup erforderlich.']);
}
$items=[];$selected=null;$visitCounts=[];
if($ready){
    $items=db()->query('SELECT id,name,city,address,website,cuisine FROM restaurants ORDER BY city COLLATE NOCASE,name COLLATE NOCASE')->fetchAll();
    $id=(string)($_GET['id']??($items[0]['id']??''));
    $stmt=db()->prepare('SELECT * FROM restaurants WHERE id=?');$stmt->execute([$id]);$selected=$stmt->fetch()?:null;
    $visitCounts=db()->query('SELECT restaurant_id,COUNT(*) AS amount,SUM(CASE WHEN status="published" THEN 1 ELSE 0 END) AS online FROM visits GROUP BY restaurant_id')->fetchAll(PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
}
?><!doctype html><html lang="de" data-theme="dark"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Restaurant-Redaktion · wiedeck.de</title><link rel="stylesheet" href="admin.css?v=3"></head><body>
<header class="bar"><a href="../restaurants.html">← Restaurants</a><strong>MW / Restaurant-Redaktion</strong><?php if($ready):?><form method="post"><input type="hidden" name="csrf" value="<?=esc(csrf())?>"><button name="action" value="logout">Abmelden</button></form><?php endif;?></header>
<main class="shell"><?php if($error):?><p class="alert" role="alert"><?=esc($error)?></p><?php endif;?>
<?php if(!$ready):?><section class="login"><h1><?=$setup?'Ersteinrichtung':'Anmelden'?></h1>
<?php if($setup && !is_file(__DIR__.'/private/setup-key.php')):?>
<p>Einrichtungsschlüssel fehlt noch. In GitHub Actions das Repository-Secret <code>REDAKTION_SETUP_TOKEN</code> (mindestens 24 Zeichen) anlegen und den Live-Upload starten.</p>
<?php else:?><form method="post"><input type="hidden" name="csrf" value="<?=esc(csrf())?>"><input type="hidden" name="action" value="<?=$setup?'setup':'login'?>">
<?php if($setup):?><label>Einrichtungsschlüssel<input type="password" name="setup_token" required autocomplete="off"></label><?php endif;?>
<label><?=$setup?'Neues Admin-Passwort (mind. 12 Zeichen)':'Passwort'?><input type="password" name="password" required minlength="<?=$setup?'12':'1'?>" autocomplete="<?=$setup?'new-password':'current-password'?>"></label><button class="primary"><?=$setup?'Adminzugang erstellen':'Anmelden'?></button></form><?php endif;?></section>
<?php else:?>
<?php if(isset($_GET['changed'])):?><p class="success">Passwort geändert.</p><?php endif;?>
<?php if(isset($_GET['saved'])):?><p class="success">Restaurant-Stammdaten gespeichert.</p><?php endif;?>
<div class="dashboard"><aside class="sidebar"><h1>Meine Restaurants</h1><p><?=count($items)?> Lokale · beliebig viele Besuche pro Lokal</p>
<form method="post" class="add"><input type="hidden" name="csrf" value="<?=esc(csrf())?>"><input type="hidden" name="action" value="new"><input name="name" placeholder="Neues Restaurant" required><input name="city" placeholder="Stadt" required><button>+ Neues Restaurant</button></form>
<nav class="restaurant-list"><?php foreach($items as $item):$counts=$visitCounts[$item['id']]??[];?><a href="?id=<?=rawurlencode($item['id'])?>" class="<?=$id===$item['id']?'active':''?>"><strong><?=esc($item['name'])?></strong><small><?=esc($item['city'])?> · <?=((int)($counts['amount']??0))?> Besuche, <?=((int)($counts['online']??0))?> online</small></a><?php endforeach;?></nav>
<a class="backup" href="?action=backup">Stammdaten + Berichte als JSON sichern ↓</a>
<details class="password"><summary>Passwort ändern</summary><form method="post"><input type="hidden" name="csrf" value="<?=esc(csrf())?>"><input type="hidden" name="action" value="password"><input name="current_password" type="password" placeholder="Aktuelles Passwort" required><input name="new_password" type="password" minlength="12" placeholder="Neues Passwort" required><button>Ändern</button></form></details>
</aside><section class="editor"><?php if($selected):?>
<div class="editor-title"><div><span class="eyebrow">RESTAURANT-STAMMDATEN</span><h2><?=esc($selected['name'])?></h2><p><?=esc($selected['city'])?></p></div></div>
<div class="venue-visit-link"><div><strong>Besuchsberichte verwalten</strong><p>Für jeden Besuch ein eigener Text mit Datum, Fotos und 0–10 Sternen. Bestehende Besuche bleiben erhalten.</p></div><a class="primary" href="visits.php?restaurant=<?=rawurlencode($selected['id'])?>">Besuche öffnen ↗</a></div>
<form method="post" class="editor-form"><input type="hidden" name="csrf" value="<?=esc(csrf())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=esc($selected['id'])?>">
<fieldset><legend>Allgemeine Angaben – gelten für alle Besuche</legend>
<div class="two"><?php foreach(['name'=>'Name','city'=>'Stadt','address'=>'Adresse','cuisine'=>'Küchenrichtung','website'=>'Restaurantwebsite (https://)'] as $k=>$label):?><label class="field"><span><?=$label?></span><input name="<?=$k?>" value="<?=esc($selected[$k])?>" <?=$k==='name'||$k==='city'?'required':''?>></label><?php endforeach;?></div>
<button class="primary">Stammdaten speichern</button></fieldset></form>
<details class="danger-zone"><summary>Restaurant samt aller Besuche löschen</summary><p>Achtung: Das Restaurant, sämtliche Besuchsberichte und Fotos werden dauerhaft gelöscht.</p><form method="post"><input type="hidden" name="csrf" value="<?=esc(csrf())?>"><input type="hidden" name="id" value="<?=esc($selected['id'])?>"><button class="danger" name="action" value="delete">Endgültig löschen</button></form></details>
<?php else:?><p>Bitte Restaurant auswählen oder neu anlegen.</p><?php endif;?></section></div>
<?php endif;?></main></body></html>
