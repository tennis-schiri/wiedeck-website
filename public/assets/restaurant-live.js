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
      var reports=data.reports.filter(function(r){return r&&typeof r.id==='string'&&/^[a-z0-9-]+$/.test(r.id);});
      // The feed contains published reports only; each restaurant appears at most once.
      var featured=document.getElementById('restaurant-journal');
      var featuredGrid=document.getElementById('restaurant-featured-grid');
      if(featured && featuredGrid && reports.length){
        featuredGrid.replaceChildren();
        reports.slice(0,4).forEach(function(r,index){
          var article=document.createElement('article');article.className='restaurant-note';
          article.id='featured-'+r.id;
          var visual=document.createElement('div');visual.className='restaurant-note-visual';visual.setAttribute('aria-hidden','true');
          var number=document.createElement('span');number.className='restaurant-note-number';
          number.textContent=String(index+1).padStart(2,'0')+' / '+String(Math.min(reports.length,4)).padStart(2,'0');
          var city=document.createElement('span');city.className='restaurant-note-city';city.textContent=String(r.city||'').toLocaleUpperCase();
          var glyph=document.createElement('span');glyph.className='restaurant-note-glyph';
          glyph.textContent=String(r.name||'?').trim().charAt(0).toLocaleUpperCase();
          visual.append(number,city,glyph);
          var content=document.createElement('div');content.className='restaurant-note-content';
          var type=document.createElement('span');type.className='restaurant-note-type';
          type.textContent=String(r.cuisine|| (english?'Personal review':'Persönlicher Bericht'));
          var title=document.createElement('h5');title.textContent=String(r.name||'');
          var info=document.createElement('p');
          var parts=[];
          if(r.visit_date && /^\d{4}-\d{2}-\d{2}$/.test(r.visit_date)){
            var a=r.visit_date.split('-');
            parts.push((english?'Last visit: ':'Letzter Besuch: ')+(english?a.join('-'):a[2]+'.'+a[1]+'.'+a[0]));
          }
          if(Number(r.visit_count)>1)parts.push(english?Number(r.visit_count)+' published visits':Number(r.visit_count)+' veröffentlichte Besuche');
          info.textContent=parts.join(' · ');
          var actions=document.createElement('div');actions.className='restaurant-note-actions';
          var link=document.createElement('a');link.href=(english?'../':'')+'redaktion/bericht.php?id='+encodeURIComponent(r.id);
          link.textContent=english?'Read personal review ↗':'Persönlichen Bericht lesen ↗';
          actions.append(link);content.append(type,title,info,actions);article.append(visual,content);featuredGrid.append(article);
        });
        featured.hidden=false;
      }else if(featured){
        featured.hidden=true;
      }
      reports.forEach(function(r){
        var url=(english?'../':'')+'redaktion/bericht.php?id='+encodeURIComponent(r.id);
        var entry=document.getElementById(r.id);
        if(entry){
          var status=entry.querySelector('.restaurant-list-entry-status');
          if(status){status.textContent=english?'Report online':'Bericht online';status.classList.add('is-published');}
          var main=entry.querySelector('.restaurant-list-entry-main');
          if(main && !main.querySelector('a[href*="bericht.php"]')){
            var a=document.createElement('a');a.href=url;a.className='restaurant-entry-report';
            a.textContent=english?'Read personal review ↗':'Persönlichen Bericht lesen ↗';
            main.appendChild(a);
          }
        }
      });
      // Date-only publications are still real public visits.
      // They remain in the directory rather than occupying a featured report card.
      var reportIds=new Set(reports.map(function(r){return r.id;}));
      if(Array.isArray(data.published)){
        data.published.forEach(function(item){
          if(!item||typeof item.id!=='string'||!/^[a-z0-9-]+$/.test(item.id)||reportIds.has(item.id))return;
          var entry=document.getElementById(item.id);
          if(!entry)return;
          var status=entry.querySelector('.restaurant-list-entry-status');
          if(status){
            status.textContent=english?'Visit online':'Besuch online';
            status.classList.add('is-published');
          }
          var main=entry.querySelector('.restaurant-list-entry-main');
          if(main&&!main.querySelector('a[href*="bericht.php"]')){
            var link=document.createElement('a');
            link.href=(english?'../':'')+'redaktion/bericht.php?id='+encodeURIComponent(item.id);
            link.className='restaurant-entry-report';
            link.textContent=english?'View visit ↗':'Besuch ansehen ↗';
            main.appendChild(link);
          }
        });
      }
      // Featured restaurants are shown above; avoid repeating them in the lower list.
      if(featured && !featured.hidden){
        var promotedIds=new Set(reports.slice(0,4).map(function(r){return r.id;}));
        var directoryGrid=document.querySelector('.restaurant-city-grid');
        if(directoryGrid){
          Array.from(directoryGrid.querySelectorAll('li.restaurant-list-entry')).forEach(function(li){
            if(promotedIds.has(li.id))li.remove();
          });
          Array.from(directoryGrid.querySelectorAll('details.restaurant-city')).forEach(function(group){
            var total=group.querySelectorAll('li.restaurant-list-entry').length;
            if(!total){group.remove();return;}
            var count=group.querySelector('.restaurant-city-count');
            if(count)count.textContent=String(total).padStart(2,'0');
          });
        }
      }
    }).catch(function(){/* General restaurant directory remains fully accessible offline. */});
})();
