/* Browser-side safety net for restaurant visit forms.
   Drafts live only in sessionStorage (this browser tab), never in a third-party service. */
(function(){
  'use strict';
  var form=document.getElementById('restaurant-visit-form');
  if(!form)return;
  var id=form.elements.namedItem('visit_id');
  var restaurant=form.elements.namedItem('restaurant_id');
  if(!id||!restaurant)return;
  var key='mw-restaurant-visit-draft-v1:'+restaurant.value+':'+id.value;
  var fields=Array.from(form.querySelectorAll('input[name], textarea[name], select[name]'))
    .filter(function(field){return !['csrf','restaurant_id','visit_id','action','photo'].includes(field.name) &&
      !['file','submit','button','hidden'].includes(field.type);});
  var stored=null;
  try{
    if(new URLSearchParams(location.search).has('saved')){
      sessionStorage.removeItem(key);
    }else if(form.dataset.validationError!=='1'){
      stored=sessionStorage.getItem(key);
    }
  }catch(error){return;} // Browser storage might be disabled.
  if(stored){
    try{
      var data=JSON.parse(stored);
      if(data&&typeof data==='object'&&!Array.isArray(data)){
        fields.forEach(function(field){
          if(typeof data[field.name]==='string' && field.value!==data[field.name]){
            field.value=data[field.name];
          }
        });
        var message=document.createElement('p');
        message.className='help draft-recovery';
        message.textContent='Deine noch nicht gespeicherten Eingaben aus diesem Browser-Tab wurden wiederhergestellt. Bitte den Besuch speichern.';
        form.insertBefore(message,form.firstChild);
      }
    }catch(error){/* Invalid or outdated browser draft: ignore. */}
  }
  function persist(){
    var data={};
    fields.forEach(function(field){data[field.name]=field.value;});
    try{sessionStorage.setItem(key,JSON.stringify(data));}catch(error){/* Storage full or unavailable. */}
  }
  form.addEventListener('input',persist);
  form.addEventListener('change',persist);
  form.addEventListener('submit',persist);
})();
