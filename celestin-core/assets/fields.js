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
 if(!$('body').hasClass('post-type-cel_livre'))return;
 const slot=$('.cel-book-title-slot'),title=$('#titlediv'),bar=$('.cel-book-savebar');
 if(!slot.length||!title.length||!bar.length)return;
 title.appendTo(slot);
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
   function filter(){const q=normalize(search.val()||'');let count=0;list.children('label').each(function(){const match=normalize($(this).text()).includes(q);$(this).toggle(match);if(match)count++});root.find('.cel-tax-empty').prop('hidden',count!==0);}
   list.on('change','input',refresh);search.on('input',filter);
   root.find('input:not([type=checkbox])').on('keydown',e=>{if(e.key==='Enter'){e.preventDefault();if($(e.target).hasClass('cel-tax-new'))root.find('.cel-tax-add').trigger('click')}});
   root.find('.cel-tax-add').on('click',function(){
     const button=$(this),input=root.find('.cel-tax-new'),name=input.val().trim(),status=root.find('.cel-tax-status');
     if(!name){status.text('Indique un nom.');return;}
     button.prop('disabled',true);status.text('Création…');
     $.post(ajaxurl,{action:'cel_library_create_term',nonce:root.data('nonce'),key:root.data('key'),name}).done(r=>{
       if(!r.success){status.text(typeof r.data==='string'?r.data:'Création impossible.');return;}
       let choice=list.find('input').filter(function(){return Number(this.value)===Number(r.data.id)});
       if(!choice.length){choice=$('<input>',{type:'checkbox',name:'cel_lib['+root.data('key')+'][]',value:r.data.id});$('<label>').append(choice,' ',$('<span>').text(r.data.name)).appendTo(list);}
       choice.prop('checked',true);input.val('');search.val('');refresh();filter();status.text('Valeur créée et sélectionnée.');
     }).fail(xhr=>status.text(xhr.responseJSON?.data||'Impossible de créer cette valeur. Réessaie.')).always(()=>button.prop('disabled',false));
   });
   refresh();filter();
 });
});
