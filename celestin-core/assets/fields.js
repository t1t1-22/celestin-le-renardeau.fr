jQuery(function($){
  $('.cel-form-tab').on('click',function(){
    const tabs=$(this).closest('.cel-form-tabs');
    const box=tabs.closest('.inside');
    const key=$(this).attr('data-tab');
    tabs.find('.cel-form-tab').removeClass('active').attr('aria-selected','false');
    $(this).addClass('active').attr('aria-selected','true');
    box.find('.cel-form-panel').attr('hidden',true);
    box.find('.cel-form-panel[data-panel="'+key+'"]').removeAttr('hidden');
  });
  $(document).on('click','#cel-add-extra',function(){
    const tpl=document.querySelector('#cel-extra-template');
    if(tpl)$('#cel-extra-rows').append($(tpl.innerHTML));
  });
  $(document).on('click','.cel-remove-extra',function(){
    $(this).closest('.cel-extra-row').remove();
  });
  $(document).on('click','.cel-select-section-image',function(e){
    e.preventDefault();
    const container=$(this).closest('.cel-section-image');
    const picker=wp.media({title:'Choisir une illustration',button:{text:'Utiliser cette illustration'},library:{type:'image'},multiple:false});
    picker.on('select',function(){
      const image=picker.state().get('selection').first().toJSON();
      container.find('.cel-section-image-id').val(image.id);
      const thumb=image.sizes&&image.sizes.medium?image.sizes.medium.url:image.url;
      container.find('.cel-section-image-preview').attr('src',thumb).show();
    });
    picker.open();
  });
  $(document).on('click','.cel-remove-section-image',function(e){
    e.preventDefault();
    const container=$(this).closest('.cel-section-image');
    container.find('.cel-section-image-id').val('');
    container.find('.cel-section-image-preview').hide().attr('src','');
  });
  $(document).on('click','.cel-pick-media',function(e){
    e.preventDefault();
    const row=$(this).closest('.cel-field');
    const type=$(this).attr('data-media-type')||'image';
    const media=wp.media({title:'Choisir un fichier',multiple:false,library:{type:type},button:{text:'Utiliser ce fichier'}});
    media.on('select',function(){
      const attachment=media.state().get('selection').first().toJSON();
      row.find('.cel-media-value').val(attachment.url).trigger('change');
    });
    media.open();
  });
});

jQuery(function($){
 if(!$('body').is('.post-type-cel_livre,.post-type-cel_histoire'))return;
 const field=$('#cel-book-title'),title=$('#title'),bar=$('.cel-book-savebar');
 if(!field.length||!bar.length)return;
 field.on('input change',()=>title.val(field.val()));
 title.val(field.val());
 // Preserve WordPress' native submit actions, nonce, draft and preview logic.
 const publish=$('#publish'),draft=$('#save-post'),preview=$('#post-preview');
 if(!publish.length)return;
 $('body').addClass('cel-book-custom-editor');
 bar.find('.cel-book-save-action').on('click',()=>{if(draft.length&&bar.find('.cel-book-publish-action').length)draft[0].click();else publish[0].click()});
 bar.find('.cel-book-publish-action').on('click',()=>publish[0].click());
 bar.find('.cel-book-preview-action').on('click',()=>{if(preview.length)preview[0].click()});
 if(!preview.length)bar.find('.cel-book-preview-action').hide();
 const update=()=>{bar.find('.cel-book-save-action,.cel-book-publish-action').prop('disabled',publish.prop('disabled')||$('#post').hasClass('is-submitting'));};
 if(window.MutationObserver)new MutationObserver(update).observe(publish[0],{attributes:true,attributeFilter:['disabled','class']});
 update();
});

