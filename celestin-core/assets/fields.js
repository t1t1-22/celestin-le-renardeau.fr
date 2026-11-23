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
