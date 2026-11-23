<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
// Only published visit entries may be shown publicly.
header('Cache-Control: public, max-age=120');
$rows=db()->query('SELECT
    r.id, r.name, r.city, r.cuisine,
    (SELECT COUNT(*) FROM visits all_visits
     WHERE all_visits.restaurant_id=r.id
       AND all_visits.status="published" AND all_visits.visit_date!="") AS visit_count,
    MAX(v.visit_date) AS visit_date,
    MAX(v.published_at) AS published_at
    FROM restaurants r
    JOIN visits v ON v.restaurant_id=r.id
    WHERE v.status="published" AND v.visit_date!=""
      AND (TRIM(v.impression)!="" OR TRIM(v.food_text)!=""
           OR TRIM(v.ambience_text)!="" OR TRIM(v.service_text)!=""
           OR TRIM(v.value_text)!="" OR TRIM(v.highlights)!=""
           OR TRIM(v.shortcomings)!="" OR TRIM(v.verdict)!="")
    GROUP BY r.id,r.name,r.city,r.cuisine
    ORDER BY published_at DESC')->fetchAll();
$directory=db()->query('SELECT id,name,city FROM restaurants
    WHERE id LIKE "place-%" OR EXISTS (
      SELECT 1 FROM visits v WHERE v.restaurant_id=restaurants.id AND v.status="published"
        AND v.visit_date!=""
    )
    ORDER BY city COLLATE NOCASE,name COLLATE NOCASE')->fetchAll();
// A public visit with only a date is legitimate, but does not make a featured report.
$published=db()->query('SELECT restaurant_id AS id,COUNT(*) AS visit_count
    FROM visits WHERE status="published" AND visit_date!=""
    GROUP BY restaurant_id')->fetchAll();
json_output(['reports'=>$rows,'directory'=>$directory,'published'=>$published]);
