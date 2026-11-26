(function(){
 'use strict';
 function init(){
  const normalize=s=>String(s||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
  function node(tag,cls,label){const el=document.createElement(tag);if(cls)el.className=cls;if(label!==undefined)el.textContent=label;return el;}
  function button(cls,label,aria){const b=node('button',cls,label);b.type='button';b.setAttribute('aria-label',aria);return b;}
  document.querySelectorAll('.cel-tax-picker').forEach(root=>{
   const list=root.querySelector('.cel-lib-choices'),selected=root.querySelector('.cel-tax-selected'),search=root.querySelector('.cel-tax-search');
   if(!list||!selected||!search||root.dataset.pickerReady)return;
   root.dataset.pickerReady='1';
   const orderField=root.querySelector('.cel-theme-order'),ordered=Boolean(orderField),count=root.querySelector('.cel-tax-count'),add=root.querySelector('.cel-tax-add'),status=root.querySelector('.cel-tax-status'),empty=root.querySelector('.cel-tax-empty');
   let order=ordered?orderField.value.split(',').filter(Boolean):[],dragged=null,busy=false;
   const inputs=()=>Array.from(list.querySelectorAll('input[type="checkbox"]'));
   const nameOf=input=>input.closest('label').querySelector('span').textContent.trim();
   const find=id=>inputs().find(input=>input.value===id);
   function refresh(){
    const checked=inputs().filter(input=>input.checked);
    const ids=checked.map(input=>input.value);
    order=ordered?order.filter(id=>ids.includes(id)).concat(ids.filter(id=>!order.includes(id))):ids;
    if(ordered)orderField.value=order.join(',');
    const fragment=document.createDocumentFragment();
    order.forEach((id,index)=>{
     const input=find(id);if(!input)return;const name=nameOf(input);
     if(!ordered){const chip=button('cel-tax-chip',name+' ×','Retirer '+name);chip.dataset.termId=id;fragment.appendChild(chip);return;}
     const chip=node('span','cel-tax-chip cel-theme-chip'+(index===0?' is-primary':''));chip.dataset.termId=id;chip.draggable=true;
     const grip=node('span','cel-theme-grip','⠿');grip.setAttribute('aria-hidden','true');
     chip.append(grip,node('span','cel-theme-name',name));
     if(index===0)chip.appendChild(node('small','cel-theme-primary','Principal'));
     [-1,1].forEach(step=>{const b=button('cel-theme-move',step<0?'←':'→',(step<0?'Avancer ':'Reculer ')+name);b.dataset.step=String(step);b.disabled=index+step<0||index+step>=order.length;chip.appendChild(b);});
     chip.appendChild(button('cel-theme-remove','×','Retirer '+name));
     fragment.appendChild(chip);
    });
    if(!order.length)fragment.appendChild(node('span','description','Aucune sélection'));
    selected.replaceChildren(fragment);
    if(count)count.textContent='('+ids.length+' sélectionné'+(ids.length>1?'s':'')+')';
   }
   function move(id,to){
    const from=order.indexOf(id);if(from<0||to<0||to>=order.length||from===to)return;
    order.splice(from,1);order.splice(to,0,id);refresh();
   }
   selected.addEventListener('click',e=>{
    const b=e.target.closest('button'),chip=b&&b.closest('[data-term-id]');if(!chip||!selected.contains(chip))return;
    const id=chip.dataset.termId;
    if(b.classList.contains('cel-theme-move')){const step=Number(b.dataset.step),to=order.indexOf(id)+step;move(id,to);const next=Array.from(selected.children).find(el=>el.dataset.termId===id);if(next){const focus=next.querySelector('.cel-theme-move[data-step="'+step+'"]:not(:disabled)')||next.querySelector('.cel-theme-move:not(:disabled)');if(focus)focus.focus();}return;}
    if(!ordered||b.classList.contains('cel-theme-remove')){const input=find(id);if(input){input.checked=false;input.dispatchEvent(new Event('change',{bubbles:true}));}}
   });
   const clearDrag=()=>selected.querySelectorAll('.is-dragging,.is-drop-target').forEach(el=>el.classList.remove('is-dragging','is-drop-target'));
   selected.addEventListener('dragstart',e=>{const chip=e.target.closest('.cel-theme-chip');if(!ordered||!chip||!e.dataTransfer)return;dragged=chip.dataset.termId;e.dataTransfer.setData('text/plain',dragged);e.dataTransfer.effectAllowed='move';chip.classList.add('is-dragging');});
   selected.addEventListener('dragover',e=>{const chip=e.target.closest('.cel-theme-chip');if(!chip||!dragged||chip.dataset.termId===dragged)return;e.preventDefault();clearDrag();chip.classList.add('is-drop-target');if(e.dataTransfer)e.dataTransfer.dropEffect='move';});
   selected.addEventListener('drop',e=>{const chip=e.target.closest('.cel-theme-chip');if(!chip||!dragged)return;e.preventDefault();const id=dragged;dragged=null;clearDrag();move(id,order.indexOf(chip.dataset.termId));});
   selected.addEventListener('dragend',()=>{dragged=null;clearDrag();});
   function exact(name){return inputs().find(input=>normalize(nameOf(input))===normalize(name.trim()));}
   function filter(){
    const name=search.value.trim(),q=normalize(name);let n=0;
    inputs().forEach(input=>{const match=normalize(nameOf(input)).includes(q);input.closest('label').style.display=match?'':'none';if(match)n++;});
    if(empty)empty.hidden=n!==0;
    if(add){add.hidden=!name||Boolean(exact(name));add.disabled=busy;add.textContent='+ Ajouter « '+name+' »';}
   }
   list.addEventListener('change',refresh);
   search.addEventListener('input',()=>{if(status)status.textContent='';filter();});
   async function create(){
    const name=search.value.trim();if(!name||busy)return;
    const existing=exact(name);if(existing){existing.checked=true;search.value='';refresh();filter();return;}
    busy=true;filter();if(status)status.textContent='Création…';
    try{
     const data=new URLSearchParams({action:'cel_library_create_term',nonce:root.dataset.nonce,key:root.dataset.key,name});
     const response=await fetch(window.ajaxurl,{method:'POST',credentials:'same-origin',body:data});
     const result=await response.json();if(!result.success)throw new Error(typeof result.data==='string'?result.data:'Création impossible.');
     let input=find(String(result.data.id));
     if(!input){input=document.createElement('input');input.type='checkbox';input.name='cel_lib['+root.dataset.key+'][]';input.value=String(result.data.id);const label=node('label');label.append(input,' ',node('span','',result.data.name));list.appendChild(label);}
     input.checked=true;if(search.value.trim()===name)search.value='';refresh();if(status)status.textContent='Valeur ajoutée et sélectionnée.';
    }catch(error){if(status)status.textContent=error.message||'Création impossible.';}finally{busy=false;filter();}
   }
   if(add)add.addEventListener('click',create);
   search.addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.isComposing){e.preventDefault();create();}});
   refresh();filter();
  });
 }
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
