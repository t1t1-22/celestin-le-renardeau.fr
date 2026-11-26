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
 if(!$('body').is('.post-type-cel_livre,.post-type-cel_histoire,.post-type-cel_coloriage,.post-type-cel_personnage'))return;
 const field=$('#cel-book-title'),title=$('#title'),bar=$('.cel-book-savebar');
 if(!bar.length)return;
 if($('body').is('.post-type-cel_personnage')){
   const sync=()=>{const name=[$('#cel_f_prenom').val(),$('#cel_f_nom').val()].map(v=>(v||'').trim()).filter(Boolean).join(' ');title.val(name);$('.cel-person-live-name').text(name||'Nouveau personnage');};
   $('#cel_f_prenom,#cel_f_nom').on('input change',sync);sync();
 }else{if(!field.length)return;field.on('input change',()=>title.val(field.val()));title.val(field.val());}
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
