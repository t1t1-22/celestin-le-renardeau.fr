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