jQuery(function($){
 const normalize=s=>s.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
 $('.cel-tax-picker').each(function(){
   const root=$(this),list=root.find('.cel-lib-choices'),selected=root.find('.cel-tax-selected'),search=root.find('.cel-tax-search');
   if(!selected.length)return;
   function refresh(){
     selected.empty();const checked=list.find('input:checked');
     checked.each(function(){const input=$(this),name=input.closest('label').find('span').text();$('<button>',{type:'button',class:'cel-tax-chip',text:name+' ×','aria-label':'Retirer '+name}).on('click',()=>{input.prop('checked',false).trigger('change')}).appendTo(selected)});
     if(!checked.length)selected.append($('<span>',{class:'description',text:'Aucune sélection'}));
     root.find('.cel-tax-count').text('('+checked.length+' sélectionné'+(checked.length>1?'s':'')+')');
   }
   const button=root.find('.cel-tax-add'),status=root.find('.cel-tax-status');
   let busy=false;
   function exact(name){return list.find('input').filter(function(){return normalize($(this).closest('label').find('span').text().trim())===normalize(name.trim())});}
   function filter(){
     const name=(search.val()||'').trim(),q=normalize(name);let count=0;
     list.children('label').each(function(){const match=normalize($(this).text()).includes(q);$(this).toggle(match);if(match)count++});
     root.find('.cel-tax-empty').prop('hidden',count!==0);
     button.prop('hidden',!name||!!exact(name).length).prop('disabled',busy).text('+ Ajouter « '+name+' »');
   }
   list.on('change','input',refresh);
   search.on('input',()=>{status.text('');filter()});
   function add(){
     const name=(search.val()||'').trim();if(!name||busy)return;
     const existing=exact(name);
     if(existing.length){existing.prop('checked',true);search.val('');refresh();filter();return;}
     busy=true;button.prop('disabled',true);status.text('Création…');
     $.post(ajaxurl,{action:'cel_library_create_term',nonce:root.data('nonce'),key:root.data('key'),name}).done(r=>{
       if(!r.success){status.text(typeof r.data==='string'?r.data:'Création impossible.');return;}
       let choice=list.find('input').filter(function(){return Number(this.value)===Number(r.data.id)});
       if(!choice.length){choice=$('<input>',{type:'checkbox',name:'cel_lib['+root.data('key')+'][]',value:r.data.id});$('<label>').append(choice,' ',$('<span>').text(r.data.name)).appendTo(list);}
       choice.prop('checked',true);
       if((search.val()||'').trim()===name)search.val('');
       refresh();status.text('Valeur ajoutée et sélectionnée.');
     }).fail(xhr=>status.text(typeof xhr.responseJSON?.data==='string'?xhr.responseJSON.data:'Impossible de créer cette valeur. Réessaie.')).always(()=>{busy=false;filter()});
   }
   button.on('click',add);
   search.on('keydown',e=>{if(e.key==='Enter'&&!e.isComposing){e.preventDefault();add()}});
   refresh();filter();
 });
});

jQuery(function($){
 $('.cel-admin-audio').each(function(){
 const box=$(this),input=box.closest('.cel-field').find('.cel-media-value');
 if(!input.length||!box.length)return;
 const audio=box.find('audio')[0],status=box.find('.cel-audio-preview-status'),remove=box.find('.cel-audio-remove');
 function refresh(){const url=input.val().trim();audio.pause();if(url){audio.src=url;audio.hidden=false;remove.prop('hidden',false);status.text('Chargement des informations audio…');}else{audio.removeAttribute('src');audio.load();audio.hidden=true;remove.prop('hidden',true);status.text('Choisis un MP3 pour afficher le lecteur.');}}
 input.on('change',refresh);remove.on('click',()=>input.val('').trigger('change'));
 audio.addEventListener('loadedmetadata',()=>{const d=Math.floor(audio.duration);status.text(Number.isFinite(d)?'Durée : '+Math.floor(d/60)+' min '+String(d%60).padStart(2,'0')+' s':'Audio disponible')});
 audio.addEventListener('error',()=>{if(input.val())status.text('Impossible de lire ce fichier. Vérifie le MP3 ou son adresse.')});
 audio.addEventListener('play',()=>{$('.cel-admin-audio audio').each(function(){if(this!==audio)this.pause()})});
 $('.cel-form-tab').on('click',()=>audio.pause());
 refresh();
 });
});

jQuery(function($){$('.cel-activity-type-picker').each(function(){const root=$(this),select=root.find('select'),open=root.find('.cel-activity-type-open'),panel=root.find('.cel-activity-type-create'),input=root.find('.cel-activity-type-name'),add=root.find('.cel-activity-type-add'),status=root.find('.cel-activity-type-status');open.on('click',()=>{const show=panel.prop('hidden');panel.prop('hidden',!show);open.attr('aria-expanded',String(show));if(show)input.trigger('focus')});function create(){const name=input.val().trim();if(!name||add.prop('disabled'))return;add.prop('disabled',true);status.text('Création…');$.post(ajaxurl,{action:'cel_activity_type_add',nonce:root.data('nonce'),name}).done(r=>{if(!r.success){status.text(r.data||'Création impossible.');return;}const v=r.data.name;if(!select.find('option').filter(function(){return this.value===v}).length)select.append($('<option>',{value:v,text:v}));select.val(v).trigger('change');input.val('');panel.prop('hidden',true);open.attr('aria-expanded','false');status.text('Type ajouté et sélectionné.');}).fail(xhr=>status.text(xhr.responseJSON?.data||'Création impossible.')).always(()=>add.prop('disabled',false))}add.on('click',create);input.on('keydown',e=>{if(e.key==='Enter'){e.preventDefault();create()}})})});
