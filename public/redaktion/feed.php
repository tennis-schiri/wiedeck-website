<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
// Only published visit entries may be shown publicly.
header('Cache-Control: public, max-age=120');
$rows=db()->query('SELECT
    r.id, r.name, r.city, r.cuisine,
    COUNT(v.id) AS visit_count,
    MAX(v.visit_date) AS visit_date,
    MAX(v.published_at) AS published_at
    FROM restaurants r
    JOIN visits v ON v.restaurant_id=r.id
    WHERE v.status="published" AND v.visit_date!="" AND v.food_text!="" AND v.verdict!=""
    GROUP BY r.id,r.name,r.city,r.cuisine
    ORDER BY published_at DESC')->fetchAll();
$directory=db()->query('SELECT id,name,city FROM restaurants
    WHERE id LIKE "place-%" OR EXISTS (
      SELECT 1 FROM visits v WHERE v.restaurant_id=restaurants.id AND v.status="published"
        AND v.visit_date!="" AND v.food_text!="" AND v.verdict!=""
    )
    ORDER BY city COLLATE NOCASE,name COLLATE NOCASE')->fetchAll();
json_output(['reports'=>$rows,'directory'=>$directory]);
