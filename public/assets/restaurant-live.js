/* Public restaurant journal: reveal only published personal reports. */
(function(){
  'use strict';
  var root=document.documentElement;
  var english=root.lang==='en';
  fetch((english?'../':'')+'redaktion/feed.php',{credentials:'omit',cache:'no-store'})
    .then(function(response){if(!response.ok)throw new Error('Feed unavailable');return response.json();})
    .then(function(data){
      if(!data||!Array.isArray(data.reports))return;
      // Keep the city lists in sync with the saved database without exposing draft reviews.
      if(Array.isArray(data.directory)){
        var directoryIds=new Set();
        var grid=document.querySelector('.restaurant-city-grid');
        var getGroup=function(city){
          var groups=Array.from(grid.querySelectorAll('details.restaurant-city'));
          var found=groups.find(function(d){return d.querySelector('.restaurant-city-name').textContent.toLocaleLowerCase()===city.toLocaleLowerCase();});
          if(found)return found;
          var details=document.createElement('details');details.className='restaurant-city';
          var summary=document.createElement('summary');
          var title=document.createElement('span');title.className='restaurant-city-name';title.textContent=city;
          var num=document.createElement('span');num.className='restaurant-city-count';
          var arrow=document.createElement('span');arrow.className='restaurant-city-chevron';arrow.setAttribute('aria-hidden','true');arrow.textContent='⌄';
          summary.append(title,num,arrow);
          details.append(summary,document.createElement('ul'));grid.append(details);return details;
        };
        data.directory.forEach(function(item){
          if(!item||typeof item.id!=='string'||!/^[a-z0-9-]+$/.test(item.id)||!item.city||!item.name)return;
          directoryIds.add(item.id);
          var target=getGroup(item.city).querySelector('ul');
          var li=document.getElementById(item.id);
          if(!li){
            li=document.createElement('li');li.className='restaurant-list-entry';li.id=item.id;
            var main=document.createElement('span');main.className='restaurant-list-entry-main';
            var title=document.createElement('strong');title.textContent=item.name;main.append(title);
            var status=document.createElement('span');status.className='restaurant-list-entry-status';status.textContent=english?'Report to follow':'Bericht folgt';
            li.append(main,status);
          }else{
            var titleNode=li.querySelector('.restaurant-list-entry-main > strong, .restaurant-list-entry-main > a');
            if(titleNode && titleNode.firstChild && titleNode.firstChild.nodeType===3)titleNode.firstChild.nodeValue=item.name+' ';
          }
          target.appendChild(li);
        });
        Array.from(grid.querySelectorAll('li.restaurant-list-entry')).forEach(function(li){
          if(!directoryIds.has(li.id))li.remove();
        });
        Array.from(grid.querySelectorAll('details.restaurant-city')).forEach(function(group){
          var number=group.querySelector('.restaurant-city-count');
          var length=group.querySelectorAll('li.restaurant-list-entry').length;
          if(number)number.textContent=String(length).padStart(2,'0');
          if(length===0)group.remove();
        });
      }
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
