<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
$error = ''; $message = '';
$setup = !admins_exist();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (blocked()) { http_response_code(429); $error = 'Zu viele Versuche. Bitte in 15 Minuten erneut versuchen.'; }
    elseif ($setup && ($_POST['action'] ?? '') === 'setup') {
        $keyFile = __DIR__ . '/private/setup-key.php';
        $key = is_file($keyFile) ? require $keyFile : '';
        $provided = (string)($_POST['setup_token'] ?? '');
        $pass = (string)($_POST['password'] ?? '');
        if (!is_string($key) || strlen($key) < 24 || !hash_equals($key,$provided) || strlen($pass)<12) {
            register_failure();
            $error = 'Einrichtungsschlüssel ungültig oder Passwort zu kurz (mindestens 12 Zeichen).';
        } else {
            $stmt = db()->prepare('INSERT INTO admin(id,password_hash) VALUES(1,?)');
            $stmt->execute([password_hash($pass, PASSWORD_DEFAULT)]);
            @unlink($keyFile);
            session_regenerate_id(true); $_SESSION['admin']=true; $_SESSION['active_at']=time(); clear_failures();
            redirect('index.php');
        }
    } elseif (!$setup && ($_POST['action'] ?? '') === 'login') {
        $hash = (string)db()->query('SELECT password_hash FROM admin WHERE id=1')->fetchColumn();
        if (!password_verify((string)($_POST['password'] ?? ''),$hash)) {
            register_failure(); usleep(300000); $error='Passwort nicht korrekt.';
        } else {
            session_regenerate_id(true); $_SESSION['admin']=true; $_SESSION['active_at']=time();
            clear_failures(); redirect('index.php');
        }
    } elseif (authenticated()) {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'logout') { $_SESSION=[]; session_regenerate_id(true); redirect('index.php'); }
        if ($action === 'new') {
            $name = trim((string)($_POST['name'] ?? '')); $city = trim((string)($_POST['city'] ?? ''));
            if ($name === '' || $city === '') $error='Restaurant und Stadt angeben.';
            else {
                $id = 'own-' . bin2hex(random_bytes(8));
                db()->prepare('INSERT INTO restaurants(id,name,city) VALUES(?,?,?)')->execute([$id,mb_substr($name,0,140),mb_substr($city,0,100)]);
                redirect('index.php?id='.rawurlencode($id));
            }
        }
        if ($action === 'password') {
            $current = (string)($_POST['current_password'] ?? '');
            $new = (string)($_POST['new_password'] ?? '');
            $hash = (string)db()->query('SELECT password_hash FROM admin WHERE id=1')->fetchColumn();
            if (!password_verify($current,$hash) || strlen($new)<12) $error='Altes Passwort falsch oder neues Passwort zu kurz.';
            else {
                db()->prepare('UPDATE admin SET password_hash=? WHERE id=1')->execute([password_hash($new,PASSWORD_DEFAULT)]);
                session_regenerate_id(true); $message='Passwort geändert.';
            }
        }
        if ($action === 'save') {
            $id=(string)($_POST['id'] ?? '');
            $stmt=db()->prepare('SELECT * FROM restaurants WHERE id=?');$stmt->execute([$id]);$existing=$stmt->fetch();
            if (!$existing) {http_response_code(404);$error='Restaurant nicht gefunden.';}
            else {
                $fields=['name','city','address','website','cuisine','visit_date','occasion','companions','price',
                    'impression','food_text','ambience_text','service_text','value_text','highlights','shortcomings','verdict','revisit'];
                $data=[];foreach($fields as $field){ $v=trim((string)($_POST[$field] ?? ''));$data[$field]=mb_substr($v,0,in_array($field,['impression','food_text','ambience_text','service_text','value_text','highlights','shortcomings','verdict'],true)?12000:320); }
                $status=($_POST['status'] ?? '')==='published'?'published':'draft';
                if ($data['name']==='' || $data['city']==='') $error='Name und Stadt sind erforderlich.';
                elseif ($data['website']!=='' && (!filter_var($data['website'],FILTER_VALIDATE_URL) || !str_starts_with($data['website'],'https://'))) $error='Website bitte mit gültiger HTTPS-Adresse eingeben.';
                elseif ($data['visit_date']!=='' && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$data['visit_date'])) $error='Besuchsdatum ungültig.';
                elseif ($status==='published' && ($data['visit_date']==='' || $data['food_text']==='' || $data['verdict']==='')) $error='Zum Veröffentlichen bitte Besuchsdatum, Essen und Fazit ausfüllen.';
                else {
                    foreach(['food','ambience','service','value'] as $kind) $data['score_'.$kind]=rating($_POST['score_'.$kind] ?? '');
                    $cols=array_keys($data); $assign=implode(',',array_map(fn($c)=>$c.'=?',$cols));
                    $publishedAt=$status==='published'?($existing['published_at'] ?:gmdate('Y-m-d H:i:s')):null;
                    $sql='UPDATE restaurants SET '.$assign.',status=?,published_at=?,updated_at=CURRENT_TIMESTAMP WHERE id=?';
                    $values=array_values($data);array_push($values,$status,$publishedAt,$id);
                    db()->prepare($sql)->execute($values);
                    // Optional image: validated by size, MIME and decoded pixel dimensions.
                    if (!empty($_FILES['photo']['name'])) {
                        $file=$_FILES['photo']; $err=(int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
                        if ($err!==UPLOAD_ERR_OK || (int)$file['size']>8*1024*1024 || (int)$file['size']<100) $error='Foto konnte nicht hochgeladen werden (max. 8 MB).';
                        else {
                            $finfo=new finfo(FILEINFO_MIME_TYPE);$mime=$finfo->file((string)$file['tmp_name']);
                            $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime] ?? '';
                            $info=@getimagesize((string)$file['tmp_name']);
                            if ($ext==='' || !$info || $info[0]>9000 || $info[1]>9000) $error='Nur gültige JPG-, PNG- und WebP-Bilder sind erlaubt.';
                            else {
                                $dir=__DIR__.'/private/images';
                                if (!is_dir($dir) && !mkdir($dir,0700,true) && !is_dir($dir)) $error='Upload-Ordner nicht verfügbar.';
                                else {
                                    $filename=bin2hex(random_bytes(20)).'.'.$ext;
                                    if (!move_uploaded_file((string)$file['tmp_name'],$dir.'/'.$filename)) $error='Datei konnte nicht gespeichert werden.';
                                    else {
                                        @chmod($dir.'/'.$filename,0600);
                                        db()->prepare('INSERT INTO photos(restaurant_id,filename,mime,caption) VALUES(?,?,?,?)')->execute([$id,$filename,$mime,mb_substr(trim((string)($_POST['photo_caption'] ?? '')),0,200)]);
                                    }
                                }
                            }
                        }
                    }
                    if ($error==='') redirect('index.php?id='.rawurlencode($id).'&saved=1');
                }
            }
        }
        if ($action === 'delete_photo') {
            $photoId=(int)($_POST['photo_id'] ?? 0);$id=(string)($_POST['id'] ?? '');
            $stmt=db()->prepare('SELECT filename FROM photos WHERE id=? AND restaurant_id=?');$stmt->execute([$photoId,$id]);$filename=$stmt->fetchColumn();
            if ($filename) {db()->prepare('DELETE FROM photos WHERE id=?')->execute([$photoId]);@unlink(__DIR__.'/private/images/'.$filename);}
            redirect('index.php?id='.rawurlencode($id));
        }
        if ($action === 'delete_restaurant') {
            $id=(string)($_POST['id'] ?? '');
            $stmt=db()->prepare('SELECT filename FROM photos WHERE restaurant_id=?');$stmt->execute([$id]);
            foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $filename) @unlink(__DIR__.'/private/images/'.$filename);
            db()->prepare('DELETE FROM restaurants WHERE id=?')->execute([$id]);redirect('index.php');
        }
    }
}
if (authenticated() && ($_GET['action'] ?? '')==='backup') {
    $rows=db()->query('SELECT * FROM restaurants ORDER BY city,name')->fetchAll();
    $photos=db()->query('SELECT id,restaurant_id,filename,mime,caption FROM photos')->fetchAll();
    header('Content-Disposition: attachment; filename="restaurant-backup-'.date('Y-m-d').'.json"');
    json_output(['exported_at'=>gmdate(DATE_ATOM),'restaurants'=>$rows,'photos'=>$photos,
        'notice'=>'Fotos sind aus Sicherheitsgründen nicht in dieser JSON-Datei enthalten und müssen zusätzlich auf dem Server gesichert werden.']);
}
$ready=authenticated();
$selected=null;$photos=[];
if($ready){
 $items=db()->query('SELECT id,name,city,status,visit_date,updated_at FROM restaurants ORDER BY city COLLATE NOCASE,name COLLATE NOCASE')->fetchAll();
 $id=(string)($_GET['id'] ?? '');if($id==='') $id=(string)($items[0]['id'] ?? '');
 $stmt=db()->prepare('SELECT * FROM restaurants WHERE id=?');$stmt->execute([$id]);$selected=$stmt->fetch()?:null;
 if($selected){$stmt=db()->prepare('SELECT * FROM photos WHERE restaurant_id=? ORDER BY id DESC');$stmt->execute([$id]);$photos=$stmt->fetchAll();}
}
function field(string $key,string $label,array $row,string $type='text'): void {
 $val=esc((string)($row[$key] ?? ''));
 echo '<label class="field"><span>'.esc($label).'</span>';
 if($type==='textarea')echo '<textarea rows="4" name="'.esc($key).'">'.$val.'</textarea>';
 else echo '<input type="'.esc($type).'" name="'.esc($key).'" value="'.$val.'"'.($key==='name'||$key==='city'?' required':'').'>';
 echo '</label>';
}
?><!doctype html><html lang="de" data-theme="dark"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Redaktion · wiedeck.de</title><link rel="stylesheet" href="admin.css?v=1"></head>
<body><header class="bar"><a href="../restaurants.html">← Restaurants</a><strong>MW / Restaurant-Redaktion</strong><?php if($ready): ?><form method="post"><input type="hidden" name="csrf" value="<?=esc(csrf())?>"><button name="action" value="logout">Abmelden</button></form><?php endif; ?></header>
<main class="shell">
<?php if ($error): ?><p class="alert" role="alert"><?=esc($error)?></p><?php endif; ?>
<?php if ($message): ?><p class="success"><?=esc($message)?></p><?php endif; ?>
<?php if(!$ready): ?>
<section class="login"><h1><?=$setup?'Ersteinrichtung':'Anmelden'?></h1>
<?php if($setup && !is_file(__DIR__.'/private/setup-key.php')): ?><p>Der geschützte Einrichtungsschlüssel ist noch nicht auf dem Server eingerichtet. Bitte das GitHub-Actions-Secret <code>REDAKTION_SETUP_TOKEN</code> (mindestens 24 Zeichen) setzen und die Website erneut hochladen.</p><?php else: ?>
<form method="post"><input type="hidden" name="csrf" value="<?=esc(csrf())?>">
<input type="hidden" name="action" value="<?=$setup?'setup':'login'?>">
<?php if($setup): ?><label>Einrichtungsschlüssel<input name="setup_token" type="password" autocomplete="off" required></label><?php endif; ?>
<label><?=$setup?'Neues Admin-Passwort (mind. 12 Zeichen)':'Passwort'?><input name="password" type="password" minlength="<?=$setup?'12':'1'?>" required autocomplete="<?=$setup?'new-password':'current-password'?>"></label>
<button class="primary" type="submit"><?=$setup?'Adminzugang erstellen':'Anmelden'?></button></form>
<?php endif; ?></section>
<?php else: ?>
<div class="dashboard"><aside class="sidebar"><h1>Meine Restaurants</h1><p><?=count($items)?> Einträge · <?=count(array_filter($items,fn($a)=>$a['status']==='published'))?> veröffentlicht</p>
<form method="post" class="add"><input type="hidden" name="csrf" value="<?=esc(csrf())?>"><input type="hidden" name="action" value="new"><input name="name" placeholder="Neues Restaurant" required><input name="city" placeholder="Stadt" required><button type="submit">+ Restaurant hinzufügen</button></form>
<nav class="restaurant-list"><?php foreach($items as $item): ?><a class="<?=$item['id']===$id?'active':''?>" href="?id=<?=rawurlencode($item['id'])?>"><strong><?=esc($item['name'])?></strong><small><?=esc($item['city'])?> · <?=$item['status']==='published'?'Online':'Entwurf'?></small></a><?php endforeach; ?></nav>
<a class="backup" href="?action=backup">Text-Datensicherung (JSON) ↓</a>
<details class="password"><summary>Passwort ändern</summary><form method="post"><input type="hidden" name="csrf" value="<?=esc(csrf())?>"><input type="hidden" name="action" value="password"><input name="current_password" type="password" placeholder="Aktuelles Passwort" required><input name="new_password" type="password" minlength="12" placeholder="Neues Passwort" required><button>Passwort ändern</button></form></details>
</aside><section class="editor">
<?php if($selected): ?>
<?php if(isset($_GET['saved'])): ?><p class="success">Gespeichert. <?=$selected['status']==='published'?'Der Bericht ist jetzt öffentlich.':'Der Entwurf bleibt privat.'?></p><?php endif; ?>
<div class="editor-title"><div><span class="eyebrow">RESTAURANTBERICHT</span><h2><?=esc($selected['name'])?></h2><p><?=esc($selected['city'])?></p></div><span class="status"><?=$selected['status']==='published'?'Veröffentlicht':'Entwurf'?></span></div>
<form method="post" enctype="multipart/form-data" class="editor-form"><input type="hidden" name="csrf" value="<?=esc(csrf())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=esc($selected['id'])?>">
<fieldset><legend>Allgemeine Angaben</legend><div class="two"><?php field('name','Restaurant',$selected);field('city','Stadt',$selected);field('address','Adresse',$selected);field('cuisine','Küchenrichtung',$selected);field('website','Restaurantwebsite (https://)',$selected,'url');field('visit_date','Besuchsdatum',$selected,'date');field('occasion','Anlass',$selected);field('companions','Begleitung',$selected);field('price','Ausgaben pro Person',$selected);?></div></fieldset>
<fieldset><legend>Mein persönlicher Bericht</legend><?php field('impression','1 · Erster Eindruck & Atmosphäre',$selected,'textarea');field('food_text','2 · Essen & Trinken',$selected,'textarea');field('ambience_text','3 · Atmosphäre (Details)',$selected,'textarea');field('service_text','4 · Service',$selected,'textarea');field('value_text','5 · Preis-Leistung',$selected,'textarea');field('highlights','Was war besonders gut?',$selected,'textarea');field('shortcomings','Was hat mich gestört?',$selected,'textarea');field('verdict','Mein Fazit',$selected,'textarea');?><label class="field"><span>Würde ich wiederkommen?</span><select name="revisit"><option value="">Noch offen</option><?php foreach(['Ja','Vielleicht','Nein'] as $v): ?><option value="<?=$v?>" <?=$selected['revisit']===$v?'selected':''?>><?=$v?></option><?php endforeach; ?></select></label></fieldset>
<fieldset><legend>Bewertung mit 0–10 Sternen</legend><p class="help">Optional. „Nicht bewertet“ eignet sich auch für Streetfood oder nicht passende Kategorien.</p><div class="two"><?php foreach(['food'=>'Essen & Trinken','ambience'=>'Atmosphäre','service'=>'Service','value'=>'Preis-Leistung'] as $key=>$label): ?><label class="field"><span><?=esc($label)?></span><select name="score_<?=esc($key)?>"><option value="">Nicht bewertet</option><?php for($n=0;$n<=10;$n++): ?><option value="<?=$n?>" <?=$selected['score_'.$key]!==null && (int)$selected['score_'.$key]===$n?'selected':''?>><?=$n?> / 10 ★</option><?php endfor; ?></select></label><?php endforeach; ?></div></fieldset>
<fieldset><legend>Meine Fotos</legend><label class="field"><span>Foto hinzufügen (JPG, PNG, WebP, maximal 8 MB)</span><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label><label class="field"><span>Bildunterschrift</span><input name="photo_caption" maxlength="200"></label>
<?php if($photos): ?><div class="photo-grid"><?php foreach($photos as $photo): ?><figure><img src="media.php?id=<?=$photo['id']?>" alt="<?=esc($photo['caption'])?>" loading="lazy"><figcaption><?=esc($photo['caption'])?></figcaption></figure><?php endforeach; ?></div><?php endif; ?></fieldset>
<div class="publish"><label class="field"><span>Status</span><select name="status"><option value="draft" <?=$selected['status']==='draft'?'selected':''?>>Entwurf – nicht öffentlich</option><option value="published" <?=$selected['status']==='published'?'selected':''?>>Veröffentlicht – öffentlich anzeigen</option></select></label><button class="primary" type="submit">Änderungen speichern</button></div><p class="help">Veröffentlichung erfordert Besuchsdatum, einen Text zu Essen & Trinken und ein Fazit. Fotos bleiben beim Entwurf geschützt.</p></form>
<?php if($photos): ?><div class="danger-zone"><h3>Fotos verwalten</h3><?php foreach($photos as $photo): ?><form method="post"><input type="hidden" name="csrf" value="<?=esc(csrf())?>"><input type="hidden" name="action" value="delete_photo"><input type="hidden" name="id" value="<?=esc($selected['id'])?>"><input type="hidden" name="photo_id" value="<?=$photo['id']?>"><span><?=esc($photo['caption']?:$photo['filename'])?></span><button>Foto löschen</button></form><?php endforeach; ?></div><?php endif; ?>
<details class="danger-zone"><summary>Restaurant dauerhaft löschen</summary><p>Restaurant und alle Bilder werden gelöscht. Bitte vorher ein Backup erstellen.</p><form method="post"><input type="hidden" name="csrf" value="<?=esc(csrf())?>"><input type="hidden" name="action" value="delete_restaurant"><input type="hidden" name="id" value="<?=esc($selected['id'])?>"><button class="danger">Endgültig löschen</button></form></details>
<?php else: ?><p>Bitte ein Restaurant auswählen oder neu anlegen.</p><?php endif; ?></section></div>
<?php endif; ?></main></body></html>
