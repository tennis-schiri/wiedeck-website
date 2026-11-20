<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
// Only explicitly published personal reports are exposed to the public.
header('Cache-Control: public, max-age=120');
$rows=db()->query('SELECT id,name,city,cuisine,visit_date,food_text,verdict,
 score_food,score_ambience,score_service,score_value,published_at
 FROM restaurants WHERE status="published" AND visit_date!="" AND food_text!="" AND verdict!=""
 ORDER BY published_at DESC')->fetchAll();
foreach($rows as &$row){$stmt=db()->prepare('SELECT id,caption FROM photos WHERE restaurant_id=? ORDER BY id');$stmt->execute([$row['id']]);$row['photos']=$stmt->fetchAll();}
json_output(['reports'=>$rows]);
