/* MW / Restaurant-Redaktion: preview AI writing suggestions before any form field changes. */
(function(){
  'use strict';
  var generate=document.getElementById('ai-generate');
  var apply=document.getElementById('ai-apply');
  var preview=document.getElementById('ai-preview');
  var previewFields=document.getElementById('ai-preview-fields');
  var status=document.getElementById('ai-status');
  if(!generate||!apply||!preview||!previewFields||!status)return;
  var form=generate.closest('form');
  if(!form)return;
  var labels={
    impression:'Erster Eindruck',food_text:'Essen & Trinken',
    ambience_text:'Atmosphäre',service_text:'Service',
    value_text:'Preis-Leistung',highlights:'Besonders gut',
    shortcomings:'Was mich gestört hat',verdict:'Mein Fazit'
  };
  var generated=null;
  function message(value){status.textContent=value;}
  generate.addEventListener('click',function(){
    var notes=form.querySelector('[name="ai_notes"]');
    if(!notes||notes.value.trim().length<8){message('Bitte erst eigene Stichpunkte eingeben (mindestens 8 Zeichen).');notes&&notes.focus();return;}
    if(notes.value.length>6000){message('Bitte die Stichpunkte auf höchstens 6.000 Zeichen kürzen.');return;}
    generated=null;preview.hidden=true;previewFields.replaceChildren();
    generate.disabled=true;message('KI formuliert einen Entwurf …');
    var body=new FormData();
    ['csrf','restaurant_id','visit_id','ai_notes'].forEach(function(name){
      var field=form.elements.namedItem(name);
      if(field)body.append(name,field.value);
    });
    fetch('ai-draft.php',{method:'POST',body:body,credentials:'same-origin',cache:'no-store'})
      .then(function(response){
        return response.json().then(function(data){
          if(!response.ok)throw new Error(data.error||'HTTP '+response.status);
          return data;
        });
      }).then(function(data){
        if(!data||!data.fields)throw new Error('Kein Textvorschlag erhalten.');
        generated=data.fields;
        var nonempty=0;
        Object.keys(labels).forEach(function(name){
          var value=generated[name];
          if(typeof value!=='string'||!value.trim())return;
          nonempty++;
          var item=document.createElement('section');
          var heading=document.createElement('h4');heading.textContent=labels[name];
          var paragraph=document.createElement('p');paragraph.textContent=value;
          item.append(heading,paragraph);previewFields.append(item);
        });
        if(nonempty===0){message('Für diese Stichpunkte konnte noch kein sinnvoller Text formuliert werden.');return;}
        preview.hidden=false;
        message('Vorschlag erstellt. Bitte die Texte prüfen und bei Bedarf übernehmen.');
      }).catch(function(error){message('KI-Entwurf fehlgeschlagen: '+error.message);})
      .finally(function(){generate.disabled=false;});
  });
  apply.addEventListener('click',function(){
    if(!generated)return;
    var conflicts=Object.keys(labels).some(function(name){
      var field=form.elements.namedItem(name);
      return field&&field.value.trim()&&typeof generated[name]==='string'&&generated[name].trim();
    });
    if(conflicts&&!window.confirm('Vorhandene Berichtstexte in den ausgefüllten Feldern durch den KI-Vorschlag ersetzen?'))return;
    var changed=0;
    Object.keys(labels).forEach(function(name){
      var field=form.elements.namedItem(name);
      if(field && typeof generated[name]==='string' && generated[name].trim()){
        field.value=generated[name];changed++;
      }
    });
    preview.hidden=true;
    message(changed+' Textfelder übernommen. Bitte prüfen und unten „Besuch speichern“ anklicken.');
    var first=form.elements.namedItem('impression');if(first)first.focus();
  });
})();