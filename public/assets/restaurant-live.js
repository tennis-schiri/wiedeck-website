/* Public restaurant journal: reveal only published personal reports. */
(function(){
  'use strict';
  var root=document.documentElement;
  var english=root.lang==='en';
  fetch((english?'../':'')+'redaktion/feed.php',{credentials:'omit',cache:'no-store'})
    .then(function(response){if(!response.ok)throw new Error('Feed unavailable');return response.json();})
    .then(function(data){
      if(!data||!Array.isArray(data.reports))return;
      var count=document.querySelector('[data-published-count]');
      if(count)count.textContent=String(data.reports.length);
      data.reports.forEach(function(r){
        if(!r||typeof r.id!=='string'||!/^[-a-z0-9]+$/.test(r.id))return;
        var url=(english?'../':'')+'redaktion/bericht.php?id='+encodeURIComponent(r.id);
        var entry=document.getElementById(r.id);
        if(entry){
          var status=entry.querySelector('.restaurant-list-entry-status');
          if(status){status.textContent=english?'Report online':'Bericht online';status.classList.add('is-published');}
          var main=entry.querySelector('.restaurant-list-entry-main');
          if(main && !main.querySelector('a[href*="bericht.php"]')){
            var a=document.createElement('a');
            a.href=url;a.className='restaurant-entry-report';
            a.textContent=english?'Read personal review ↗':'Persönlichen Bericht lesen ↗';
            main.appendChild(a);
          }
        }
        // The first four restaurants also have existing general-info cards.
        var maps={'place-berlin-01':'restaurant-staev-berlin','place-berlin-02':'restaurant-jamie-oliver-kitchen','place-berlin-03':'restaurant-night-kitchen','place-berlin-04':'restaurant-scheers-schnitzel'};
        var feature=document.getElementById(maps[r.id]||'');
        if(feature){
          var pending=feature.querySelector('.restaurant-note-pending');
          if(pending){pending.textContent=english?'PERSONAL REPORT ONLINE':'PERSÖNLICHER BERICHT ONLINE';}
          var links=feature.querySelector('.restaurant-note-actions');
          if(links && !links.querySelector('.restaurant-report-link')){
            var b=document.createElement('a');b.href=url;b.className='restaurant-report-link';b.textContent=english?'Read my report ↗':'Meinen Bericht lesen ↗';links.prepend(b);
          }
        }
      });
    }).catch(function(){/* General restaurant directory remains fully accessible offline. */});
})();
