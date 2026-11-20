/* Public comedy club directory: show only explicitly published shows and reports. */
(function(){
  'use strict';
  var english=document.documentElement.lang==='en';
  var prefix=english?'../':'';
  var root=document.getElementById('comedy-directory-groups');
  var featured=document.getElementById('comedy-featured');
  var featuredGrid=document.getElementById('comedy-featured-grid');
  if(!root||!featured||!featuredGrid)return;
  var labels={
    'Clubs & kleine Bühnen':english?'Clubs & small stages':'Clubs & kleine Bühnen',
    'Andere Bühnen & besondere Orte':english?'Other stages & venues':'Andere Bühnen & besondere Orte'
  };
  function make(tag,cls,text){
    var node=document.createElement(tag);if(cls)node.className=cls;
    if(text!==undefined)node.textContent=String(text);return node;
  }
  function url(id){return prefix+'redaktion/comedy-bericht.php?id='+encodeURIComponent(id);}
  fetch(prefix+'redaktion/comedy-feed.php',{cache:'no-store',credentials:'omit'})
    .then(function(r){if(!r.ok)throw new Error('Feed unavailable');return r.json();})
    .then(function(data){
      if(!data||!Array.isArray(data.directory)||!Array.isArray(data.reports))return;
      var reports=data.reports.filter(function(r){return r&&typeof r.id==='string'&&/^[a-z0-9-]+$/.test(r.id);});
      var top=reports.slice(0,4),topIds=new Set(top.map(function(r){return r.id;}));
      featuredGrid.replaceChildren();
      if(top.length){
        top.forEach(function(r,i){
          var card=make('article','restaurant-note');
          var artwork=make('div','restaurant-note-visual');artwork.setAttribute('aria-hidden','true');
          artwork.append(make('span','restaurant-note-number',String(i+1).padStart(2,'0')+' / '+String(top.length).padStart(2,'0')),
            make('span','restaurant-note-city',r.city||''),make('span','restaurant-note-glyph',(r.name||'?').charAt(0)));
          var body=make('div','restaurant-note-content');
          body.append(make('span','restaurant-note-type',labels[r.category]||r.category||'Comedy'),make('h5','',r.name||''));
          var date=typeof r.show_date==='string' && /^\d{4}-\d{2}-\d{2}$/.test(r.show_date)
            ?(english?r.show_date:r.show_date.slice(8)+'.'+r.show_date.slice(5,7)+'.'+r.show_date.slice(0,4)):'';
          if(date)body.append(make('p','', (english?'Last show: ':'Letzte Show: ')+date));
          var actions=make('div','restaurant-note-actions');
          var link=make('a','',english?'Read my report ↗':'Meinen Bericht lesen ↗');link.href=url(r.id);
          actions.append(link);body.append(actions);card.append(artwork,body);featuredGrid.append(card);
        });featured.hidden=false;
      }else featured.hidden=true;
      // Only public appearances affect the public statistics; private drafts never leak.
      var groups=new Map();
      data.directory.forEach(function(r){
        if(!r||typeof r.id!=='string'||!/^[-a-z0-9]+$/.test(r.id)||!r.name||!r.category||topIds.has(r.id))return;
        var category=r.category;
        if(!groups.has(category))groups.set(category,[]);
        groups.get(category).push(r);
      });
      root.replaceChildren();
      groups.forEach(function(clubs,category){
        var group=make('section','comedy-group');group.dataset.category=category;
        group.append(make('h3','',labels[category]||category));
        var list=make('ul','');
        clubs.forEach(function(club){
          var li=make('li','comedy-place');li.id=club.id;
          li.append(make('strong','',club.name));
          var meta=make('span','comedy-place-meta',club.city||'');
          var shows=Number(club.show_count)||0,comedians=Number(club.comedians_count)||0;
          if(shows>0){
            meta.textContent+=(meta.textContent?' · ':'')+
              shows+' '+(english?(shows===1?'show':'shows'):(shows===1?'Besuch':'Besuche'))+
              ' · '+comedians+' '+(english?(comedians===1?'comedian':'comedians'):(comedians===1?'Comedian':'Comedians'));
            var link=make('a','comedy-place-link',english?'View appearances ↗':'Auftritte ansehen ↗');
            link.href=url(club.id);li.append(link);
          }
          li.append(meta);list.append(li);
        });group.append(list);root.append(group);
      });
    }).catch(function(){/* Static list remains available without the editorial API. */});
})();
