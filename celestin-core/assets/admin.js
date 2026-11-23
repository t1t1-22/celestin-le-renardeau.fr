jQuery(function($){
  $('.cel-media-choose').on('click',function(e){
    e.preventDefault();var row=$(this).closest('.cel-media-setting');
    var picker=wp.media({title:'Choisir une illustration de Célestin',button:{text:'Utiliser cette image'},multiple:false});
    picker.on('select',function(){
      var a=picker.state().get('selection').first().toJSON();
      row.find('.cel-media-id').val(a.id);
      row.find('.cel-media-preview').attr('src',a.url).show();
    });
    picker.open();
  });
  $('.cel-media-remove').on('click',function(e){e.preventDefault();var row=$(this).closest('.cel-media-setting');row.find('.cel-media-id').val('');row.find('.cel-media-preview').hide().attr('src','');});
});
