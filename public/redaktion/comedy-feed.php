<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
header('Cache-Control: public, max-age=120');
// All clubs are public; only explicitly published shows contribute public counters.
$clubs=db()->query('SELECT id,name,city,category,website FROM comedy_clubs
    ORDER BY category,name COLLATE NOCASE')->fetchAll();
$shows=db()->query('SELECT s.id,s.club_id,s.show_date,s.title,s.comedians,s.report_text,
    s.published_at,c.name,c.city,c.category
    FROM comedy_shows s JOIN comedy_clubs c ON c.id=s.club_id
    WHERE s.status="published" AND s.show_date!=""
    ORDER BY s.published_at DESC,s.id DESC')->fetchAll();
$totals=[];$reports=[];$featuredClubs=[];
foreach($shows as $show){
    $id=$show['club_id'];
    if(!isset($totals[$id]))$totals[$id]=['shows'=>0,'comedians'=>[]];
    $totals[$id]['shows']++;
    // Names per line; count unique names, regardless of the number of appearances.
    foreach(preg_split('/\\r\\n|\\r|\\n/u',(string)$show['comedians']) as $name){
        $name=trim($name);
        if($name!=='')$totals[$id]['comedians'][mb_strtolower($name,'UTF-8')]=$name;
    }
    if(trim($show['report_text'])!==''&&!isset($featuredClubs[$id])){
        $featuredClubs[$id]=true;
        $reports[]=['id'=>$id,'name'=>$show['name'],'city'=>$show['city'],
            'category'=>$show['category'],'show_date'=>$show['show_date'],
            'published_at'=>$show['published_at']];
    }
}
foreach($clubs as &$club){
    $id=$club['id'];
    $club['show_count']=$totals[$id]['shows']??0;
    $club['comedians_count']=count($totals[$id]['comedians']??[]);
}
unset($club);
json_output(['directory'=>$clubs,'reports'=>$reports]);
