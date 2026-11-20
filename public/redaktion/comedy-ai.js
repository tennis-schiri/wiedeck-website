/* Optional AI draft for the comedy journal: no automatic publication. */
(function(){
  'use strict';
  var button=document.getElementById('comedy-ai-generate');
  var apply=document.getElementById('comedy-ai-apply');
  var preview=document.getElementById('comedy-ai-preview');
  var text=document.getElementById('comedy-ai-text');
  var status=document.getElementById('comedy-ai-status');
  var form=document.getElementById('comedy-editor-form');
  if(!button||!apply||!preview||!text||!status||!form)return;
  var draft='';
  button.addEventListener('click',function(){
    var notes=form.elements.namedItem('ai_notes');
    if(!notes||notes.value.trim().length<8){status.textContent='Bitte erst eigene Stichpunkte eingeben.';return;}
    draft='';preview.hidden=true;button.disabled=true;
    status.textContent='KI erstellt einen Vorschlag …';
    var data=new FormData();
    ['csrf','club_id','show_id','ai_notes'].forEach(function(field){
      var input=form.elements.namedItem(field);if(input)data.append(field,input.value);
    });
    fetch('comedy-ai.php',{method:'POST',credentials:'same-origin',body:data,cache:'no-store'})
      .then(function(response){return response.json().then(function(payload){if(!response.ok)throw Error(payload.error||'HTTP '+response.status);return payload;});})
      .then(function(payload){
        draft=typeof payload.report==='string'?payload.report:'';
        if(!draft.trim()){status.textContent='Bitte mehr eigene Eindrücke angeben, damit ein sinnvoller Bericht entstehen kann.';return;}
        text.textContent=draft;preview.hidden=false;
        status.textContent='Entwurf erstellt – bitte prüfen und bei Bedarf übernehmen.';
      }).catch(function(e){status.textContent='KI nicht verfügbar: '+e.message;})
      .finally(function(){button.disabled=false;});
  });
  apply.addEventListener('click',function(){
    if(!draft)return;
    var output=form.elements.namedItem('report_text');if(!output)return;
    if(output.value.trim()&&!window.confirm('Vorhandenen Bericht durch den KI-Vorschlag ersetzen?'))return;
    output.value=draft;preview.hidden=true;status.textContent='Text übernommen. Bitte prüfen und unten speichern.';
    output.focus();
  });
})();